# Portal Analysis — Cashier POS & Waiter Panel

An end-to-end analysis of the two front-line portals already present in this
Laravel Coffee POS codebase: the **Cashier POS** (multi-ticket point of sale)
and the **Waiter Panel** (table service / running bills). It documents what
already exists: routes, controllers, models, views, business rules, security,
and how both feed the shared kitchen/order engine.

---

## 1. Overview

| | Cashier POS | Waiter Panel |
| --- | --- | --- |
| Role | `cashier` (admin also allowed) | `waiter` |
| Login redirect | `cashier.index` (`/cashier`) | `waiter.dashboard` |
| Route group | `routes/cashier.php` | `routes/waiter.php` |
| Controller | `app/Http/Controllers/Cashier/CashierController.php` | `app/Http/Controllers/Waiter/WaiterController.php` |
| Layout | `admin/layouts/master.blade.php` (admin shell) | `waiter/layouts/master.blade.php` (own shell) |
| Core idea | One cashier runs **many independent tickets** (per-customer carts) and finalizes each with payment | Waiter builds **one cart at a time**, places it to the kitchen, and can build a **running bill (session)** per table/customer |
| Data anchor | `CashierDraft` (ticket) + `carts` keyed by `order_code` | `carts` keyed by `order_code` / `CustomerSession` for running bills |
| Session key | `cashierOrderCode` | `waiterOrderCode` / `waiterSessionCart_{sessionId}` |
| Payment | Cashier charges → `Order` + `PaymentRecord` + payment slip | Waiter marks payment method but **does not settle** – sessions end with "Request Bill" |

Both portals are strict about **branch scoping** (everything is filtered by the
logged-in user's `branch_id`) and **ownership** (waiter/cashier can only touch
their own carts, orders or drafts). Both place orders into the shared kitchen
queue (see §2).

---

## 2. Shared foundation (what both portals reuse)

### Order status lifecycle (`orders.status`, integer)

```text
1 = NEW (submitted to kitchen)       <- both portals create orders here
2 = completed / served
3 = rejected / cancelled
4 = PREPARING (chef started)
5 = READY (chef finished)
```

- The cashier POS and waiter panel always create orders with `status = 1`.
- MySQL PDO returns integers as **strings**, so comparisons must use
  `(int)`/loose comparison (see `ChefController`, AGENTS.md).
- The chef portal (in this repo) consumes orders from **both** portals by
  branch: `newOrders` (1), `preparing` (4), `ready` (5), `history`
  (4,5,2,3).

### Order type mapping

```php
'eat_in'    => 1
'take_away' => 2
'delivery'  => 3
```

Stored as int on `orders.order_type` and mirrored on `cashier_drafts.order_type`.

### Pricing / tax / discount logic (shared by both portals)

- **Discounts** (`discounts` table): a product-level discount
  (`product_id` set) or a whole-menu discount (`product_id` NULL) whose
  `[start_date, end_date]` window covers "today". Applied price =
  `size.price - size.price * discount_percentage / 100`.
- **Unit price** is always resolved server-side from `product_sizes.price`
  joined on `products.id + carts.size` (or `orders.size`).
- **Tax** is read from the first `TaxSetting` row (`tax_rate`).
- **Rounding** (`computeRounded` in waiter, `roundUp` in cashier): amounts are
  rounded **up to the nearest 10**:
  `ceil($amount / 10) * 10`.
- Standard totals: `subTotal` → `taxAmount = roundUp(subTotal × rate/100)` →
  `total = roundUp(subTotal + taxAmount)` (cashier additionally adds a
  **delivery fee** from `delivery_fees`, see §3).

### PaymentRecord (one per finalized order group)

`order_code, user_id, net_amount, paid_amount, change_amount, payment_method, status`.
- Waiter: created at order placement with `paid_amount = net_amount`,
  `change_amount = 0`, `status = 1` (the money is assumed collected later;
  waiter does not enter cash received).
