# Cashier POS – Testing Guide

The cashier panel is a **multi-ticket POS**: an independent cart per ticket
(order_code), Hold / Continue, per-ticket order type, and Confirm & Pay that
reuses the existing Order + PaymentRecord + payment-slip engine.

## SAFETY FIRST (see AGENTS.md)

`phpunit.xml` has the SQLite in-memory DB commented out, so `php artisan test`
(no filter) runs against the **real MySQL `coffeepos` database** and the
pre-existing Breeze `RegistrationTest` wipes all business data.

**Only run the cashier tests with a filter — they hold everything in a
transaction and roll back in `tearDown()`:**

```
php artisan test --filter=CashierTicketFlowTest
```

This test file is self-contained: each method wraps its work in
`DB::beginTransaction()` (setUp) ... `DB::rollBack()` (tearDown), so the live
database is never modified.

## What is covered (A..N)

| Scenario | Test method |
| --- | --- |
| A: New order = empty, current ticket; two tickets stay distinct | `test_a_new_ticket_starts_empty_and_is_current` |
| B: Added items belong to the current ticket (qty + notes shown) | `test_b_added_items_belong_to_the_current_ticket` |
| C: Hold saves the ticket WITHOUT creating order/payment | `test_c_hold_saves_the_ticket_without_creating_an_order_or_payment` |
| D: New ticket after hold is empty; the held cart stays intact | `test_d_second_ticket_starts_empty_after_hold` |
| E/F/G: Independent carts never mix; resume restores the exact cart | `test_e_f_g_independent_carts_never_mix_and_resume_restores_exact_cart` |
| H: Order type is per ticket and restored on resume | `test_h_order_type_is_per_ticket_and_restored_on_resume` |
| I: Charge creates orders (status=1) + payment with server-side total, archives ticket, keeps other tickets, then reuses the existing payment-slip endpoint | `test_i_charge_creates_order_and_payment_archives_ticket_and_keeps_others` |
| J: Discard removes draft + cart, no order/payment | `test_j_discard_removes_draft_and_cart_without_order_or_payment` |
| K: Refresh/F5 keeps the current ticket with its exact cart | `test_k_refresh_keeps_the_current_ticket_with_its_exact_cart` |
| L: Another cashier cannot touch my draft (404) | `test_l_another_cashier_cannot_touch_my_draft` |
| M: Cross-branch cashier cannot touch the draft (404) | `test_m_cross_branch_cashier_cannot_touch_the_draft` |
| N (embedded in I): duplicate charge rejected (422, single payment record) | inside `test_i_...` |
| Middleware: waiter/chef are rejected from cashier routes (403) | `test_waiter_and_chef_are_rejected_from_cashier_routes` |

## Manual smoke test

1. Login as a `cashier` role user → redirected to `cashier.index` (`/cashier`).
2. ** NEW ORDER** → a `CSR-…` ticket is created and becomes current.
3. Add products with size/quantity/notes → they appear on the ticket with
   live totals (tax + delivery fee are server-side).
4. Change **order type** (Eat at Shop / Take Away / Delivery) → Delivery shows
   the delivery-location selector and adds the fee.
5. **Hold / Suspend** → ticket goes to the Active Tickets strip (Suspended).
6. **Continue** a held ticket → its exact cart returns; carts never mix.
7. **Confirm & Pay** (cash/card/mobile) → prints/opens the payment slip, the
   draft is archived (paid), cart cleared; duplicate payment returns 422.
8. **Discard** removes a ticket without creating anything.
9. Refresh (F5) mid-ticket → same ticket + cart come back.

## Useful commands

- Lint: `php -l <file>`
- Routes: `php artisan route:list --path=cashier`
- Migrations: `php artisan migrate --force`
- Tests (safe): `php artisan test --filter=CashierTicketFlowTest`