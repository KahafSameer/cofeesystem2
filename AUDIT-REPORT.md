# Master Audit Report — Waiter & Cashier Workflow

**Audit-only.** No code was modified during this audit. Findings, recommendations and a
minimal implementation plan follow; nothing is to be implemented until the report is
reviewed and approved.

**Audited code:** the cashier portal (`CashierController`, `CashierDraft`, `CashierMiddleware`,
`routes/cashier.php`, `resources/views/cashier/pos/index.blade.php`), the waiter portal
(`WaiterController`, `routes/waiter.php`, waiter views), the shared kitchen engine
(`ChefController`, `KitchenTicketService`), the legacy admin bill engine
(`Admin\OrderController`, admin payment-slip views, `AdminMiddleware`), the customer web flow
(`UserDashboardController`), the affected models, migrations and seeders.

**Scope note:** several areas called out by the original spec do NOT exist in the cashier
portal (`cashier/dashboard`, `cashier/orders/incoming`, `cashier/sessions`,
`cashier/reports`, `cashier/placeOrder`, `cashier/closeSession`, `cashier/payOrder`).
Confirmed by the route table and controller. Where the spec asked to "verify, don't assume",
this report treats them as **missing workflow features** rather than as bugs in existing code.

---

## 1. Executive Summary

The Waiter and Cashier portals both share the same `orders` / `carts` / `payment_records`
engine and both produce branch-scoped kitchen tickets. The recently built Cashier POS is
structurally the healthiest module: draft ownership, branch scoping, server-side totals,
transaction + row-lock on charge, and one-time KOT printing are all correctly implemented
and covered by a passing feature test (12 tests / 151 assertions).

The threats to the business are not in the new code — they are in the **role/middleware
layer** and in **when money is recorded**:

1. **Any staff role can reach every `/admin/*` route** — `AdminMiddleware` allows
   `admin / chef / cashier / waiter` (`AdminMiddleware.php:23`). That exposes order
   accept/reject, booking POS, payment-slip printing, discounts, tax rate, delivery fees,
   products, branches and user creation to every front-line employee with URL knowledge.
2. **Waiter payments are recorded before the customer pays.** `placeOrder` /
   `placeSessionOrder` create a "status 1 / paid" `PaymentRecord` the moment the order is
   sent to the kitchen — before any cash is taken. A session with 3 rounds creates 3 paid
   records while zero money has been collected.
3. **The waiter session flow dead-ends.** `requestBill` (open → `bill_requested`) is a
   one-way door with no consumer: no cashier settlement screen, no close, no single session
   bill. `bill_requested` sessions live forever.
4. **Online customer orders bypass the kitchen entirely.** Customer `paymentConfirm`
   creates orders with `branch_id = NULL`; the chef queries only `branch_id == own branch`,
   so web orders never appear on any kitchen screen.
5. **Delivery fee is inconsistent across portals.** The cashier adds a delivery fee
   (and rounds to 10); the waiter composes a delivery order with no delivery fee and no
   delivery location at all — the same delivery is priced differently depending on who takes
   the order.

These are design-level financial issues. The recommended fix sequence (P0 first) is listed
in sections 14 and 17; it is deliberately minimal and does not rebuild anything.

---

## 2. Current Architecture

### 2.1 Roles & middleware
| Role | Middleware | Branch-scoped | Redirect after login |
|---|---|---|---|
| admin | `AdminMiddleware` | no | `adminDashboard` |
| chef | `ChefMiddleware` (chef only) | yes (chef.branch_id) | `chef.dashboard` |
| cashier | `CashierMiddleware` (cashier + admin) | yes (cashier.branch_id) | `cashier.index` |
| waiter | `WaiterMiddleware` (waiter only) | yes (waiter.branch_id) | `waiter.dashboard` |
| user | `UserMiddleware` (user only) | n/a | `userDashboard` |

**`AdminMiddleware` is the security bottleneck** (see §7). All role middleware otherwise
only allow the named role, and `ChefMiddleware`/`WaiterMiddleware`/`CashierMiddleware`
return `back()` (not 403/abort) when the wrong role is logged in.

### 2.2 Shared domain model
- **`orders`** — one row per product line; one order group = one `order_code`.
  - `status` `char(1)`: `1` new, `2` completed/served, `3` rejected/cancelled,
    `4` preparing, `5` ready.
  - `order_type` int semantics: `1` eat_in, `2` take_away, `3` delivery.
  - Carries `waiter_id`, `branch_id`, `session_id`, `delivery_location_id` (all nullable).
