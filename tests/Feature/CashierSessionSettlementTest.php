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
 * Waiter Running-Bill session settlement (#2) + waiter payment timing (#1).
 * SAFE: every test runs inside a transaction that is rolled back in tearDown,
 * so the live MySQL business database is never modified.
 */
class CashierSessionSettlementTest extends BaseTestCase
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

        return [$product, $size ? $size->size : 'Medium'];
    }

    private function makeSession(User $waiter, int $branchId, string $status = CustomerSession::STATUS_OPEN): CustomerSession
    {
        return CustomerSession::create([
            'session_code'   => 'SES-' . rand(100000, 999999),
            'waiter_id'      => $waiter->id,
            'branch_id'      => $branchId,
            'status'         => $status,
            'opened_at'      => now(),
            'bill_requested_at' => $status === CustomerSession::STATUS_BILL_REQUESTED ? now() : null,
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

    private function settlePayload(string $method = 'cash', float $cash = 5000): array
    {
        return [
            'paymentMethod' => $method,
            'cashReceived'  => $cash,
        ];
    }

    // ---------- #2: session settlement lifecycle ----------

    public function test_open_session_cannot_be_settled()
    {
        $branch = $this->makeBranch('S1');
        $cashier = $this->makeUser('cashier', $branch->id, 'o1');
        $waiter = $this->makeUser('waiter', $branch->id, 'o1w');

        $session = $this->makeSession($waiter, $branch->id);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload())
            ->assertRedirect();

        $this->assertSame(CustomerSession::STATUS_OPEN, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_open_session_is_visible_but_not_payable_on_list_and_detail()
    {
        $branch = $this->makeBranch('S2');
        $cashier = $this->makeUser('cashier', $branch->id, 'o2');
        $waiter = $this->makeUser('waiter', $branch->id, 'o2w');

        $session = $this->makeSession($waiter, $branch->id);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($cashier);
        $this->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee($session->session_code)
            ->assertSee('bill not requested');
        $this->get(route('cashier.sessionDetails', $session->id))
            ->assertOk()
            ->assertDontSee('Settle Session');
    }

    public function test_bill_requested_session_settles_once_with_one_consolidated_payment()
    {
        $branch = $this->makeBranch('S3');
        $cashier = $this->makeUser('cashier', $branch->id, 'o3');
        $waiter = $this->makeUser('waiter', $branch->id, 'o3w');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch->id);
        $this->makeSessionOrder($session, $branch->id, ['order_code' => 'ORD-X' . rand(100000, 999999)]);
        // a rejected ticket line must survive settlement untouched
        $this->makeSessionOrder($session, $branch->id, ['status' => 3]);

        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload('cash'))
            ->assertRedirect();

        // Session closed + single consolidated PaymentRecord
        $this->assertSame(CustomerSession::STATUS_CLOSED, CustomerSession::find($session->id)->status);
        $records = PaymentRecord::where('order_code', $session->settlementCode())->get();
        $this->assertCount(1, $records);
        $this->assertGreaterThan(0, (float) $records->first()->net_amount);

        // All non-rejected orders -> completed (2); rejected stay rejected (3)
        $this->assertSame(2, Order::where('session_id', $session->id)->where('status', '!=', 3)->count());
        $this->assertSame(0, Order::where('session_id', $session->id)->where('status', 1)->count());
        $this->assertSame(1, Order::where('session_id', $session->id)->where('status', 3)->count());

        // Settled session disappears from the "running bills" list.
        // NOTE: the success flash contains "Bill #SET-SES-...", so we cannot
        // assert on the bare session code here; assert the list is empty.
        $this->get(route('cashier.sessions'))
            ->assertOk()
            ->assertSee('No bills waiting to be settled right now.')
            ->assertSee('No open sessions are being served right now.');

        // A settled session cannot be settled again (no duplicate payment)
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload('card'))
            ->assertRedirect();
        $this->assertSame(1, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_card_and_mobile_settlement_do_not_require_cash()
    {
        $branch = $this->makeBranch('S4');
        $cashier = $this->makeUser('cashier', $branch->id, 'o4');
        $waiter = $this->makeUser('waiter', $branch->id, 'o4w');

        foreach (['card', 'mobile'] as $method) {
            $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
            $this->makeSessionOrder($session, $branch->id);

            $this->be($cashier);
            $this->post(route('cashier.settleSession', $session->id), $this->settlePayload($method, 0))
                ->assertRedirect();

            $record = PaymentRecord::where('order_code', $session->settlementCode())->first();
            $this->assertNotNull($record);
            $this->assertSame(ucfirst($method), $record->payment_method);
            $this->assertSame((float) $record->net_amount, (float) $record->paid_amount);
        }
    }

    public function test_insufficient_cash_is_rejected_without_payment()
    {
        $branch = $this->makeBranch('S5');
        $cashier = $this->makeUser('cashier', $branch->id, 'o5');
        $waiter = $this->makeUser('waiter', $branch->id, 'o5w');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch->id, ['quantity' => 1, 'totalprice' => 99999]);

        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload('cash', 1))
            ->assertRedirect();

        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_empty_session_cannot_be_settled()
    {
        $branch = $this->makeBranch('S6');
        $cashier = $this->makeUser('cashier', $branch->id, 'o6');
        $waiter = $this->makeUser('waiter', $branch->id, 'o6w');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);

        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload())
            ->assertRedirect();

        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    // ---------- #2: ownership / branch isolation ----------

    public function test_cross_branch_cashier_cannot_view_or_settle_a_session()
    {
        $branch1 = $this->makeBranch('S7a');
        $branch2 = $this->makeBranch('S7b');
        $waiter = $this->makeUser('waiter', $branch1->id, 'o7w');
        $otherCashier = $this->makeUser('cashier', $branch2->id, 'o7c');

        $session = $this->makeSession($waiter, $branch1->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch1->id);

        $this->be($otherCashier);
        $this->get(route('cashier.sessionDetails', $session->id))->assertStatus(404);
        $this->get(route('cashier.sessionBill', $session->id))->assertStatus(404);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload())->assertStatus(404);

        // Not even visible in the branch list
        $this->get(route('cashier.sessions'))
            ->assertOk()
            ->assertDontSee($session->session_code);

        // Unharmed
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_waiter_cannot_settle_a_session()
    {
        $branch = $this->makeBranch('S8');
        $waiter = $this->makeUser('waiter', $branch->id, 'o8w');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch->id);

        $this->be($waiter);
        $this->get(route('cashier.sessions'))->assertStatus(403);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload())->assertStatus(403);

        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);
        $this->assertSame(0, PaymentRecord::where('order_code', $session->settlementCode())->count());
    }

    public function test_settled_bill_prints_session_bill_slip()
    {
        $branch = $this->makeBranch('S9');
        $cashier = $this->makeUser('cashier', $branch->id, 'o9');
        $waiter = $this->makeUser('waiter', $branch->id, 'o9w');

        $session = $this->makeSession($waiter, $branch->id, CustomerSession::STATUS_BILL_REQUESTED);
        $this->makeSessionOrder($session, $branch->id);
        $this->makeSessionOrder($session, $branch->id, ['order_code' => 'ORD-Y' . rand(100000, 999999)]);

        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload('cash'))
            ->assertRedirect();

        $this->get(route('cashier.sessionBill', $session->id))
            ->assertOk()
            ->assertSee($session->settlementCode());
        $this->get(route('cashier.sessionDetails', $session->id))
            ->assertOk()
            ->assertSee($session->settlementCode())
            ->assertSee('Print Bill');
    }

    // ---------- #1: waiter money is recorded only at settlement ----------

    public function test_waiter_place_order_creates_orders_but_no_payment_record()
    {
        $branch = $this->makeBranch('W1');
        $waiter = $this->makeUser('waiter', $branch->id, 'w1');
        [$p, $size] = $this->productRef();

        $code = 'WP-' . rand(100000, 999999);
        Cart::create([
            'user_id'   => $waiter->id,
            'product_id'=> $p->id,
            'orderCode' => $code,
            'size'      => $size,
            'qty'       => 2,
            'notes'     => 'no ice',
        ]);

        $this->be($waiter);
        $this->post(route('waiter.placeOrder'), [
            'orderCode'     => $code,
            'paymentMethod' => 'cash',
            'orderType'     => 'eat_in',
            'totalAmount'   => 999,
        ])->assertRedirect();

        // Kitchen orders exist, but money is NOT recorded yet
        $this->assertSame(1, Order::where('order_code', $code)->count());
        $this->assertSame(1, Order::where('order_code', $code)->where('status', 1)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $code)->count());

        // Edits (the old payment-record update path) still create no payment
        $orderItem = Order::where('order_code', $code)->first();
        $this->post(route('waiter.updateOrderItem'), [
            'order_id' => $orderItem->id,
            'quantity' => 3,
            'notes'    => 'changed',
        ])->assertRedirect();
        $this->assertSame(0, PaymentRecord::where('order_code', $code)->count());
    }

    public function test_waiter_place_session_order_creates_no_payment_record_until_settled()
    {
        $branch = $this->makeBranch('W2');
        $waiter = $this->makeUser('waiter', $branch->id, 'w2');
        [$p, $size] = $this->productRef();

        $session = $this->makeSession($waiter, $branch->id);
        $orderCode = 'WSO-' . rand(100000, 999999);
        Cart::create([
            'user_id'   => $waiter->id,
            'product_id'=> $p->id,
            'orderCode' => $orderCode,
            'size'      => $size,
            'qty'       => 1,
            'notes'     => '',
        ]);

        $this->be($waiter);
        $this->post(route('waiter.placeSessionOrder', $session->id), [
            'orderCode'     => $orderCode,
            'paymentMethod' => 'cash',
            'orderType'     => 'eat_in',
        ])->assertRedirect();

        $this->assertSame(1, Order::where('order_code', $orderCode)->count());
        $this->assertSame(0, PaymentRecord::where('order_code', $orderCode)->count());

        // Request the bill -> cashier can settle -> money recorded exactly once
        $this->post(route('waiter.requestBill', $session->id))->assertRedirect();
        $this->assertSame(CustomerSession::STATUS_BILL_REQUESTED, CustomerSession::find($session->id)->status);

        $cashier = $this->makeUser('cashier', $branch->id, 'w2c');
        $this->be($cashier);
        $this->post(route('cashier.settleSession', $session->id), $this->settlePayload('cash'))
            ->assertRedirect();

        $this->assertSame(1, PaymentRecord::where('order_code', $session->settlementCode())->count());
        $this->assertSame(2, (int) Order::where('order_code', $orderCode)->first()->status);
    }
}