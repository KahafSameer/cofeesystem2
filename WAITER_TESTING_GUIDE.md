# Waiter Panel — Testing Guide

This guide explains how to test the newly added **Waiter Panel** in the Laravel Coffee
Shop POS, and where each piece is implemented.

---

## What the feature does

- A **waiter** (a staff user with role `waiter`) has their own panel.
- A waiter logs into the existing staff login and is automatically sent to the
  **waiter dashboard**.
- The waiter can browse the **existing menu**, build a **cart**, and **place an order**.
- Every waiter order is automatically tagged with:
  - `waiter_id` = the authenticated waiter
  - `branch_id` = the authenticated waiter's branch
- The waiter can only see **their own** orders, within **their own branch**.
- The waiter's **branch and role cannot be changed** from their own profile.

---

## Where it's implemented (file map)

### 1. Database
| File | Purpose |
|------|---------|
| `database/migrations/2026_08_29_012949_add_waiter_fields_to_orders_table.php` | Adds nullable `waiter_id` (→ users) and `branch_id` (→ branches) to `orders`. Both `nullOnDelete`. |

> Verify with: `php artisan migrate:status` (should show it as ran)

### 2. Backend logic
| File | Purpose |
|------|---------|
| `app/Http/Middleware/WaiterMiddleware.php` | Rejects access unless the logged-in user role is `waiter`. |
| `bootstrap/app.php` | Registers the `waiter` middleware alias. |
| `app/Http/Controllers/Waiter/WaiterController.php` | Dashboard, menu, cart, place order, current orders, order details, history, profile. Derives `waiter_id` + `branch_id` from the authenticated user. |
| `app/Models/Order.php` | Added `waiter_id`, `branch_id` to fillable; `waiter()`, `branch()`, `product()`, `user()` relations. |
| `app/Models/User.php` | Added `waiterOrders()` relation. |
| `app/Models/Branch.php` | Added `orders()` relation. |

### 3. Routes
`routes/waiter.php` (loaded from `routes/web.php`) — all under `['auth', 'waiter']`:

| Route | Name | Description |
|-------|------|-------------|
| `GET  waiter/dashboard` | `waiter.dashboard` | Waiter dashboard |
| `GET  waiter/order/new` | `waiter.newOrder` | Menu / product selection |
| `POST waiter/order/add` | `waiter.addToCart` | Add item to cart |
| `GET  waiter/order/cart` | `waiter.cart` | Review cart |
| `POST waiter/order/cart/update` | `waiter.updateCart` | Change qty / notes |
| `POST waiter/order/cart/remove/{cartId}` | `waiter.removeCart` | Remove item |
| `POST waiter/order/place` | `waiter.placeOrder` | Place order |
| `GET  waiter/order/current` | `waiter.currentOrders` | Current (pending) orders |
| `GET  waiter/order/{orderCode}` | `waiter.orderDetails` | Order details |
| `GET  waiter/order/history` | `waiter.orderHistory` | Past orders |
| `GET  waiter/profile` | `waiter.profile` | Profile (branch read-only) |

### 4. Auth redirect
| File | Change |
|------|--------|
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | `waiter` role → `waiter.dashboard` (others unchanged) |
| `app/Http/Controllers/ProviderController.php` | Social login also sends `waiter` → `waiter.dashboard` |

### 5. Views
| File | Purpose |
|------|---------|
| `resources/views/waiter/layouts/master.blade.php` | Waiter layout + waiter-only sidebar (Dashboard, New Order, Current Orders, Order History, Profile, Logout) + Cart link in navbar |
| `resources/views/waiter/dashboard.blade.php` | Welcome + branch + order counts |
| `resources/views/waiter/new-order.blade.php` | Menu (categories, search, add-to-cart) + live cart summary |
| `resources/views/waiter/cart.blade.php` | Cart review + place-order form |
| `resources/views/waiter/orders/index.blade.php` | Current (pending) orders |
| `resources/views/waiter/orders/show.blade.php` | Order details |
| `resources/views/waiter/orders/history.blade.php` | Order history |
| `resources/views/waiter/profile.blade.php` | Profile with read-only branch |