- **`carts`** — shared cart table used by **five** entry surfaces: customer web, waiter
  new-order, waiter session, admin booking POS, cashier POS. Discriminator = `user_id` +
  `orderCode`.
- **`payment_records`** — one record per `order_code`, written at charge/place time (see §6).
- **`cashier_drafts`** — cashier-only ticket registry: `active / suspended / paid / discarded`,
  `order_type` (int), `delivery_location_id`.
- **`customer_sessions`** — waiter running-bill sessions: `open / bill_requested / closed`.

### 2.3 Data flow
```
Customer (web) -----> paymentConfirm ------> orders (branch NULL!) ----┐
Waiter cart --------> placeOrder ----------> orders (branch=waiter) --┼--> chef (branch-scoped)
Waiter session -----> placeSessionOrder ---> orders (session=X) ------┘      | new -> preparing -> ready
Cashier POS --------> charge --------------> orders (branch=cashier) --------┘
Admin booking ------> orderConfirm --------> orders (branch NULL)  (legacy, no chef routing)
```
- Kitchen consumes orders from waiter (placeOrder, placeSessionOrder) and cashier (charge).
  Customer/admin-created orders have `branch_id = NULL` and are invisible to the chef.

---

## 3. Cashier POS Findings

Routes verified: `cashier.index, new, suspend, resume, discard, cart.add, cart.update,
cart.remove, orderType, charge`. No `cashier/dashboard`, no incoming-orders page, no
session settlement, no reports.

| ID | Type | Severity | Location | Finding | Recommendation |
|----|------|----------|----------|---------|----------------|
| C-01 | DESIGN IMPROVEMENT | Medium | `charge()` `CashierController.php:345` | Card/Mobile inputs are cosmetic; server sets `paid_amount = total` for non-cash. No gateway/ref. | Add a payment-reference field (card last-4 / mobile ref) stored on the record; keep gateway integration out of scope. |
| C-02 | UX IMPROVEMENT | Low | `pos/index.blade.php:94-162` | No category selected ⇒ **empty product grid**; cashier must click a category each session. No "All" option. | Add an "All products" chip / default selected category. |
| C-03 | TECHNICAL DEBT | Low | `payment-slip.blade.php:10` + `pos/index.blade.php:542` | Slip triggers `window.print()` on load AND the popup calls `print()` → **double print dialog**. | Remove one of the two triggers. |
| C-04 | CONFIRMED BUG | Low | `setOrderType()` `CashierController.php:319` / `charge()` `:383,:410` | `deliveryLocation` is accepted even for non-delivery, and `charge` adds the fee from `delivery_location_id` regardless of `order_type`. A stale location id (e.g. set on delivery, pivoted to eat_in before save) will still charge a delivery fee. | Only apply `deliveryFeeFor()` when `order_type === 3`; clear `delivery_location_id` when order type is not delivery. |
| C-05 | DESIGN GAP | Medium | `charge()` `CashierController.php:388-429` | Cashier tickets create `orders.status=1` (kitchen) but **no KOT is auto-printed**; the chef must manually print. Waiter flow auto-prints the KOT via `kotOrderCode`. | Flash `kotOrderCode` after charge and open the KOT in the cashier browser (same one-time pattern as waiter). |
| C-06 | DESIGN IMPROVEMENT | Low | `charge()` `:390-393` | Order rows store `user_id = cashier id`; semantics are "placed by" not "customer". Fine for FK but ambiguous in reports. | Add/use `cashier_id` on orders (or document the convention). |
| C-07 | TECHNICAL DEBT | Medium | `Cart` shared table (`Cart.php:11`) | Five surfaces write the same `carts` table keyed by `user_id + orderCode`. Session-persistence (`sessionCartCode`) makes it work, but a cross-surface collision (same user, same code) would corrupt carts. | Enforce unique order-code schemes per surface (`CSR-`, `WTR-`, `ORD-`, `SES-`) and add a DB uniqueness/index on `orderCode`. |
| C-08 | CONFIRMED (verified safe) | – | `charge()` `:359-371` | Draft locked with `lockForUpdate`, double-payment guarded by `isPaid()`, empty-ticket guarded. | No action. |
| C-09 | CONFIRMED (verified safe) | – | `ownedOpenDraft()` `:461` | All draft mutations scoped to `cashier_id + branch_id`. | No action. |
| C-10 | UX IMPROVEMENT | Low | view payment section | Cash flow: `Confirm & Pay` is not disabled after the alert (button re-enabled in catch only). | Disable until inputs are valid; no functional risk (server recomputes). |
| C-11 | UX IMPROVEMENT | Low | `pos/index.blade.php:70-72` | "Dashboard" button links to `adminDashboard` (full admin dashboard) for a cashier. | Point cashier dashboard to a cashier-only summary, or hide the button for cashier. |

