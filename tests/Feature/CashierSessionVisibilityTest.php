<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cart;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Waiter -> Request Bill -> Cashier visibility audit fixes.
 * SAFE: every test runs inside a transaction that is rolled back in tearDown,
 * so the live MySQL business database is never modified.
 */
class CashierSessionVisibilityTest extends BaseTestCase
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

    private function makeSessionOrder(CustomerSession $session, int $branchId): Order
    {
        $products = Product::whereNotNull('qty')->orderBy('id')->get();
        $product  = $products->first();
        $size     = ProductSize::where('product_id', $product->id)->first();

        return Order::create([
            'user_id'        => $session->waiter_id,
            'waiter_id'      => $session->waiter_id,
            'branch_id'      => $branchId,
            'session_id'     => $session->id,
            'product_id'     => $product->id,
            'order_code'     => 'ORD-' . rand(100000, 999999),
            'quantity'       => 2,
            'totalprice'     => (float) optional($size)->price ?? 100,
            'status'         => 1,
            'payment_method' => 'cash',
            'order_type'     => 1,
            'size'           => $size ? $size->size : 'Standard',
            'notes'          => '',
        ]);
    }

    private function settlePayload(): array
    {
        return ['paymentMethod' => 'card', 'cashReceived' => 0];
    }

    public function test_same_branch_cashier_sees_bill_requested_session()
    {
        $branch = $this->makeBranch('V1');
        $cashier = $this->makeUser('cashier', $branch->id, 'v1c');
        $waiter = $this->makeUser('waiter', $branch->id, 'v1w');

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiter->id,
            'branch_id' => $branch->id,
            'status' => CustomerSession::STATUS_BILL_REQUESTED,
            'opened_at' => now(),
            'bill_requested_at' => now(),
        ]);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($cashier);
        $this->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee('Awaiting Bill Settlement');
        $this->get(route('cashier.sessionDetails', $session->id))->assertOk();
    }

    public function test_admin_sees_and_can_settle_another_branches_session()
    {
        $b1 = $this->makeBranch('V2a');
        $b2 = $this->makeBranch('V2b');
        $admin = $this->makeUser('admin', $b1->id, 'v2a');   // admin lives on branch 1
        $waiter = $this->makeUser('waiter', $b2->id, 'v2w'); // bill on branch 2

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiter->id,
            'branch_id' => $b2->id,
            'status' => CustomerSession::STATUS_BILL_REQUESTED,
            'opened_at' => now(),
            'bill_requested_at' => now(),
        ]);
        $this->makeSessionOrder($session, $b2->id);

        // A plain branch-1 cashier must NOT see it (isolation preserved)
        $branch1Cashier = $this->makeUser('cashier', $b1->id, 'v2c1');
        $this->be($branch1Cashier);
        $this->get(route('cashier.sessions'))->assertOk()->assertDontSee($session->session_code);

        // The admin CAN see the cross-branch bill and settle it
        $this->be($admin);
        $this->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload())
            ->assertRedirect();

        $this->assertSame(CustomerSession::STATUS_CLOSED, CustomerSession::find($session->id)->status);
        $this->assertSame(1, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_open_session_bill_cannot_be_printed_before_request()
    {
        $branch = $this->makeBranch('V3');
        $cashier = $this->makeUser('cashier', $branch->id, 'v3c');
        $waiter = $this->makeUser('waiter', $branch->id, 'v3w');

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiter->id,
            'branch_id' => $branch->id,
            'status' => CustomerSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($cashier);
        $this->get(route('cashier.sessionBill', $session->id))
            ->assertRedirect(route('cashier.sessionDetails', $session->id));
    }

    public function test_request_bill_double_submit_is_idempotent()
    {
        $branch = $this->makeBranch('V4');
        $waiter = $this->makeUser('waiter', $branch->id, 'v4w');

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiter->id,
            'branch_id' => $branch->id,
            'status' => CustomerSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($waiter);
        $this->post(route('waiter.requestBill', $session->id))->assertRedirect();
        $this->post(route('waiter.requestBill', $session->id))->assertRedirect();

        $fresh = CustomerSession::find($session->id);
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, $fresh->status);
        $this->assertNotNull($fresh->bill_requested_at);
        $this->assertSame(0, PaymentRecord::where('order_code', $fresh->settlementCode())->count());
    }

    public function test_waiter_cannot_request_bill_for_another_waiters_session()
    {
        $branch = $this->makeBranch('V5');
        $waiterA = $this->makeUser('waiter', $branch->id, 'v5a');
        $waiterB = $this->makeUser('waiter', $branch->id, 'v5b');

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiterA->id,
            'branch_id' => $branch->id,
            'status' => CustomerSession::STATUS_OPEN,
            'opened_at' => now(),
        ]);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($waiterB);
        $this->post(route('waiter.requestBill', $session->id))->assertStatus(404);

        $this->assertSame(CustomerSession::STATUS_OPEN, CustomerSession::find($session->id)->status);
    }

    public function test_pending_bill_count_is_surfaced_on_the_pos_index()
    {
        $branch = $this->makeBranch('V6');
        $cashier = $this->makeUser('cashier', $branch->id, 'v6c');
        $waiter = $this->makeUser('waiter', $branch->id, 'v6w');

        $session = CustomerSession::create([
            'session_code' => 'SES-' . rand(100000, 999999),
            'waiter_id' => $waiter->id,
            'branch_id' => $branch->id,
            'status' => CustomerSession::STATUS_BILL_REQUESTED,
            'opened_at' => now(),
            'bill_requested_at' => now(),
        ]);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($cashier);
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('Running Bills');
    }
}