---

## Pre-requisites

1. App running: `php artisan serve`
2. Migrations applied: `php artisan migrate`
3. You need **at least two waiter accounts** belonging to **two different branches**
   to fully test branch/order isolation.

### How to create waiter accounts (as admin)
1. Log in as **admin**.
2. Go to **Manage Profile → Create New User**.
3. Create one user with role **Waiter** assigned to **Branch 1**.
4. Create another user with role **Waiter** assigned to **Branch 2**.
   - The Branch dropdown appears automatically for the waiter role.

---

## Test Plan

### A. Authentication & role redirect
Log in with each role and confirm the landing page:

1. **Waiter** → lands on the **Waiter Dashboard** (`waiter/dashboard`).
   - Sidebar shows: Dashboard, New Order, Current Orders, Order History, Profile, Logout.
   - Sidebar does **NOT** show admin settings/purchase/asset/reports items.
2. **Admin** → lands on Admin dashboard (`admin/home`). *(unchanged)*
3. **Cashier** → lands on Admin dashboard. *(unchanged)*
4. **Chef** → lands on Admin dashboard. *(unchanged)*
5. **Customer (user)** → lands on User dashboard. *(unchanged)*

> Result: waiter gets the waiter panel; all other roles go to their usual place.

### B. Waiter Dashboard
1. On the waiter dashboard confirm it shows:
   - `Welcome, {Name}`
   - `Branch: {Branch Name}` (from the waiter's assigned branch)
   - Today's Orders, Pending Orders, Completed Orders, Total Orders counts.
2. If the waiter has no branch assigned, a red "No branch assigned" warning should show.

### C. New Order (menu)
1. Click **New Order** in the sidebar (or go to `waiter/order/new`).
2. **Expected:**
   - Product cards are displayed (with image, name, sizes, prices, discounts).
   - Category filter buttons (All + each category) at the top.
   - A search box filters products by name.
   - The right panel shows the current cart summary (empty to start).
3. Click a category to filter; click **All** to reset.
4. Search for a product by name.

### D. Add to Cart
1. On a product card, select a **size** (if the product has multiple sizes).
2. Set an **quantity** (default 1).
3. Optionally type a **note** (e.g. "no ice").
4. Click **Add**.
5. **Expected:** success alert "Item added to cart." and the cart summary count/rows update.

**Validation checks** (try to break it):
- Add **0** or a negative quantity → should be rejected (min 1).
- Add the same product+size twice → quantity should combine/increment, not duplicate rows.

### E. Review Cart & Place Order
1. Click **Review** (or go to `waiter/order/cart`).
2. The cart page shows each item with Product, Size, Qty, Price, Total, Notes, and a remove button.
3. **Change quantity:** update the qty box and click OK → total updates.
4. **Change notes:** edit the notes box and click Save.
5. **Remove item:** click the trash icon.
6. Choose an **Order Type** (Eat In / Take Away / Delivery) and **Payment Method** (Cash / Card / Mobile).
7. Click **Place Order**.
8. **Expected:** success alert "Order #[code] placed successfully." and you're taken to Current Orders.

### F. Verify order data in the database
Run (in your DB client):
```sql
SELECT order_code, user_id, waiter_id, branch_id, status,
       order_type, payment_method FROM orders ORDER BY id DESC LIMIT 5;
```
- The just-placed order must have:
  - `waiter_id` = the logged-in waiter's user id
  - `branch_id` = the logged-in waiter's branch id
  - `status = 1` (pending)
  - `user_id` = the waiter's id (matching the existing POS/cashier pattern)
- Confirm a `PaymentRecord` row was created for the same `order_code`.

### G. Current Orders
1. Go to **Current Orders** (`waiter/order/current`).
2. **Expected:** the just-placed order appears with its items and a **Pending** badge.
3. Click **View** → order details page:
   - Order #, Date, Branch, Status, Order Type, Payment Method
   - Items table (Item, Size, Qty, Price, Notes)
   - Paid / Change amounts (if a payment record exists)

### H. Order History
1. Have the **chef** mark the order as **Accepted** (in the admin/chef order list, status 2) or **Rejected** (status 3).
2. Go to **Order History** (`waiter/order/history`).
3. **Expected:** the order now shows there with a **Completed** or **Rejected** badge.
4. It should no longer appear in **Current Orders**.

### I. Order status display
Statuses in the waiter panel map to the existing system:
- `1` = Pending (yellow badge)
- `2` = Completed / Accepted (green badge)
- `3` = Rejected (red badge)

### J. Security — branch & order isolation (IMPORTANT)
1. Log in as **Waiter 1** (Branch 1) and place an order. Note the order code.
2. Log out and log in as **Waiter 2** (Branch 2).
3. **Waiter 2** must:
   - NOT see Waiter 1's order in Current Orders or Order History.
4. Manually open Waiter 1's order via the URL:
   ```
   waiter/order/<waiter-1-order-code>
   ```
   - **Expected:** a **404** (not the order). The details route enforces both `waiter_id` and `branch_id`.
5. Also try a non-existent order code → 404.

**Cross-role access test:**
- Log in as **admin** and open `waiter/dashboard`.
  - **Expected:** redirected away / denied (waiter middleware rejects non-waiter roles).
- Log in as **customer** and open `waiter/dashboard`.
  - **Expected:** denied.

### K. Waiter Profile
1. Go to **Profile** (`waiter/profile`).
2. **Expected:** shows Name, Email, Phone, Role, Branch.
3. The **Branch** and **Role** are **read-only** — there is no form/select to change them.
4. Confirm there is no way for the waiter to edit `role` or `branch_id` from this page.

### L. Cart isolation (bonus)
Because the cart is keyed by `user_id`, each waiter's cart is separate:
1. Waiter 1 adds items to the cart (do NOT place).
2. Log out, log in as Waiter 2 → their cart should be empty (different user).

---

## Existing functionality regression (must still work)

Verify the following still behave as before after the waiter changes:
- [ ] **Admin** login → admin dashboard, branch management, create/edit users, settings, reports
- [ ] **Cashier** login → admin dashboard, Booking/POS screen, confirm orders, payment slips
- [ ] **Chef** login → admin dashboard, orders list, accept/reject orders
- [ ] **Customer** login → customer dashboard, menu, cart, place order
- [ ] **Customer order** still has `waiter_id = NULL` (checked in DB)
- [ ] **Branch Management** add/edit/activate/deactivate still works
- [ ] Logout works for all roles

---

## Quick checklist

**Waiter panel**
- [ ] Waiter login lands on waiter dashboard (not admin)
- [ ] Waiter sidebar shows only waiter items (Dashboard, New Order, Current Orders, Order History, Profile, Logout)
- [ ] Dashboard shows welcome + branch name + order counts
- [ ] New Order shows menu, categories, search, sizes, discounts
- [ ] Add to cart works; dup size/product combines qty
- [ ] Cart review: change qty, save notes, remove item
- [ ] Place order succeeds with success alert
- [ ] Order saved with correct `waiter_id` + `branch_id` (DB check)
- [ ] Current Orders lists only the waiter's pending orders
- [ ] Order details page shows all required fields
- [ ] Order History shows accepted/rejected orders
- [ ] Profile shows branch as read-only
- [ ] Waiter 2 cannot see Waiter 1's orders
- [ ] Manual URL `waiter/order/<other-waiter-code>` returns 404
- [ ] Non-waiter cannot access `waiter/*` routes

**Regression**
- [ ] Admin / cashier / chef / customer all log into correct panels
- [ ] Customer orders still have `waiter_id = NULL`
- [ ] POS, chef accept/reject, branch management all work