---

## 4. Multiple Draft / Active Ticket Findings

| ID | Type | Severity | Location | Finding | Recommendation |
|----|------|----------|----------|---------|----------------|
| MT-01 | CONFIRMED (verified safe) | – | `CashierController.php:37-52` | Tickets are scoped to `cashier_id + branch_id`; the "current ticket" must match the session or it is silently cleared. | No action. |
| MT-02 | DESIGN IMPROVEMENT | Low | `index()` `:57-64` | Suspended drafts reappear only via the Active Tickets strip; no search/filter, no per-cashier "my tickets" page, and no cap. Long shifts accumulate tickets. | Cap open tickets per cashier (e.g. 20) and add a date label. |
| MT-03 | UX IMPROVEMENT | Low | `pos/index.blade.php:16-59` | Discard is destructive and permanent (confirm only). Suspended tickets cannot be discarded from the strip without first resuming. | Allow discard directly on suspended cards (route already supports it). |
| MT-04 | CONFIRMED (verified safe) | – | `discardDraft()` `:176-194` | Discard archives the draft and deletes cart rows; never creates order/payment. | No action. |

---

## 5. Waiter Session Findings

| ID | Type | Severity | Location | Finding | Recommendation |
|----|------|----------|----------|---------|----------------|
| WS-01 | CONFIRMED BUG / DESIGN GAP | **Critical** | `WaiterController.php:1033-1060` | `requestBill` sets `bill_requested` and **nothing ever consumes it** — no cashier settlement screen, no close-session, no single bill. Sessions stay `bill_requested` forever and the "running bill" can never be settled. | Build a cashier "Running Bills / Sessions" screen that lists `bill_requested` sessions of the cashier's branch and records one settlement. |
| WS-02 | CONFIRMED BUG (financial) | **Critical** | `placeSessionOrder()` `:1003-1030` | Every session order group creates a `PaymentRecord` (status 1, paid = total) **immediately**, before any money is collected. A session with multiple rounds already reports multiple "paid" invoices. | Do not create `PaymentRecord` at placement. Create it only at settlement/charge. |
| WS-03 | CONFIRMED BUG (financial) | **Critical** | `placeOrder()` `:367-375` | Same premature-payment issue for single waiter orders. | Move `PaymentRecord` creation to a cashier charge step for waiter orders. |
| WS-04 | CONFIRMED BUG (billing) | High | `placeOrder()` `:299-305`, `placeSessionOrder()` `:960-964` | Waiter accepts `orderType = delivery` but has **no delivery location and no delivery fee**; totals are `subtotal + tax` only. The same delivery order is priced differently vs the cashier (subtotal + tax + delivery fee). | Either remove `delivery` from the waiter UI or add delivery-location selection + fee, and share one pricing routine. |
| WS-05 | CONFIRMED BUG (minor) | Medium | `updateCart()/removeCart()` `:256-295` | Cart mutation is ownership-guarded (good), but a waiter can edit a cart row he no longer "owns" via a different orderCode only if the code matches his session — verified safe. Keep as-is. | No action. |
| WS-06 | SECURITY RISK (inherited) | High | session key `sessionCartCode()` `:863` | The session cart code is stored in the **PHP session**, not tied to the DB. If a waiter is re-assigned or the session purges, the cart reference can survive/reuse across customers. | Store the active session per orderCode on `customer_sessions` instead of session state. |
| WS-07 | POTENTIAL BUG | Low | `sessionDetails()` `:838-843` | Session orders are fetched regardless of branch (session is already branch-verified; OK) but includes `status=3` lines in display while `subtotal()` excludes them. Display includes cancelled lines. | Filter out status 3 in the session detail view. |
| WS-08 | CONFIRMED (verified safe) | – | `verifiableSession()` `:780-794` | All `{sessionId}` routes verify `waiter_id + branch_id`; `requestBill` refuses empty/closed sessions. | No action. |