- Cashier: created at charge with real `paid_amount` + computed `change_amount`
  (see §3).

### KOT (Kitchen Order Ticket) auto-print

- After a successful placement (`waiter.placeOrder`,
  `waiter.placeSessionOrder`, `waiter.addToOrder`), the controller
  **flashes** `session('kotOrderCode')`.
- The waiter layout's footer JS opens
  `route('kitchen.kotPrint', $orderCode)` **once** (flash consumed = never
  re-prints on refresh).
- `ChefController::printKot` authorizes only the owning waiter or a chef of the
  order's branch, renders via `KitchenTicketService`, and shows **no payment
  info**. Manual reprint is available from chef pages and never duplicates.

---

## 3. Cashier POS

### 3.1 Access control

`app/Http/Middleware/CashierMiddleware.php` — allows `cashier` **and** `admin`
roles (so an admin can operate the till); every other role → `403`.
Registered as the `cashier` alias in `bootstrap/app.php` and applied to the
whole `routes/cashier.php` group together with `auth`.

### 3.2 Routes (`routes/cashier.php`, prefix `cashier`)

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| GET | `/` | `cashier.index` | POS main page |
| POST | `/new` | `cashier.new` | create a new ticket (draft) |
| POST | `/{orderCode}/suspend` | `cashier.suspend` | hold the ticket |
| POST | `/{orderCode}/resume` | `cashier.resume` | continue a held ticket |
| POST | `/{orderCode}/discard` | `cashier.discard` | archive & drop ticket cart |
| POST | `/cart/add` | `cashier.cart.add` | add item to current ticket |
| POST | `/cart/update` | `cashier.cart.update` | change qty/notes |
| POST | `/cart/remove` | `cashier.cart.remove` | remove a row |
| POST | `/order-type` | `cashier.orderType` | persist order type / delivery location |
| POST | `/{orderCode}/charge` | `cashier.charge` | finalize ticket + payment |

### 3.3 UI — `resources/views/cashier/pos/index.blade.php`

Extends the **admin** layout and loads `admin/CSS/booking.css`. Structure:

1. **Active Tickets strip** (top card)
   - Lists every unfinished draft of *this cashier + this branch*
     (`status IN active, suspended`, newest first).
   - Each card: ticket code, `N Items · PKR total` (server-computed per-ticket
     summary incl. delivery fee), and a badge:
     `Current` (session ticket) / `Suspended` (yellow) / `Active`.
   - Buttons: **Continue** (resume) + **Discard** (with confirm) on
     non-current cards; **Hold / Suspend** on the current card.
   - Header contains ** NEW ORDER** (creates a new ticket via AJAX).

2. **Category filter + product grid** (`col-lg-8`)
   - Category buttons as GET forms (`?categoryId=`).
   - Products only shown **after** a category is selected.
   - Cards mirror the admin booking page: image, name badge, price preview,
     **+ / − quantity** steppers, **size dropdown** (disables when a single
     size; price preview updates on change), note (pencil) button that opens
     the note modal, and **Add to Ticket**.

3. **Current ticket panel** (`col-lg-4`)
   - Ticket code header + items table: name (+ notes), qty
     (auto-submit on change), price, size initial, line amount, remove.
   - **Order type** `<select>` (items: `eat_in`, `take_away`, `delivery`)
     persisted per-ticket via AJAX; the ticket refreshes.
   - When order type = **delivery**, a **delivery location** `<select>`
     appears (from `delivery_fees`, shows township + fee) and a **Delivery Fee**
     line is added to the totals (fee from `draft.delivery_location_id`).
   - Totals: Items, Subtotal, Tax, (Delivery Fee), **Total** (all rounded up to 10).
   - **Payment section**: Cash / Card / Mobile buttons reveal the respective
     input panel; cash shows **Cash Received** + **Change Due**
     (client-side check + re-checked server-side). Card shows card number/expiry/CVV
     fields (display only). Mobile shows a QR placeholder.
   - **Hold / Suspend** and **Confirm & Pay** button (disabled when the cart is empty).

