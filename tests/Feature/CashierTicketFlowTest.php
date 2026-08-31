<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cart;
use App\Models\CashierDraft;
use App\Models\DeliveryFees;
use App\Models\Order;
use App\Models\PaymentRecord;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Cashier multi-ticket POS - tests the A..N scenarios from the cashier spec.
 * SAFE: every test runs inside a transaction that is rolled back in tearDown,
 * so the live MySQL business database is never modified.
 */
class CashierTicketFlowTest extends BaseTestCase
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
            'name' => $name . '-' . rand(100000, 999999),
            'address' => 'addr',
            'status' => 'Active',
        ]);
    }

    private function makeUser(string $role, int $branchId, string $slug): User
    {
        return User::create([
            'name' => $role . rand(100000, 999999),
            'email' => $slug . rand(100000, 999999) . '@t.com',
            'password' => bcrypt('secret'),
            'role' => $role,
            'status' => 'Active',
            'branch_id' => $branchId,
        ]);
    }

    private function productRef(int $offset = 0): array
    {
        $products = Product::whereNotNull('qty')->orderBy('id')->get();
        $product = $products[$offset % max(1, $products->count())];
        $size = ProductSize::where('product_id', $product->id)->first();

        return [$product, $size ? $size->size : 'Standard'];
    }

    private function newTicket(User $cashier): CashierDraft
    {
        $this->be($cashier);
        $res = $this->postJson(route('cashier.new'));
        $res->assertOk();
        $code = $res->json('orderCode');
        $this->assertNotEmpty($code);

        return CashierDraft::where('order_code', $code)->firstOrFail();
    }

    private function addToCurrent(Product $product, string $size, int $qty = 1, string $notes = '')
    {
        return $this->post(route('cashier.cart.add'), [
            'product_id' => $product->id,
            'quantity'   => $qty,
            'size'       => $size,
            'notes'      => $notes,
        ]);
    }

    private function chargePayload(string $method = 'cash'): array
    {
        return [
            'paymentMethod' => $method,
            'cashReceived'  => 5000,
            'changeDue'     => 0,
            'orderType'     => 'eat_in',
            'totalAmount'   => 999, // MUST be ignored server-side
        ];
    }

    // ---------- Test A: New Ticket ----------

    public function test_a_new_ticket_starts_empty_and_is_current()
    {
        $branch = $this->makeBranch('B1');
        $cashier = $this->makeUser('cashier', $branch->id, 'a');

        $this->be($cashier);
        $this->get(route('cashier.index'))->assertOk()->assertSee('No Active Ticket');

        $draft = $this->newTicket($cashier);

        $this->assertDatabaseHas('cashier_drafts', [
            'order_code' => $draft->order_code,
            'cashier_id' => $cashier->id,
            'branch_id'  => $branch->id,
            'status'     => 'active',
        ]);

        // Empty cart for the freshly created ticket
        $this->assertSame(0, Cart::where('orderCode', $draft->order_code)->count());
        $this->assertSame($draft->order_code, session('cashierOrderCode'));

        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($draft->order_code)
            ->assertSee('No items on this ticket yet.');

        // Two "New Order" clicks create two distinct tickets
        $second = $this->newTicket($cashier);
        $this->assertNotSame($draft->order_code, $second->order_code);
        $this->assertSame(2, CashierDraft::where('cashier_id', $cashier->id)->count());
    }

    // ---------- Test B: Add Items ----------

    public function test_b_added_items_belong_to_the_current_ticket()
    {
        [$p, $size] = $this->productRef(0);
        $draft = $this->newTicket($this->makeUser('cashier', $this->makeBranch('B')->id, 'b'));

        $this->addToCurrent($p, $size, 2, 'less sugar')->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'orderCode' => $draft->order_code,
            'product_id' => $p->id,
            'qty' => 2,
        ]);

        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($p->name)
            ->assertSee('less sugar');
    }

    // ---------- Test C: Hold / Suspend ----------

    public function test_c_hold_saves_the_ticket_without_creating_an_order_or_payment()
    {
        [$p, $size] = $this->productRef(0);
        $draft = $this->newTicket($this->makeUser('cashier', $this->makeBranch('B')->id, 'c'));
        $this->addToCurrent($p, $size);

        $this->post(route('cashier.suspend', $draft->order_code))->assertOk();

        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draft->order_code, 'status' => 'suspended']);

        // Cart is kept, but no order and no payment were created
        $this->assertSame(1, Cart::where('orderCode', $draft->order_code)->count());
        $this->assertSame(0, Order::where('order_code', $draft->order_code)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $draft->order_code)->count());

        // Current ticket is cleared -> empty state, but the ticket stays listed
        $this->assertNull(session('cashierOrderCode'));
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($draft->order_code)
            ->assertSee('Suspended')
            ->assertSee('No Active Ticket');
    }

    // ---------- Test D: Second Ticket ----------

    public function test_d_second_ticket_starts_empty_after_hold()
    {
        [$p, $size] = $this->productRef(0);
        $cashier = $this->makeUser('cashier', $this->makeBranch('B')->id, 'd');
        $draftA = $this->newTicket($cashier);
        $this->addToCurrent($p, $size);
        $this->post(route('cashier.suspend', $draftA->order_code))->assertOk();

        $draftB = $this->newTicket($cashier);

        $this->assertSame(0, Cart::where('orderCode', $draftB->order_code)->count());
        $this->assertSame(1, Cart::where('orderCode', $draftA->order_code)->count());
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draftA->order_code, 'status' => 'suspended']);
    }

    // ---------- Tests E, F, G: Independent carts + Resume ----------

    public function test_e_f_g_independent_carts_never_mix_and_resume_restores_exact_cart()
    {
        [$p1, $size1] = $this->productRef(0);
        [$p2, $size2] = $this->productRef(1);
        $cashier = $this->makeUser('cashier', $this->makeBranch('B')->id, 'efg');

        $draftA = $this->newTicket($cashier);
        $this->addToCurrent($p1, $size1)->assertRedirect();
        $this->post(route('cashier.suspend', $draftA->order_code))->assertOk();

        $draftB = $this->newTicket($cashier);
        $this->addToCurrent($p2, $size2)->assertRedirect();

        // Resume A -> only A's product, never B's
        $this->post(route('cashier.resume', $draftA->order_code))->assertRedirect();
        $this->assertSame($draftA->order_code, session('cashierOrderCode'));
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($p1->name)
            ->assertDontSee($p2->name);

        // Resume B -> only B's product, never A's
        $this->post(route('cashier.resume', $draftB->order_code))->assertRedirect();
        $this->assertSame($draftB->order_code, session('cashierOrderCode'));
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($p2->name)
            ->assertDontSee($p1->name);

        // Resume A again -> still the exact A cart
        $this->post(route('cashier.resume', $draftA->order_code))->assertRedirect();
        $this->get(route('cashier.index'))->assertOk()->assertSee($p1->name)->assertDontSee($p2->name);
    }

    // ---------- Test H: Order type isolation ----------

    public function test_h_order_type_is_per_ticket_and_restored_on_resume()
    {
        $cashier = $this->makeUser('cashier', $this->makeBranch('B')->id, 'h');

        $draftA = $this->newTicket($cashier);
        $this->postJson(route('cashier.orderType'), ['orderType' => 'take_away', 'deliveryLocation' => null])
            ->assertOk();
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draftA->order_code, 'order_type' => 2]);
        $this->post(route('cashier.suspend', $draftA->order_code))->assertOk();

        $draftB = $this->newTicket($cashier);
        $this->postJson(route('cashier.orderType'), ['orderType' => 'delivery', 'deliveryLocation' => null])
            ->assertOk();
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draftB->order_code, 'order_type' => 3]);
        $this->post(route('cashier.suspend', $draftB->order_code))->assertOk();

        // Resume A -> Take Away is selected
        $this->post(route('cashier.resume', $draftA->order_code))->assertRedirect();
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('value="take_away" selected', false)
            ->assertDontSee('value="eat_in" selected', false);

        // Resume B -> Delivery is selected
        $this->post(route('cashier.resume', $draftB->order_code))->assertRedirect();
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('value="delivery" selected', false)
            ->assertDontSee('value="eat_in" selected', false);
    }

    // ---------- Test I: Confirm & Pay ----------

    public function test_i_charge_creates_order_and_payment_archives_ticket_and_keeps_others()
    {
        $branch = $this->makeBranch('B');
        $cashier = $this->makeUser('cashier', $branch->id, 'i');
        [$p1, $size1] = $this->productRef(0);

        // Ticket A being finalized
        $draftA = $this->newTicket($cashier);
        $this->addToCurrent($p1, $size1, 2, 'no ice')->assertRedirect();

        // Ticket B must stay untouched by finalizing A
        [$p2, $size2] = $this->productRef(1);
        $draftB = $this->newTicket($cashier);
        $this->addToCurrent($p2, $size2)->assertRedirect();
        $this->post(route('cashier.suspend', $draftB->order_code))->assertOk();

        // Make A current again
        $this->post(route('cashier.resume', $draftA->order_code))->assertRedirect();

        // Finalize A with the existing order/payment engine
        $this->postJson(route('cashier.charge', $draftA->order_code), $this->chargePayload('cash'))
            ->assertOk()
            ->assertJson(['orderCode' => $draftA->order_code]);

        // Orders created with the correct structure (status 1, cashier branch, no waiter)
        $orders = Order::where('order_code', $draftA->order_code)->get();
        $this->assertCount(1, $orders);
        $this->assertSame(1, (int) $orders->first()->status);
        $this->assertSame($branch->id, (int) $orders->first()->branch_id);
        $this->assertNull($orders->first()->waiter_id);
        $this->assertNull($orders->first()->session_id);
        $this->assertSame($cashier->id, (int) $orders->first()->user_id);

        // One payment record, server-side total (client totalAmount ignored)
        $payments = PaymentRecord::where('order_code', $draftA->order_code)->get();
        $this->assertCount(1, $payments);
        $this->assertGreaterThan(0, (float) $payments->first()->net_amount);

        // Draft archived + cart removed + session cleared
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draftA->order_code, 'status' => 'paid']);
        $this->assertSame(0, Cart::where('orderCode', $draftA->order_code)->count());
        $this->assertNull(session('cashierOrderCode'));

        // B is completely untouched
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draftB->order_code, 'status' => 'suspended']);
        $this->assertSame(1, Cart::where('orderCode', $draftB->order_code)->count());
        $this->assertSame(0, Order::where('order_code', $draftB->order_code)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $draftB->order_code)->count());

        // Existing bill/payment-slip engine is reused after finalization
        $this->post('/admin/order/generatePaymentSlip', ['orderCode' => $draftA->order_code])->assertOk();

        // Test N (embedded): duplicate finalization rejected
        $this->postJson(route('cashier.charge', $draftA->order_code), $this->chargePayload('cash'))
            ->assertStatus(422);
        $this->assertSame(1, PaymentRecord::where('order_code', $draftA->order_code)->count());
    }

    // ---------- Test J: Discard ----------

    public function test_j_discard_removes_draft_and_cart_without_order_or_payment()
    {
        [$p, $size] = $this->productRef(0);
        $draft = $this->newTicket($this->makeUser('cashier', $this->makeBranch('B')->id, 'j'));
        $this->addToCurrent($p, $size)->assertRedirect();

        $this->post(route('cashier.discard', $draft->order_code))->assertRedirect();

        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draft->order_code, 'status' => 'discarded']);
        $this->assertSame(0, Cart::where('orderCode', $draft->order_code)->count());
        $this->assertSame(0, Order::where('order_code', $draft->order_code)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $draft->order_code)->count());
        $this->assertNull(session('cashierOrderCode'));
    }

    // ---------- Test K: Refresh / browser recovery ----------

    public function test_k_refresh_keeps_the_current_ticket_with_its_exact_cart()
    {
        [$p, $size] = $this->productRef(0);
        $draft = $this->newTicket($this->makeUser('cashier', $this->makeBranch('B')->id, 'k'));
        $this->addToCurrent($p, $size, 3, 'keep me')->assertRedirect();

        $this->get(route('cashier.index'))->assertOk()->assertSee($p->name)->assertSee('keep me');
        // Second (F5-style) load - the ticket and cart are intact
        $this->get(route('cashier.index'))
            ->assertOk()
            ->assertSee($draft->order_code)
            ->assertSee($p->name)
            ->assertSee('keep me');
        $this->assertSame($draft->order_code, session('cashierOrderCode'));
    }

    // ---------- Test L: Cashier ownership ----------

    public function test_l_another_cashier_cannot_touch_my_draft()
    {
        $branch = $this->makeBranch('B');
        $c1 = $this->makeUser('cashier', $branch->id, 'l1');
        $c2 = $this->makeUser('cashier', $branch->id, 'l2');

        $draft = $this->newTicket($c1);
        $this->addToCurrent(...$this->productRef(0))->assertRedirect();

        $this->be($c2);
        $this->post(route('cashier.suspend', $draft->order_code))->assertStatus(404);
        $this->post(route('cashier.resume', $draft->order_code))->assertStatus(404);
        $this->post(route('cashier.discard', $draft->order_code))->assertStatus(404);
        $this->postJson(route('cashier.charge', $draft->order_code), $this->chargePayload())->assertStatus(404);

        // c2 cannot even see c1's ticket
        $this->get(route('cashier.index'))->assertOk()->assertDontSee($draft->order_code);

        // c1's ticket is unharmed
        $this->assertDatabaseHas('cashier_drafts', ['order_code' => $draft->order_code, 'status' => 'active']);
        $this->assertSame(1, Cart::where('orderCode', $draft->order_code)->count());
    }

    // ---------- Test M: Branch isolation ----------

    public function test_m_cross_branch_cashier_cannot_touch_the_draft()
    {
        $branch1 = $this->makeBranch('B1');
        $branch2 = $this->makeBranch('B2');
        $c1 = $this->makeUser('cashier', $branch1->id, 'm1');
        $c2 = $this->makeUser('cashier', $branch2->id, 'm2');

        $draft = $this->newTicket($c1);
        $this->addToCurrent(...$this->productRef(0))->assertRedirect();

        $this->be($c2);
        $this->post(route('cashier.suspend', $draft->order_code))->assertStatus(404);
        $this->post(route('cashier.resume', $draft->order_code))->assertStatus(404);
        $this->post(route('cashier.discard', $draft->order_code))->assertStatus(404);
        $this->postJson(route('cashier.charge', $draft->order_code), $this->chargePayload())->assertStatus(404);

        $this->get(route('cashier.index'))->assertOk()->assertDontSee($draft->order_code);
    }

    // ---------- Middleware: only cashier/admin may access the POS ----------

    public function test_waiter_and_chef_are_rejected_from_cashier_routes()
    {
        $branch = $this->makeBranch('B');
        $waiter = $this->makeUser('waiter', $branch->id, 'w');
        $chef = $this->makeUser('chef', $branch->id, 'ch');

        $this->be($waiter);
        $this->get(route('cashier.index'))->assertStatus(403);
        $this->post(route('cashier.new'))->assertStatus(403);

        $this->be($chef);
        $this->get(route('cashier.index'))->assertStatus(403);
    }
}