---

## 6. Payment & Billing Findings

| ID | Type | Severity | Location | Finding | Recommendation |
|----|------|----------|----------|---------|----------------|
| PB-01 | CONFIRMED BUG | **Critical** | `WaiterController.php:367, 373` and `:1003-1030` | `payment_records` created at kitchen-submission time = "paid" before cash-in. Financial records are wrong by design. See WS-02/WS-03. | Settlement-time-only payment creation. |
| PB-02 | CONFIRMED BUG (slip accuracy) | High | `OrderController.php:544` `getPaymentRecordData()` | The slip's discount join `leftJoin('discounts', 'products.id', '=', 'discounts.product_id')` has **no date window**. Expired/history discount rows alter `SUM(total_price)` and the printed slip, so the receipt can disagree with the amount actually charged. | Add the `start_date <= now <= end_date` condition inside the join (as `orderConfirm` does), and key by product+size. |
| PB-03 | POTENTIAL BUG (slip focus) | Low | `OrderController.php:565` | `subTotalAmt` is recomputed from live DB; for a printed slip after the fact it should use the stored charged amount. | Print from `payment_records.net_amount` and line totals persisted at charge; treat the DB recompute as display-only. |
| PB-04 | CONFIRMED BUG (low) | Low | `payment-slip.blade.php:65` | "Net Amount" shown as `paid_amount - change_amount` instead of `net_amount`. Coincidentally equal today because `paid >= total` for cash and `paid = total` otherwise. | Render `net_amount` directly. |
| PB-05 | DESIGN GAP | Medium | `payment_records` table | No unique key on `order_code`, no `branch_id`, no `payment_date` semantics; duplicates are technically possible. | Add a unique constraint per (order_code, payment event) and store `branch_id`. |
| PB-06 | CONFIRMED BUG (duplicate codes) | High | `Admin OrderController.php:156,291` + `UserDashboardController.php:270` | Order codes are generated from `uniqid()`/timestamp fragments; collision is unlikely but the **format is guessable** (`ORD-…`) and `paymentConfirm` resolves cart by code only → anyone with a code can finalize someone else's cart. | Prefix per surface and enforce `Cart.where('user_id', auth()->id())` in `paymentConfirm`. |
| PB-07 | CONFIRMED (verified safe) | – | `CashierController.php:407-415` | Cashier change/insufficient-cash is server-checked; client `totalAmount` is ignored. | No action. |

---

## 7. Security Findings (branch isolation, ownership, URL tampering)

| ID | Type | Severity | Location | Finding | Recommendation |
|----|------|----------|----------|---------|----------------|
| SEC-01 | SECURITY RISK | **Critical** | `AdminMiddleware.php:23` | The entire `admin/*` group (user creation, role change, products, discounts, tax, delivery fees, branches, suppliers/assets, reports, order accept/reject, booking POS, payment slips) is reachable by **admin, chef, cashier AND waiter**. | Replace with a strict `role == admin` (or an explicit roles list per route group). Do not fix the UI only — fix the middleware. |
| SEC-02 | SECURITY RISK | **Critical** | `OrderController.php:90-121` `updateOrder()` | Waiter/chef/cashier can POST accept/reject on **any order_code + product_id in any branch** (no branch/role check). A waiter could cancel another branch's orders. | Branch-scope and role-scope this route; ideally remove it in favour of chef status flow. |
| SEC-03 | SECURITY RISK | High | `generatePaymentSlip / paymentRecord / searchRecord / paymentSlip / printPaymentSlip` (`OrderController.php:458-514;517`) | Payment slips retrievable by guessable `order_code` with **no branch check** by any staff role. | Route through cashier/waiter scoped lookups (`branch_id`) and role-restrict. |
| SEC-04 | SECURITY RISK | Medium | `UserDashboardController.php:407-461` `paymentConfirm` | Cart resolved by `orderCode` only (no `user_id`); the confirm-er becomes the payment-record owner. Cross-user cart/payment tampering. | Scope to `carts.user_id = auth()->id()`. |
| SEC-05 | SECURITY RISK | Medium | `UserDashboardController.php:311-353` `updateCart/removeCart` | Update keyed by product_id+size and delete by cart id — ownership is assumed but not explicitly asserted for `updateCart` (user_id is in the update filter ✓, delete is not). | Include `user_id` in the delete filter. |
| SEC-06 | SECURITY RISK | Low | `Admin OrderController.php:334-341` `clearCart` | Deletes carts by `orderCode` only. | Add user/branch scope. |
| SEC-07 | CONFIRMED (verified safe) | – | Chef `verifiableOrder()`, cashier `ownedOpenDraft()`, waiter `verifiableOrder()/verifiableSession()` | New-own portals enforce branch+owner server-side. | No action. |
| SEC-08 | SECURITY RISK | Medium | `ChefMiddleware/WaiterMiddleware` `back()` | Non-qualified roles are silently bounced instead of 403; combined with SEC-01 the effective boundary is too porous. | Standardize `abort(403)`. |