4. **Empty state**: "No Active Ticket" + a big ** NEW ORDER** button when no
   ticket is current.

5. **JS behaviours** (`@section('scripts')`)
   - `newOrder()` — POST `cashier.new`, reload.
   - `holdTicket(code)` — POST `cashier.suspend`, reload.
   - `+/-` steppers, size→price preview, and note modal wiring.
   - `persistOrderMeta()` — POST `cashier.orderType` with the selected
     order type + delivery location, then reload (keeps the ticket).
   - `showPaymentSection(method)` / `calculateChange()`.
   - Confirm & Pay (`ftp`) → POST `cashier.charge` → on success reuse the
     existing `generatePaymentSlip` (admin order engine) → open print window →
     return to `/cashier`.

### 3.4 Business flow (`CashierController`, ~620 lines)

**Ticket lifecycle**
- `createDraft` — unique code `CSR-YmdHis-XXXX`; creates a
  `CashierDraft` (`cashier_id`, `branch_id`, `label` = "Ticket XXXX",
  `status = active`, `order_type = 1`); sets `session('cashierOrderCode')`.
- `suspendDraft` — only `status active|suspended` drafts of this
  cashier+branch (`ownedOpenDraft`); sets `suspended`, clears the session
  pointer if it points at this ticket. Never creates order/payment.
- `resumeDraft` — sets `active` again, points the session at it, redirects to
  the POS.
- `discardDraft` — sets `discarded` (kept for history), **deletes the cart
  rows**, clears the session pointer. Never creates order/payment.
- `index` — loads the drafts strip, resolves the session ticket against the
  drafts (else forgets the key), computes per-ticket summaries and the current
  ticket's cart + totals, passes `orderType` (string) for the select.

**Cart**
- `addItem` — requires a current open draft (else redirects with a message).
  Same product+size+orderCode rows **merge quantity** (and keep first notes);
  size defaults to the product's first size / “Medium”. Touches the draft.
- `updateCart` / `removeCart` — scope rows by the **current draft's**
  `order_code` + cashier id (404 otherwise). `draft->touch()`.
- All actions derive the order code from the **session** (never from the
  client), so they always target the correct current ticket.

**Order type + delivery**
- `setOrderType` — validates `orderType in eat_in|take_away|delivery` and an
  optional `deliveryLocation` (FK to `delivery_fees`); stores `order_type`
  int + `delivery_location_id` on the draft.

**Charge (`/charge`)**
- Runs inside `DB::transaction` and takes `lockForUpdate()` on the draft so
  **parallel duplicate charges serialize**.
- Guards: ticket missing → 404; already `paid` → **422**; `discarded` or empty
  cart → **422**.
- Re-reads the authoritative cart (`cartRowsForCharge`, server-side discount
  resolution), rebuilds the pricing (subTotal → tax → delivery fee → total,
  all rounded), and **ignores the client's `totalAmount`**.
- Cash: validates `cashReceived ≥ total` (else 422), `changeDue = paid − total`;
  card/mobile: `paidAmount = total`.
- Creates one `Order` row **per cart line** with: `user_id = cashier`,
  `waiter_id = null`, `branch_id = auth branch`, `session_id = null`,
  `status = 1` (kitchen NEW), `payment_method`, `order_type` (int),
  `size`, `notes`, `delivery_location_id`.
- Creates one `PaymentRecord` (`status=1`) with server-side totals.
- Deletes the cart, marks the draft `paid`, clears the session pointer.

### 3.5 CashierDraft model (`app/Models/CashierDraft.php`)

- **Statuses**: `active`, `suspended`, `paid`, `discarded`.
- **Order types**: `1 = eat_in`, `2 = take_away`, `3 = delivery`
  (mirrors `orders.order_type`) with `ORDER_TYPE_STRING` map +
  `orderTypeString()`.
