<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cart;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\TaxSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * MUST-FIX 01 - Waiter "Request Bill" -> Cashier bill visible -> detail -> Print Bill.
 *
 * Drives the REAL route stack (waiter + cashier sides), exactly mirroring the
 * browser flow. SAFE: every test runs inside a DB transaction rolled back in
 * tearDown, so the live MySQL business database is never modified.
 */
class BillRequestWorkflowTest extends BaseTestCase
{
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // ---------- helpers ----------

    private function makeBranch(string $name): Branch
    {
        return Branch::create([
            'name'    => $name . '-' . rand(100000, 999999),
            'address' => 'addr',
            'status'  => 'Active',
        ]);
    }

    private function makeUser(string $role, int $branchId, string $slug): User
    {
        return User::create([
            'name'     => $role . rand(100000, 999999),
            'email'    => $slug . rand(100000, 999999) . '@t.com',
            'password' => bcrypt('secret'),
            'role'     => $role,
            'status'   => 'Active',
            'branch_id'=> $branchId,
        ]);
    }

    private function productRef(int $offset = 0): array
    {
        $products = Product::whereNotNull('qty')->orderBy('id')->get();
        $product  = $products[$offset % max(1, $products->count())];
        $size     = ProductSize::where('product_id', $product->id)->first();

        return [$product, $size ? $size->size : 'Standard'];
    }

    private function makeSession(User $waiter, int $branchId, string $status = CustomerSession::STATUS_OPEN): CustomerSession
    {
        return CustomerSession::create([
            'session_code'       => 'SES-' . rand(100000, 999999),
            'waiter_id'          => $waiter->id,
            'branch_id'          => $branchId,
            'status'             => $status,
            'opened_at'          => now(),
            'bill_requested_at'  => $status === CustomerSession::STATUS_BILL_REQUESTED ? now() : null,
        ]);
    }

    private function makeSessionOrder(CustomerSession $session, int $branchId, array $overrides = []): Order
    {
        [$p, $size] = $this->productRef();
        $price = (float) optional($p->sizes()->first())->price ?? 100;

        return Order::create(array_merge([
            'user_id'        => $session->waiter_id,
            'waiter_id'      => $session->waiter_id,
            'branch_id'      => $branchId,
            'session_id'     => $session->id,
            'product_id'     => $p->id,
            'order_code'     => 'ORD-' . rand(100000, 999999),
            'quantity'       => 2,
            'totalprice'     => $price,
            'status'         => 1,
            'payment_method' => 'cash',
            'order_type'     => 1,
            'size'           => $size,
            'notes'          => '',
        ], $overrides));
    }

    private function sessionTotal(CustomerSession $session): float
    {
        $subTotal = $session->subtotal();
        $taxRate  = (float) (optional(TaxSetting::first())->tax_rate ?? 0);
        $tax      = ceil(($subTotal * $taxRate / 100) / 10) * 10;

        return (float) ceil(($subTotal + $tax) / 10) * 10;
    }

    private function addSessionCart(User $waiter, string $orderCode, int $offset): void
    {
        [$p, $size] = $this->productRef($offset);
        Cart::create([
            'user_id'   => $waiter->id,
            'product_id'=> $p->id,
            'orderCode' => $orderCode,
            'size'      => $size,
            'qty'       => 2,
            'notes'     => '',
        ]);
    }

    // ---------- Test A: full waiter -> request -> cashier -> detail -> print ----------