---

## 8. UI/UX Findings

| ID | Type | Severity | Finding | Recommendation |
|----|------|----------|---------|----------------|
| UX-01 | UX IMPROVEMENT | Medium | No "all products" default in cashier POS (§C-02). | Add "All" chip. |
| UX-02 | UX IMPROVEMENT | Medium | Waiter delivery orders have no location/fee UI (§WS-04). | Add delivery location picker or hide delivery. |
| UX-03 | UX IMPROVEMENT | Low | Double print dialog (§C-03). | Keep one print trigger. |
| UX-04 | UX IMPROVEMENT | Medium | Cashier POS auto-redirects to index after charge; no "paid ticket" feedback/summary. | Flash success + order code; let the slip be the confirmation. |
| UX-05 | UX IMPROVEMENT | Low | Waiter dashboard counts distinct `order_code`; swaps show no KOT preview. | Minor. |
| UX-06 | UX ISSUE | Medium | Navbar "Orders / Invoice" links route staff (incl. waiter/chef) into admin order pages; badge `orderPending` is undefined on non-admin pages (guarded). | Gate on role and compute badge per role. |

---

## 9. Database Findings

| ID | Type | Severity | Finding |
|----|------|----------|---------|
| DB-01 | TECHNICAL DEBT | Low | `orders.status` is `char(1)` — the new code correctly uses `(int)` casts everywhere; legacy code does loose `!=`. Keep casting on all new comparisons. |
| DB-02 | TECHNICAL DEBT | Low | `orders.order_type` column is `varchar` (nullable in migration) while code writes ints `1/2/3`. Consistent today; collate to an int column when adapting. |
| DB-03 | TECHNICAL DEBT | Medium | `orders.batch/cart` has no index on `order_code`; `PaymentRecord.order_code` has no unique key (§PB-05). Add indexes/uniqueness. |
| DB-04 | DATABASE REQUIREMENT GAP | Medium | `payment_records` cannot isolate branches (no `branch_id`) and does not reference a `delivery_location_id`. Add columns for reporting. |
| DB-05 | DATABASE REQUIREMENT GAP | Medium | `cart` rows from 5 surfaces share one table; enforce prefix-style uniqueness via code + index. |
| DB-06 | CONFIRMED (schema OK) | – | `orders.waiter_id/branch_id/session_id/delivery_location_id` all nullable with FKs; `customer_sessions`/`cashier_drafts` are clean. |
| DB-07 | DATABASE REQUIREMENT GAP | Low | `cashier_drafts` has no `paid_amount/net_amount` snapshot; after charge the slip re-derives from live joins (§PB-02/03). Persist a charge snapshot. |

---

## 10. Performance Findings

| ID | Severity | Finding |
|----|----------|---------|
| PERF-01 | Medium | `CustomerSession::subtotal()/ordersCount()` load **all** orders rows then sum in PHP (`CustomerSession.php:63-79`); N rows per session detail. Aggregate in SQL. |
| PERF-02 | Low | Chef `dashboard()` runs 4+ distinct counts (`ChefController.php:69-79`); fine at store scale, batch into one query. |
| PERF-03 | Low | `getPaymentRecordData()` group-by joins per order-code render; acceptable, re-run at print. |
| PERF-04 | Low | All order listing screens group by `order_code` in PHP collections; fine for demo scale, revisit for growth. |

---

## 11. Regression Risks (Admin / Waiter / Chef / Customer)