- **Casts**: `order_type`, `delivery_location_id` → integer.
- **Relations**: `cashier()` (User), `branch()`, `deliveryLocation()`
  (DeliveryFees), `carts()` (hasMany on `orderCode`).
- **Helpers**: `isOpen()` (active|suspended), `isSuspended()`,
  `isPaid()`, `isDiscarded()`.
- **Migrations**: the table already existed in the DB
  (`cashier_id`, `branch_id`, `order_code` unique, `label`, `status`,
  `order_type`); a guarded create-if-not-exists migration +
  `add_delivery_location_id_to_cashier_drafts` migration keep it reproducible.

### 3.6 Security & edge cases (cashier)

- Ownership via `ownedOpenDraft`: `cashier_id = auth id` **and**
  `branch_id = auth branch` **and** status open → anything else is a 404.
- Branch comes from `auth()->user()->branch_id` on **every** write.
- Row-locked charge prevents double payment; `paid` drafts are immutable.
- Client-provided `order_code`/`totalAmount` are never trusted.
- The 3 pre-existing `CSR-…` drafts in the DB (from an earlier session) are
  simply carried as normal active tickets.

---

## 4. Waiter Panel

### 4.1 Access control

`WaiterMiddleware` (`waiter` alias) protects the `routes/waiter.php` group
together with `auth`. The waiter only ever sees their **own** orders/sessions
within their assigned branch.

### 4.2 Routes (`routes/waiter.php`, prefix `waiter`)

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| GET | `/dashboard` | `waiter.dashboard` | stats dashboard |
| GET | `/order/new` | `waiter.newOrder` | menu + cart |
| POST | `/order/add` | `waiter.addToCart` | add to cart |
| GET | `/order/cart` | `waiter.cart` | review cart + place form |
| POST | `/order/cart/update` | `waiter.updateCart` | qty/notes |
| POST | `/order/cart/remove/{cartId}` | `waiter.removeCart` | remove row |
| POST | `/order/place` | `waiter.placeOrder` | place order |
| GET | `/order/current` | `waiter.currentOrders` | pending orders |
| GET | `/order/history` | `waiter.orderHistory` | completed/rejected |
| POST | `/order/item/update` | `waiter.updateOrderItem` | edit line qty/notes |
| POST | `/order/item/remove` | `waiter.removeOrderItem` | remove line |
| GET | `/order/{orderCode}/edit` | `waiter.editOrder` | edit screen |
| POST | `/order/{orderCode}/add` | `waiter.addToOrder` | add items to pending order |
| POST | `/order/{orderCode}/meta` | `waiter.updateOrderMeta` | order type + payment method |
| GET | `/order/{orderCode}` | `waiter.orderDetails` | order detail |
| GET | `/profile` | `waiter.profile` | profile (branch read-only) |
| GET | `/sessions` | `waiter.sessions` | running bills list |
| POST | `/sessions` | `waiter.createSession` | start session |
| GET | `/sessions/{session}/menu` | `waiter.sessionNewOrder` | session menu + cart |
| POST | `/sessions/{session}/orders` | `waiter.placeSessionOrder` | push cart into session |
| POST | `/sessions/{session}/bill` | `waiter.requestBill` | request the bill |
| GET | `/sessions/{session}` | `waiter.sessionDetails` | session bill view |

### 4.3 Layout — `waiter/layouts/master.blade.php`

- Own shell (bootstrap 5 + FontAwesome + SweetAlert2 + `admin/CSS/style.css`).
- **Sidebar**: Dashboard, New Order, Current Orders, Running Bills, Order
  History, Profile, Logout.
- **Navbar**: brand, **Cart** link with a red badge (respects a `$cartCount`
  var), profile dropdown (avatar, role, profile/logout).
- Footnotes: flashes SweetAlert from `session('alert')` (`success`/`error`),
  and the **KOT auto-print** script (§2).

