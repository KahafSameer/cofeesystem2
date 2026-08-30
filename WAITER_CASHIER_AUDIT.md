# Waiter + Cashier Portal — Functional & Technical Audit

**Scope:** `routes/waiter.php`, `routes/cashier.php`, both controllers, models (`CustomerSession`, `CashierDraft`, `Order`, `Cart`), all waiter/cashier blade views, middleware, relevant migrations, seeded users. **Read-only; no code was changed.**

## 1. Executive Summary

The waiter→session→request-bill→cashier→settle→print-slip loop is **functional and correctly branch-isolated**; it is the strongest part of the codebase (hard transactional guards, ownership checks everywhere). The cashier POS draft/ticket engine is also solid and concurrency-safe. The weakest areas are **domain-consistency gaps** (no payment path for standalone waiter orders; delivery orders from waiter sessions can't get a delivery location) and a **pricing desync** between what tickets display and what the server re-resolves at charge time on expired/future discounts. The previously reported "bill doesn't appear in Cashier Portal" bug is **fixed and verified** (see §11).

## 2. Feature Matrix

| Feature | Status | Where |
|---|---|---|
| Waiter standalone order (menu→cart→place→KOT) | ✅ Working | `WaiterController::newOrder/addToCart/cart/placeOrder` |
| Waiters current orders | 🟡 Partial (see §11 B1) | `currentOrders` (status 1 only) |
| Waiter order history | 🟡 Partial (statuses 2/3 only) | `orderHistory` |
| Waiter pending-order edit (qty/notes/add/remove/meta, steppers) | ✅ Working | `editOrder/updateOrderItem/removeOrderItem/addToOrder/updateOrderMeta` + `orders/edit.blade.php` |
| Customer Session (running bill) lifecycle | ✅ Working | `createSession→sessionNewOrder→placeSessionOrder→requestBill` |
| Request Bill (idempotent, transactional) | ✅ Working | `requestBill` + `POST waiter/sessions/{id}/bill` |
| Cashier POS tickets (new/suspend/resume/discard) | ✅ Working | `createDraft/suspendDraft/resumeDraft/discardDraft` |
| Cashier charge + payment slip print | ✅ Working | `charge` + `generatePaymentSlip` |
| Cashier running bills list + settle + print | ✅ Working | `sessions/sessionDetails/settleSession/sessionBill` |
| KOT auto-print once | ✅ Working | `session('kotOrderCode')` flash + `kitchen.kotPrint` |

## 3. Waiter Audit

- **Ownership enforced everywhere**: `verifiableOrder`/`verifiableSession` (waiter_id **and** branch_id, else 404) guard edit/add/remove/meta/place/session/requestBill. `updateCart`/`removeCart` check `cart->user_id`. ✅
- **`placeOrder`** (L298–376): date-window discount join, writes `waiter_id`+`branch_id`, status 1, deletes cart, no PaymentRecord (by design), flashes `kotOrderCode`. ✅
- **`requestBill`** (L1016–1067): `DB::transaction` + `lockForUpdate`, idempotent (`already`), blocks `open→bill_requested` only, rejects empty sessions, stops further items server-side. ✅
- **Gap — standalone orders never get paid.** A standalone `placeOrder` creates kitchen orders that are not in any session; the cashier can only settle *sessions*. Such orders live at status 1, vanish from "Current Orders" once a chef marks ✓ (status 5, not in `[1]` and not in history `[2,3]`), and can only reach status 2 via the *admin* order screen. Money is never recorded for them. Flag as design gap.
- **Gap — delivery orders from sessions have no delivery location** (`sessions/menu.blade.php` L56–68 has no delivery select; `placeSessionOrder` only validates orderType/paymentMethod). Session subtotal then omits delivery fee entirely (`CustomerSession::subtotal()`).

## 4. Cashier Audit

- **POS index** (L50–133): drafts strictly `cashier_id`+`branch_id`; current ticket resolved from session and validated against owned drafts (stale session auto-forgotten). ✅
- **`charge`** (L371–483): transactional, `lockForUpdate` on draft, guards `isPaid` (422) / discarded / empty; **authoritative totals recomputed server-side** (never trusts client `totalAmount`); one PaymentRecord; cart deleted; draft→`paid`. ✅
- **Pricing desync (real bug)**: `cartItems()` (L562–589, **used for the on-screen ticket**) joins `discounts` **without** a date window, while `charge` (`cartRowsForCharge` L527–557) and `draftSummaries` apply the date window. If a discount expires/starts while a ticket is open, the **displayed** price and the **charged** price differ (charge wins, so the slip can be higher than the screen showed).
- Same desync in the waiter app: `getCartItems` (L149–173, no window) vs `placeOrder`/`placeSessionOrder` (window). Pre-existing.

## 5. Bill Request Flow (Waiter → Cashier)

1. `waiter.sessions` → POST `createSession` → `SES-…` open session.
2. `sessionNewOrder` → menu + session cart (order code stored in session `waiterSessionCart_{id}`); `placeSessionOrder` writes orders with `session_id`.
3. `requestBill` flips to `bill_requested` (locking/guarded).
4. Cashier badge (`pendingBillCount`, L108–110) surfaces on POS "Running Bills" button → `cashier.sessions` (Awaiting Bill Settlement card).
5. `sessions/{id}` → settle form only when `bill_requested` → `settleSession`.
6. `sessions/{id}/bill` → printable slip (bill_requested **or** closed; open sessions blocked with redirect guard L818–823).

**Audit verdict: correct.** All state transitions gated, both directions ownership-checked.

## 6. Draft / Ticket Audit (Cashier POS)

- Lifecycle `active → suspended → active | discarded | paid`; cart rows keyed by `orderCode`; `CashierDraft::carts()` relation on `orderCode`. ✅
- **No FK from `carts` to drafts/orders** (string `orderCode` join). `charge` deletes `Cart::where('orderCode', …)` (not user-scoped), but code prefixes (`WTR-`/`CSR-`/`SES-`/`ORD-`/`SET-`) keep collisions improbable.
- NEW ORDER creates an **independent** ticket and switches current; previous active/current tickets stay in the strip (multi-ticket supported, not a leak). ✅
- Suspend/Discard are owner-scoped (`ownedOpenDraft`). ✅

## 7. Payment & Bill Audit

- **Waiter/standalone**: intentionally unrecorded (see §3 gap).
- **Cashier POS**: `PaymentRecord` per order code, `payment_method`, net/paid/change, status 1. Method mapping correct (cash uses received amount; card/mobile use total).
- **Session settle** (L718–803): exactly one `PaymentRecord` (`SET-<session_code>`), non-rejected orders→2, session→`closed`. Duplicate settlement prevented by `lockForUpdate` + `isBillRequested` re-check.
- **Consistency issue:** `payment_records.order_code` has **no unique index** — correctness depends on the code-level guards (acceptable, but fragile).
- `AdminMiddleware` allows cashier/waiter/chef under `/admin/*`, so the cashier's POS slip print (`POST admin/order/generatePaymentSlip`) works for the cashier role. ✅

## 8. Security / Branch Isolation

- Waiters: strictly own waiter + own branch everywhere. ✅
- Cashiers: `allowedSessionBranchIds()` = own branch; **admin = all branches** (deliberate, flagged in UI). POS drafts/charge remain own-branch (`branch_id = auth`). ✅
- `kitchen.kotPrint` is `auth`-only (any logged-in user with a code can fetch a KOT). Low severity.
- Card data never leaves the browser (no card fields sent to server). ✅
- Chef stays branch-scoped (`ChefController` L46/L209). ✅

## 9. Frontend / UX

| Area | Finding |
|---|---|
| Waiter UI | Cohesive dark-coffee theme (`waiter.css`, `new-order`, `orders/edit` restyled); steppers work; page-scoped CSS kept the admin portal untouched. ✅ |
| Cashier POS | Good: confirm button disabled on empty/insufficient cash, per-ticket persists orderType/delivery, totals incl. delivery fee, adult-level JS separation. 🟡 |
| POS product grid | No "All products" filter; categories are the only way to reveal products (`@if($selectedCategoryId)` L105). Minor onboarding friction. 🟡 |
| Change calc | Both settle + charge JS compute change from rendered `$total` — matches server after page reload on type change. ✅ |
| Billing | `bill.blade.php` is a self-printing slip (400px, `onload="window.print()"`). ✅ |

## 10. Database / Architecture

| Area | Finding |
|---|---|
| `orders.status` | integer 1/2/3/4/5; MySQL PDO returns strings — code consistently casts `(int)`/loose compare. ✅ |
| `customer_sessions` | `status` varchar(20), `opened_at/bill_requested_at/closed_at`; helpers `subtotal()/ordersCount()/settlementCode()/settlementRecord()`. ✅ |
| `cashier_drafts` | `order_code` unique; status + order_type/delivery_location_id. ✅ |
| `carts` | No FK to drafts/orders → keyed by orderCode. Acceptable risk noted. |
| Session totals | subtotal + tax only; delivery fee never persisted for sessions (never added at menu, no column). |
| Tests | `phpunit.xml` runs live MySQL; only safe transaction+rollback suites. |

## 11. Known Bugs / Regressions

| # | Severity | Bug | Location |
|---|---|---|---|
| F1 | ✅ Fixed | Bill hidden from cashier because only cashier was on branch 1 while sessions were branch 2 | `CashierController::allowedSessionBranchIds` (admin=all, cashier=own) + seeded `cashier2` (branch 2) + `pendingBillCount` badge — verified live, 6 tests |
| F2 | 🔴 | POS ticket shows wrong price when discount window changes mid-ticket (display vs charge desync) | `CashierController::cartItems` (L562) vs `cartRowsForCharge`; same in `WaiterController::getCartItems` |
| F3 | 🟡 | Standalone waiter orders can never be paid/settled → money unrecorded, order invisibility after chef marks READY | `currentOrders` (status 1) / `orderHistory` (2,3) |
| F4 | 🟡 | Waiter session delivery orders have no delivery-location input → fee omitted | `waiter/sessions/menu.blade.php` + `placeSessionOrder` |
| F5 | ⚠️ Latent | Route `paymentSlip` (GET) named in `admin.php` L85 has no controller method → 500 if hit | `Admin\OrderController` (methods end at L607) |
| F6 | ⚠️ Pre-existing | `Cart::where('orderCode')->delete()` not user-scoped in `charge` | L458 |
| F7 | ⚠️ Minor | `payment_records.order_code` not unique (relies on tx guards) | migration |
| F8 | ⚠️ Minor | `kitchen.kotPrint` not branch/role limited beyond `auth` | `web.php` L22–24 |
| F9 | ⚠️ Pre-existing | Discount join in `cartItems` display paths may show stale discounts | see F2 |

## 12. Missing Features (not necessarily requested)

- Cashier all-branch real-time ringing/notifications when a bill is requested (currently a badge + manual refresh).
- Reconcile/end-of-day report per cashier (POS ticket vs session settlements) — only one PaymentRecord engine, no daily X/Z.
- Waiter view of PREPARING/READY statuses (visibility ends at status 1).
- Delivery fee support in waiter sessions.
- Void/refund path for POS tickets after payment (drafts are archived, not reversible).

## 13. Testing Coverage

29 safe tests, all green across `CashierTicketFlowTest` (12), `CashierSessionSettlementTest` (11), `CashierSessionVisibilityTest` (6) — all transaction + `DB::rollBack()`, run individually with `--filter`. Nothing here touches the live DB.

## 14. Recommended Fix Priority

1. **F2** — align the display joins with the charge-time date-window logic (low effort, prevents pricing disputes).
2. **F4** — add delivery-location selection + fee to waiter session orders so session bills can be correct for delivery.
3. **F3** — decide the payment path for standalone waiter orders (route through POS or auto-associate with the cashier draft), then surface statuses 4/5 in waiter lists.
4. **F5** — either implement or delete the `paymentSlip` route.
5. Optionally add a unique/composite key on `payment_records.order_code` and scope KOT reprint to order owner/branch chef.