| Surface | Risk | Notes |
|---------|------|-------|
| Admin legacy POS (`bookings/additems/orderConfirm`) | **High** | Any change to `carts`/`orderConfirm`/payment-slip affects the legacy admin flow. Currently produces `branch_id = NULL` orders invisible to the kitchen — do not "fix" without also routing them to a branch. |
| Admin reports + profile CRUD | High | SEC-01 means the fix must be **middleware**, not sidebar hiding; failing tests if staff lose today's URL-level access. |
| Waiter edit-pending-order | Medium | `recomputeOrderTotal()` mutates `PaymentRecord` on qty change (§WS-02/03). Once payments move to settlement, ensure edit recomputes only the *unpaid* record or the bill draft, not payments. |
| Chef status transitions | Medium | `startPreparing/markReady` rely on `(int)` casts and `verifiableOrder`; keep casts if `status` column stays `char(1)`. Do not change the status vocabulary. |
| Customer web flow | Medium | `paymentConfirm` hard-codes `order_type = 3`, pays "card", and never sets branch. Adding branch routing changes customer order visibility and KOT — coordinate with the chef screen. |
| KOT one-time print | Low | Keep `session('kotOrderCode')` flash-only; reprint must not duplicate (already safe). |

---

## 12. Recommended Improvements (prioritised)

### Must Fix (correctness of money & isolation)
1. **M1 — Settlement-time payments (WS-02/WS-03, PB-01).** Remove `PaymentRecord::create` from `placeOrder`/`placeSessionOrder`/`recomputeOrderTotal`; create it only when cash is actually collected (cashier charge flow). Highest impact; nothing else matters until records tell the truth.
2. **M2 — Strict role boundary (SEC-01).** `AdminMiddleware` must reject non-admin (or an explicit roles list) for the `/admin` group. Move financing-related admin routes behind `admin` only. Cashier/chef/waiter access must come from their own route groups.
3. **M3 — Branch-scope all order/payment lookups (SEC-02/SEC-03).** `updateOrder`, `generatePaymentSlip`, `paymentRecord`, `orderConfirm`, `getPaymentRecordData` must filter by `branch_id` (and owner for slips) so one branch can never read/modify another's money data.
4. **M4 — Complete the session lifecycle (WS-01).** A cashier "Running Bills" screen (branch-scoped) that lists `bill_requested` sessions, shows the running total, and records ONE settlement payment + `status = closed`. This is the missing half of the waiter running-bill feature.
5. **M5 — Kitchen visibility of web orders (exec §4).** `paymentConfirm` must set `branch_id` (from a branch selection / default branch) and `delivery_location_id`, so online orders appear on the chef screen as they already do for waiter/cashier.

### Should Fix (consistency & hardening)
6. **S1 — Delivery-fee consistency (WS-04, C-04).** One pricing helper (subtotal → tax → delivery-fee-if-delivery → round to 10) shared by cashier, waiter and admin; waiter delivery orders must carry a location and fee (or delivery is removed from the waiter UI).
7. **S2 — Payment-slip accuracy (PB-02/03/04).** Date-window the discounts join in `getPaymentRecordData`, print `net_amount`/`paid`/`change` from the stored record, and persist a charge snapshot (DB-07).
8. **S3 — Customer cart ownership (SEC-04/05).** Scope `paymentConfirm` and cart update/delete by `carts.user_id`.
9. **S4 — KOT for cashier (C-05).** Flash `kotOrderCode` after `charge` and open the KOT once in the cashier browser.
10. **S5 — Customer eligibility.** Guard `paymentConfirm` (and delivery) on `users.branch_id` presence; block orders when no branch is assigned.

### Nice to Have
11. **N1** — Cart-table uniqueness + `order_code` index (DB-03/05, C-07).
12. **N2** — SQL-side session totals (PERF-01).
13. **N3** — Cashier "All products" default, single print dialog, ticket cap (C-02/03, MT-02).
14. **N4** — `abort(403)` standardisation (SEC-08).
15. **N5** — Payment reference field for card/mobile (C-01).
16. **N6** — Session cart stored on the session row instead of PHP session (WS-06).

---

## 13. Minimal Implementation Plan

Ordered so that each step can land alone and be tested safely (`php artisan test --filter=…`).