### 4.4 Dashboard (`waiter.dashboard`)

Welcome card with branch name; four stat cards (distinct `order_code` counts,
all waiter+branch scoped): **Today's Orders**, **Pending Orders**
(status = 1), **Completed Orders** (status = 2), **Total Orders**.

### 4.5 New Order menu (`waiter.newOrder`)

- Generates a persistent **`waiterOrderCode`** (`WTR-YmdHis-XXXX`) stored in
  session if none exists.
- Two-column layout: left **Cart summary** (items, size, qty, line total;
  Subtotal / Tax / Total; "Review" button); right **menu**:
  - **Search** (`?searchKey=`) and **category filter** (`?categoryId=`).
  - Product cards with image, sizes and discounted prices (original shown
    struck-through when a discount applies), a **size select**, quantity input,
    optional **notes** field, and an **Add** button (`waiter.addToCart`).

### 4.6 Cart review + place order (`waiter.cart`, `waiter.placeOrder`)

- Review page: editable qty, editable notes (save), remove row; Subtotal/Tax/Total.
- **Place Order** form asks for **Order Type** (Eat In / Take Away / Delivery)
  and **Payment Method** (Cash / Card / Mobile), posts the hidden `orderCode`
  and `totalAmount`.
- `placeOrder`:
  - Re-reads the cart with the same discount-join; empty cart → redirect.
  - Maps order type to int, creates `Order` rows (`status = 1`,
    `waiter_id = waiter id`, `branch_id = branch`, `payment_method`, `size`,
    `notes`), deletes the cart, clears `waiterOrderCode`.
  - Computes tax + total server-side, creates `PaymentRecord`
    (`paid_amount = net_amount`, `change = 0`, `status = 1`).
  - Flashes `kotOrderCode` for the one-time KOT print and redirects to
    `waiter.currentOrders` with a success alert.

### 4.7 Current orders + editing a pending order

- `currentOrders` lists the waiter's `status = 1` groups (per `order_code`)
  with items, status badge, **Edit** + **View** actions.
- `editOrder` (only while `status == 1`, else bounce):
  - Left: order items table with **qty update**, **notes update**, **remove**;
    totals; an **Order Details** form to change **Order Type** + **Payment
    Method** (`updateOrderMeta` updates all rows + the PaymentRecord).
  - Right: "Add More Items" menu (search + categories + product cards) posting
    to `addToOrder`.
- `updateOrderItem` — validates ownership via `verifiableOrder`
  (waiter+branch+`status==1`), saves qty/notes, then **`recomputeOrderTotal`**
  re-calculates and updates the single PaymentRecord.
- `removeOrderItem` — if the order group becomes empty, sets `status = 3`
  (rejected/cancelled) and redirects to current orders.
- `addToOrder` — resolves discounted unit price server-side; if the same
  product+size already exists in the order it **merges quantity + notes**,
  otherwise inserts a new `Order` row (`status = 1`); recalculates totals and
  flashes `kotOrderCode` (kitchen gets an updated KOT).

### 4.8 Order history & details

- `orderHistory`: groups with `status IN (2, 3)` → Completed / Rejected badges.
- `orderDetails`: header (date, branch, status, order type, payment method),
  items table (incl. notes), and the PaymentRecord summary (net / paid / change)
  when present. Ownership 404s apply.

### 4.9 Running bills / Customer Sessions

- `sessions` — lists this waiter's sessions with
  `status IN (open, bill_requested)` (Open / Bill Requested badge), orders
  count, opened time, and **View Bill**. **Start New Session** button.
- `createSession` — session code `SES-yymd-XXXX`, `status = open`,
  `opened_at = now()`, waiter + branch from auth.
- `sessionNewOrder` — menu identical to new order, but the cart is anchored to
  a **per-session order code** (`waiterSessionCart_{sessionId}` in session,
  e.g. `SES-{sessionId}-…`). Refuses if session is not open.
