<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CustomerSession;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * End-to-end Chef Portal + KOT test following the spec's waiter -> kitchen flow.
 * Uses a single method with one transaction so auth/state stays consistent.
 */
class ChefPortalKotTest extends BaseTestCase
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

    public function test_waiter_to_kitchen_flow_with_kot_and_branch_security()
    {
        $suffix = rand(100000, 999999);

        // ---- Branch 1 / Branch 2 setup ----
        $branch1 = Branch::create(['name' => 'KOT Branch 1 ' . $suffix, 'address' => 'addr1', 'status' => 'Active']);
        $branch2 = Branch::create(['name' => 'KOT Branch 2 ' . $suffix, 'address' => 'addr2', 'status' => 'Active']);

        $waiter = User::create(['name' => 'Ali', 'email' => 'ali-' . $suffix . '@t.com', 'password' => bcrypt('x'), 'role' => 'waiter', 'status' => 'Active', 'branch_id' => $branch2->id]);
        $chef2  = User::create(['name' => 'Chef 2', 'email' => 'chef2-' . $suffix . '@t.com', 'password' => bcrypt('x'), 'role' => 'chef', 'status' => 'Active', 'branch_id' => $branch2->id]);
        $chef1  = User::create(['name' => 'Chef 1', 'email' => 'chef1-' . $suffix . '@t.com', 'password' => bcrypt('x'), 'role' => 'chef', 'status' => 'Active', 'branch_id' => $branch1->id]);

        $product = Product::whereNotNull('qty')->orderBy('id')->first();

        // ---- Waiter creates Session #1001 ----
        $session = CustomerSession::create([
            'session_code' => 'SES-1001-' . $suffix,
            'waiter_id' => $waiter->id,
            'branch_id' => $branch2->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        // ---- Step 3: Place Order #101 (Coffee x 2) into the session ----
        $code1 = 'ORD-101-' . $suffix;
        Order::create([
            'product_id' => $product->id, 'user_id' => $waiter->id, 'waiter_id' => $waiter->id,
            'branch_id' => $branch2->id, 'session_id' => $session->id, 'order_code' => $code1,
            'quantity' => 2, 'totalprice' => 5.00, 'status' => 1, 'size' => 'Large', 'notes' => 'Less sugar',
        ]);

        // Step 4: verify order fields
        $this->assertDatabaseHas('orders', ['order_code' => $code1, 'session_id' => $session->id, 'branch_id' => $branch2->id, 'waiter_id' => $waiter->id, 'status' => 1]);

        // ---- Non-chef cannot reach chef routes ----
        $this->be($waiter);
        $this->get('/chef/order/new')->assertStatus(302);

        // ---- Chef 2 (Branch 2) sees the new order ----
        $this->be($chef2);
        $this->get('/chef/order/new')->assertStatus(200)->assertSee($code1);

        // ---- Chef 1 (Branch 1) cannot see it ----
        $this->be($chef1);
        $this->get('/chef/order/new')->assertStatus(200)->assertDontSee($code1);

        // ---- Foreign branch chef cannot open/manipulate the order (404) ----
        $this->be($chef1);
        $this->get('/chef/order/' . $code1)->assertStatus(404);
        $this->post('/chef/order/' . $code1 . '/start')->assertStatus(404);

        // ---- Chef 1 cannot print this branch's KOT (403) ----
        $this->get('/kitchen/print/' . $code1)->assertStatus(403);

        // ---- Owning waiter CAN print the KOT (auto-print / reprint endpoint) ----
        $this->be($waiter);
        $this->get('/kitchen/print/' . $code1)->assertStatus(200);

        // ---- Chef 2 prints KOT + verifies contents (Step 6) ----
        $this->be($chef2);
        $kot1 = $this->get('/kitchen/print/' . $code1);
        $kot1->assertStatus(200)
            ->assertSee('KITCHEN ORDER')
            ->assertSee($code1)
            ->assertSee('SES-1001-' . $suffix)
            ->assertSee('Ali')
            ->assertSee($product->name)
            ->assertSee('Large')
            ->assertSee('Less sugar')
            ->assertDontSee('Payment Slip');

        // ---- Step 7: Chef starts preparing (NEW -> PREPARING) ----
        $r = $this->post('/chef/order/' . $code1 . '/start');
        $r->assertStatus(302);
        $this->assertDatabaseHas('orders', ['order_code' => $code1, 'status' => 4]);

        // ---- Step 8: Chef marks ready (PREPARING -> READY) ----
        $this->post('/chef/order/' . $code1 . '/ready')->assertStatus(302);
        $this->assertDatabaseHas('orders', ['order_code' => $code1, 'status' => 5]);

        // A ready order cannot go back to preparing (status stays 5)
        $this->post('/chef/order/' . $code1 . '/start')->assertStatus(302);
        $this->assertDatabaseHas('orders', ['order_code' => $code1, 'status' => 5]);

        // ---- Step 9: Waiter adds a SECOND order (Cake x 1) to the SAME session ----
        $code2 = 'ORD-102-' . $suffix;
        Order::create([
            'product_id' => $product->id, 'user_id' => $waiter->id, 'waiter_id' => $waiter->id,
            'branch_id' => $branch2->id, 'session_id' => $session->id, 'order_code' => $code2,
            'quantity' => 1, 'totalprice' => 8.00, 'status' => 1, 'size' => 'Medium', 'notes' => '',
        ]);
        $this->assertDatabaseHas('orders', ['order_code' => $code2, 'session_id' => $session->id, 'status' => 1]);

        // Chef 2 receives a NEW order for #102 - a NEW KOT - and #101 is untouched
        $this->be($chef2);
        $this->get('/chef/order/new')->assertStatus(200)->assertSee($code2)->assertDontSee($code1);
        $kot2 = $this->get('/kitchen/print/' . $code2);
        $kot2->assertStatus(200)->assertSee($code2)->assertSee('SES-1001-' . $suffix);

        // Previous order #101 KOT still reflects READY state and only its own items
        $this->assertDatabaseHas('orders', ['order_code' => $code1, 'status' => 5]);
        $this->assertDatabaseHas('orders', ['order_code' => $code2, 'status' => 1]);

        // Session remains OPEN (chef never closes it) - step 20 independence
        $this->assertDatabaseHas('customer_sessions', ['id' => $session->id, 'status' => 'open']);

        // Chef portal exposes no editing of order details (branch/qty/price never editable)
        $this->get('/chef/order/' . $code2)->assertStatus(200)
            ->assertDontSee('name="branch_id"')
            ->assertDontSee('name="totalprice"')
            ->assertDontSee('name="quantity"');
    }
}