| # | Change | File(s) | Reason | Depends on | Risk |
|---|--------|---------|--------|-----------|------|
| 1 | Drop `PaymentRecord` creation from waiter placement; add payment capture at a new cashier charge step for waiter/session bills | `WaiterController` (`placeOrder`, `placeSessionOrder`, `recomputeOrderTotal`); new cashier method | Money truth (M1) | – | Waiter screens lose the "paid" invoice until cashier charges; update waiter `orderDetails` to hide `PaymentRecord` for unpaid orders |
| 2 | Restrict `AdminMiddleware` to admin (keep cashier/chef/waiter out of `/admin`) | `AdminMiddleware` | Boundary (M2) | – | Existing staff that relied on admin URLs lose access; verify each portal still routes via its own group |
| 3 | Branch-scope order/payment lookups + remove accept/reject shorts | `Admin\OrderController` (`updateOrder`, `getPaymentRecordData`), views | Isolation (M3) | 2 | Ensure cashier slip flow (`generatePaymentSlip`) still works from cashier group — move a cashier-scoped copy into `CashierController` if needed |
| 4 | Customer orders: set `branch_id` + `delivery_location_id`; ownership scope | `UserDashboardController::paymentConfirm`, user menu views | Kitchen visibility (M5), cart ownership (S3) | – | Customer UX (choose branch or default) |
| 5 | Session settlement screen (cashier) | new cashier route/controller method/views; `CustomerSession` | Lifecycle (M4) | 1 | New UI on cashier portal |
| 6 | Delivery fee consistency (shared pricing + waiter location) | `WaiterController`, `CashierController`, small `PricingService` | Billing consistency (S1) | – | All three portals must round identically |
| 7 | Slip accuracy + snapshot | `Admin\OrderController`, `payment-slip.blade.php`, `payment_records` migration | Slip truth (S2) | – | Migrations must be additive (guard) |
| 8 | KOT for cashier tickets | `CashierController::charge`, cashier view JS | Kitchen UX (S4) | – | Keep one-time flash semantics |

Each item has its own test (transaction + rollback) before/after.

---

## 14. Proposed Final Cashier Workflow

1. Cashier logs in → `cashier.index` (branch-scoped). Active Tickets strip = own `active/suspended` drafts.
2. ` NEW ORDER` creates a draft `CSR-…` (order_type eat_in default).
3. Pick category → product grid → add items (qty ±, size, notes) to the **current ticket only** (server-validated).
4. Set order type (eat_in / take_away / delivery + location for delivery); each persists per ticket.
5. Hold / resume / discard tickets at will — no order or payment is ever created by these.
6. **Confirm & Pay**: server recomputes subtotal+tax+delivery(if delivery), rounds to 10; cash must cover total (change computed); card/mobile record a reference. Inside a transaction with a locked draft:
   - `orders` lines created → status 1 → appears in chef's branch queue.
   - `PaymentRecord` created **here** (only here) with `net/paid/change`.
   - KOT flashed once → printed; ticket status = `paid`; payment slip printed from the stored snapshot.
7. Optional: cashier "Running Bills" screen settles `bill_requested` waiter sessions (adds a settlement `PaymentRecord`, closes the session).

## 15. Proposed Final Waiter Workflow

1. Waiter dashboard: today/pending/completed counts (branch-scoped).
2. New Order → menu → cart (notes/sizes) → **place order** (orders only — NO payment record).
3. KOT auto-prints once; chef starts preparing/ready; waiter tracks on Current Orders.
4. Pending orders remain editable (qty/notes/add/remove, meta) while status == 1; recompute updates the **unpaid bill draft**, not a payment.
5. Running Bills: session open → add rounds (placeSessionOrder, orders only) → **request bill** (open → bill_requested, additions blocked).
6. Cashier (their branch) lists `bill_requested` sessions, totals the session, collects payment, records ONE `PaymentRecord`, closes the session; waiter sees `closed`.

## 16. Priority Roadmap

| Priority | Items | Why |
|----------|-------|-----|
| **P0** | M1 payments at settlement, M2 admin role boundary | Money records lie; every staff can reach admin ops. |
| **P1** | M3 branch-scoped order/payment lookup, M5 web-order kitchen visibility | Cross-branch money leakage; silent order loss. |
| **P2** | M4 session settlement screen, S1 delivery-fee consistency, S2 slip accuracy | Customer-facing correctness on the running-bill + delivery pricing. |
| **P3** | S3 cart ownership, S4 cashier KOT, S5 eligibility | Hardening. |
| **P4** | N1–N6 (indexes, SQL totals, UX, abort(403), payment ref, session cart) | Polish/debt. |

## 17. Final — STOP

This is an **audit-only** report. No code has been changed. Please review sections 12–16;
on approval, each P0 item will be scoped, tested in isolation
(`php artisan test --filter=…`, transaction + rollback only — never `php artisan test`) and
implemented minimally without rebuilding the existing POS/cart/order/payment engine.