    public function test_a_full_flow_waiter_requests_bill_cashier_sees_details_and_prints()
    {
        $branch  = $this->makeBranch('BF-A');
        $waiter  = $this->makeUser('waiter', $branch->id, 'bA');
        $cashier = $this->makeUser('cashier', $branch->id, 'bAc');

        // Waiter: start a session via the real route
        $this->actingAs($waiter)->post(route('waiter.createSession'))->assertRedirect();
        $session = CustomerSession::where('waiter_id', $waiter->id)->orderByDesc('id')->first();
        $this->assertNotNull($session);
        $this->assertSame(CustomerSession::STATUS_OPEN, $session->status);

        // Waiter: add 2 DIFFERENT items to the session cart, then place them
        $orderCode = 'SES-' . $session->id . '-' . now()->format('YmdHis') . '-ZZZZ';
        $this->addSessionCart($waiter, $orderCode, 0);
        $this->addSessionCart($waiter, $orderCode, 1);

        $this->actingAs($waiter)
            ->post(route('waiter.placeSessionOrder', $session->id), [
                'orderCode'     => $orderCode,
                'paymentMethod' => 'cash',
                'orderType'     => 'eat_in',
            ])->assertRedirect();

        $this->assertSame(2, Order::where('order_code', $orderCode)->count());
        $this->assertSame($session->id, (int) Order::where('order_code', $orderCode)->first()->session_id);
        $this->assertSame(CustomerSession::STATUS_OPEN, CustomerSession::find($session->id)->status);

        // Waiter: Request Bill -> status transition
        $this->actingAs($waiter)->post(route('waiter.requestBill', $session->id))->assertRedirect();

        $fresh = CustomerSession::find($session->id);
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, $fresh->status);
        $this->assertNotNull($fresh->bill_requested_at);
        $this->assertSame($branch->id, (int) $fresh->branch_id);
        $this->assertSame($waiter->id, (int) $fresh->waiter_id);
        $this->assertSame(1, CustomerSession::where('id', $session->id)->count()); // no duplicate session
        $this->assertSame(2, Order::where('order_code', $orderCode)->count());     // no duplicate orders