- `placeSessionOrder` — like `placeOrder` but attaches `session_id`, clears the
  per-session cart key, flashes KOT, redirects to the session bill.
- `sessionDetails` — session status bar + **running total** (Subtotal/Tax/Total,
  server-side; subtotal = Σ `totalprice × quantity` excluding rejected rows);
  **Add More Items** button while open; a **Request Bill** button when open and
  non-empty; otherwise a warning that no more items can be added.
- `requestBill` — only when open and at least one order group exists;
  sets `status = bill_requested`, records `bill_requested_at`.
- Model helpers: `CustomerSession::subtotal()`, `ordersCount()`,
  `isOpen/isBillRequested/isClosed`.

### 4.10 Profile

`waiter.profile` — read-only name/email/role/branch display.

---

## 5. How the two portals interoperate

- **Shared kitchen queue**: both create `orders` with `status = 1`, so a chef
  of the branch sees cashier orders and waiter orders side-by-side in
  `chef.orders.new`. KOT printing works for both.
- **Payment ownership differs**:
  - Waiter “Running Bill” sessions are **not** paid for by the waiter —
    creating orders records the method (Cash/Card/Mobile) and a `PaymentRecord`,
    but settlement/change-calc belongs to the cashier/back office.
  - Cashier tickets are **fully settled at the till** with change calculation
    and an immediate payment slip print.
- **Delivery fee asymmetry**: the cashier adds a delivery fee when
  `order_type = delivery` (from `delivery_fees` + the ticket's
  `delivery_location_id`); the waiter flow records `order_type = 3` but has no
  delivery-location/fee handling.

---

## 6. Security & correctness notes (already implemented)

- Every read/write in both controllers is scoped by
  `auth()->user()->branch_id`; ownership checks return **404** (waiter/cashier
  editing someone else's data) and **403** (cross-role access via middleware).
- Cashier charge uses a row lock (`lockForUpdate`) inside a transaction;
  `paid` drafts are immutable and duplicates return **422**.
- Waiter cart/order edits are guarded by `verifiableOrder` /
  `verifiableSession` (waiter + branch + still pending/open).
- Prices, discounts and totals are always recomputed server-side; client
  totals are not trusted (`totalAmount` is recorded but ignored by the
  cashier's authoritative pricing).
- Tests: `tests/Feature/CashierTicketFlowTest.php` covers the cashier A–N
  tiket scenarios (transaction + rollback → safe on the live DB);
  `ChefPortalKotTest.php` covers the waiter→kitchen→KOT round trip.

---

## 7. File map

```
Cashier POS
  routes/cashier.php
  app/Http/Controllers/Cashier/CashierController.php
  app/Models/CashierDraft.php
  app/Http/Middleware/CashierMiddleware.php
  resources/views/cashier/pos/index.blade.php
  database/migrations/2026_08_29_090000_create_cashier_drafts_table.php
  database/migrations/2026_08_29_090001_add_delivery_location_id_to_cashier_drafts_table.php

Waiter Panel
  routes/waiter.php
  app/Http/Controllers/Waiter/WaiterController.php
  app/Http/Middleware/WaiterMiddleware.php
  app/Models/CustomerSession.php
  resources/views/waiter/layouts/master.blade.php
  resources/views/waiter/{dashboard,new-order,cart,profile}.blade.php
  resources/views/waiter/orders/{index,edit,show,history}.blade.php
  resources/views/waiter/sessions/{index,show,menu}.blade.php

Shared
  app/Http/Controllers/Chef/ChefController.php        (status flow + KOT print)
  app/Services/KitchenTicketService.php               (KOT payload)
  app/Http/Controllers/Auth/AuthenticatedSessionController.php (role redirects)
  bootstrap/app.php                                   (middleware aliases)
  tests/Feature/CashierTicketFlowTest.php
  tests/Feature/ChefPortalKotTest.php
```