        // Cashier: running bills list must show it
        $this->actingAs($cashier)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code);

        // Cashier: detail shows the 2 items + totals
        $total = $this->sessionTotal($fresh);
        $this->actingAs($cashier)
            ->get(route('cashier.sessionDetails', $session->id))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee(number_format($total, 2));

        // Cashier: POS page offers an obvious "Bill Requests" entry point
        $this->actingAs($cashier)
            ->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('Bill Requests');

        // Cashier: Print Bill opens the SAME totals, and does NOT settle
        $this->actingAs($cashier)
            ->get(route('cashier.sessionBill', $session->id))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee(number_format($total, 2))
            ->assertSee('Bill requested');

        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    // ---------- Test B: multiple requested bills are independent ----------

    public function test_b_multiple_requested_bills_are_independent()
    {
        $branch = $this->makeBranch('BF-B');
        $waiter = $this->makeUser('waiter', $branch->id, 'bB');
        $cashier = $this->makeUser('cashier', $branch->id, 'bBc');

        $sessions = [];
        foreach (['A', 'B', 'C'] as $tag) {
            $s = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
            for ($i = 0; $i < 2; $i++) {
                $this->makeSessionOrder($s, $branch->id);
            }
            $sessions[$tag] = $s;
        }

        // Cashier sees all three
        $this->actingAs($cashier)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($sessions['A']->session_code)
            ->assertSee($sessions['B']->session_code)
            ->assertSee($sessions['C']->session_code);

        // Opening/printing B must not alter A or C
        $this->actingAs($cashier)
            ->get(route('cashier.sessionBill', $sessions['B']->id))
            ->assertOk()
            ->assertSee($sessions['B']->session_code);

        foreach ($sessions as $tag => $s) {
            $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($s->id)->status);
            $this->assertSame(0, PaymentRecord::where('order_code', $s->settlementCode())->count());
        }
    }

    // ---------- Test C: branch isolation with the REAL seeded users ----------

    public function test_c_branch_isolation_with_seeded_users()
    {
        $waiter2   = User::where('email', 'waiter@gmail.com')->first();   // branch 2
        $cashier2  = User::where('email', 'cashier2@gmail.com')->first(); // branch 2
        $cashier1  = User::where('email', 'cashier@gmail.com')->first();  // branch 1
        $admin     = User::where('email', 'admin@gmail.com')->first();    // branch 1 (all branches)

        $this->assertNotNull($waiter2);
        $this->assertNotNull($cashier2);
        $this->assertNotNull($cashier1);
        $this->assertNotNull($admin);
        $this->assertSame(2, (int) $waiter2->branch_id);
        $this->assertSame(2, (int) $cashier2->branch_id);
        $this->assertSame(1, (int) $cashier1->branch_id);

        $session = $this->makeSession($waiter2, 2, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, 2);

        // Branch-2 cashier sees it
        $this->actingAs($cashier2)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code);
        $this->actingAs($cashier2)
            ->get(route('cashier.sessionDetails', $session->id))->assertOk();
        $this->actingAs($cashier2)
            ->get(route('cashier.sessionBill', $session->id))->assertOk();

        // Branch-1 cashier must NOT see / open / print / settle it
        $this->actingAs($cashier1)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertDontSee($session->session_code);
        $this->actingAs($cashier1)
            ->get(route('cashier.sessionDetails', $session->id))->assertStatus(404);
        $this->actingAs($cashier1)
            ->get(route('cashier.sessionBill', $session->id))->assertStatus(404);
        $this->actingAs($cashier1)
            ->post(route('cashier.settleSession', $session->id), ['paymentMethod' => 'cash', 'cashReceived' => 5000])
            ->assertStatus(404);

        // Admin (any branch) sees it
        $this->actingAs($admin)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code);

        // Unharmed
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    // ---------- Test D: open session cannot be printed as a final bill ----------

    public function test_d_open_session_cannot_print_a_requested_bill()
    {
        $branch = $this->makeBranch('BF-D');
        $waiter = $this->makeUser('waiter', $branch->id, 'bD');
        $cashier = $this->makeUser('cashier', $branch->id, 'bDc');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_OPEN);
        $this->makeSessionOrder($session, $branch->id);

        // Visible on the list but clearly not bill-ready
        $this->actingAs($cashier)
            ->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee('bill not requested');

        // Detail page has no settle form
        $this->actingAs($cashier)
            ->get(route('cashier.sessionDetails', $session->id))
            ->assertOk()
            ->assertDontSee('Settle Session');

        // Print endpoint refuses an open session (redirect, not a bill)
        $this->actingAs($cashier)
            ->get(route('cashier.sessionBill', $session->id))
            ->assertRedirect();
    }

    // ---------- Test E: duplicate request is idempotent ----------

    public function test_e_duplicate_request_bill_is_idempotent()
    {
        $branch = $this->makeBranch('BF-E');
        $waiter = $this->makeUser('waiter', $branch->id, 'bE');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_OPEN);
        $this->makeSessionOrder($session, $branch->id);

        // First request flips the session
        $this->actingAs($waiter)->post(route('waiter.requestBill', $session->id))->assertRedirect();
        $requestedAt = CustomerSession::find($session->id)->bill_requested_at;
        $this->assertNotNull($requestedAt);

        // Duplicate requests must be no-ops
        $this->actingAs($waiter)->post(route('waiter.requestBill', $session->id))->assertRedirect();
        $this->actingAs($waiter)->post(route('waiter.requestBill', $session->id))->assertRedirect();

        $fresh = CustomerSession::find($session->id);
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, $fresh->status);
        $this->assertNotNull($fresh->bill_requested_at);
        $this->assertSame(1, CustomerSession::where('waiter_id', $waiter->id)->where('branch_id', $branch->id)->count());
        $this->assertSame(1, Order::where('session_id', $session->id)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
        $this->assertSame((string) $requestedAt, (string) $fresh->bill_requested_at); // untouched by duplicates
    }

    // ---------- Test F: printed bill total matches cashier screen total ----------

    public function test_f_printed_bill_matches_screen_total()
    {
        $branch = $this->makeBranch('BF-F');
        $waiter = $this->makeUser('waiter', $branch->id, 'bF');
        $cashier = $this->makeUser('cashier', $branch->id, 'bFc');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch->id, ['quantity' => 3, 'totalprice' => 350]);
        $this->makeSessionOrder($session, $branch->id, ['quantity' => 1, 'totalprice' => 400, 'order_code' => 'ORD-Z' . rand(100000, 999999)]);

        $total = $this->sessionTotal($session);

        $screen = $this->actingAs($cashier)
            ->get(route('cashier.sessionDetails', $session->id))
            ->assertOk()
            ->assertSee(number_format($total, 2));

        $printed = $this->actingAs($cashier)
            ->get(route('cashier.sessionBill', $session->id))
            ->assertOk()
            ->assertSee(number_format($total, 2));

        // Both pages show the same final figure
        $this->assertTrue(str_contains($screen->getContent(), number_format($total, 2)));
        $this->assertTrue(str_contains($printed->getContent(), number_format($total, 2)));
    }
}