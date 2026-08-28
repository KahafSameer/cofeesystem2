# Coffee Shop POS System — Project Overview

## 1. Introduction

This is a **Point of Sale (POS) System for a Coffee Shop** built with **Laravel 11 (PHP 8.2)**. It provides a complete café management solution with two separate interfaces:

1. **Admin / Staff Panel** (`/admin/*`) — used by the shop owner, **administrators**, **chefs**, **cashiers**, and **waiters** to manage the business.
2. **User / Customer Panel** (`/user/*`) — used by **registered customers** to browse the menu, place orders, track orders, leave reviews, and contact the shop.

The system is an open-source project originally created by **Join Coder** (www.youtube.com/@joincoder) and then customized. It was built as a learning project, so parts of the codebase are not fully "professional-grade" — this document describes it as it actually is.

### Feature Highlights

- Role-based authentication (admin, cashier, chef, waiter, user)
- Product & category management with multiple **sizes/prices** per product
- Discount management (per-product or apply-to-all)
- Tax configuration and delivery fees
- In-shop (eat-in), take-away, and delivery order types
- Cash, card, and mobile-payment order confirmation with printed payment slips
- Kitchen/chef order view (accept / reject orders) with order notes
- CRM: customer profiles, reviews, contact messages
- Supplier & purchase (ingredients) management
- Asset management (locations, categories, depreciation)
- Comprehensive reporting (sales, inventory, product analysis, supplier purchases, assets, feedback)
- Google / GitHub OAuth login (via Laravel Socialite)

> **Note:** The mobile payment option currently only displays a QR code — it is **not** connected to a real payment gateway.

---

## 2. Technology Stack

| Layer        | Technology                                          |
|--------------|-----------------------------------------------------|
| Backend      | Laravel 11 (`laravel/framework:^11.9`)              |
| Language     | PHP `^8.2`                                          |
| Frontend     | Blade templates, Bootstrap 5, vanilla JS, Alpine.js, Tailwind CSS, Font Awesome, SweetAlert2, Vite |
| Databases    | MySQL (configured) — `.env` defaults               |
| Auth/Packages| Laravel Breeze, Laravel Sanctum, Laravel Socialite (Google/GitHub) |
| Payments     | Stripe SDK installed (see "Notes" section for limitations) |
| Other        | Sweet Alert (`realrashid/sweet-alert`), `laravel/tinker`, `laravel/pint`, PHPUnit |

Key `composer.json` dependencies:

```
php ^8.2
laravel/framework ^11.9
laravel/sanctum ^4.0
laravel/socialite ^5.16
laravel/tinker ^2.9
realrashid/sweet-alert ^7.2
stripe/stripe-php ^17.1
```

**Frontend dev dependencies** (`package.json`): `vite`, `tailwindcss`, `alpinejs`, `axios`, `postcss`, `autoprefixer`, `@tailwindcss/forms`, `laravel-vite-plugin`.

---

## 3. Installation & Setup

### Prerequisites

- PHP **8.2.12** or above
- Composer **2.6** or above
- Node.js (for Vite frontend build tools)
- A web server with PHP + MySQL, e.g. **XAMPP** (Windows) or **MAMP** (Mac/Linux)
- A code editor (e.g. VS Code)

### Step-by-step

```bash
# 1. Install composer dependencies
composer install

# 2. Install frontend dependencies
npm install

# 3. Create the environment file
cp .env.example .env
```

On Windows PowerShell, copy manually: `Copy-Item .env.example .env`

### 4. Generate the application key

```bash
php artisan key:generate
```

### 5. Create the database

- Create a new database (e.g. `coffeepos`) in phpMyAdmin (or your DB tool)
- Open `.env` and set:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffeepos
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run migrations and seeders

```bash
php artisan migrate --seed
```

This creates all tables and seeds:
- A default **admin** user (`admin@gmail.com` / password from `DEFAULT_USER_PASSWORD` env, default `Password123`)
- Delivery fee locations (Yangon, Mandalay, Naypyidaw townships)

> Optional seeders that are NOT auto-called: `AssetCategorySeeder`, `AssetsTableSeeder`, `OrderSeeder`.
> To run all seeders including these: `php artisan db:seed --class=AssetCategorySeeder`, etc.

### 7. Start the app

```bash
php artisan serve        # http://127.0.0.1:8000
npm run dev              # Vite frontend (separate terminal)
```

### 8. Google / GitHub OAuth (optional)

Generate your own credentials for the OAuth provider(s) and set them in `.env`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

---

## 4. Default Login Credentials

| Role   | Email         | Password       |
|--------|---------------|----------------|
| Admin  | admin@gmail.com | Password123  |

Other accounts (cashier, chef, waiter, user) can be created by the admin from the **Admin Panel → Profile → Add User Account** section.

---

## 5. User Roles & Access Control

Roles are stored as plain strings in the `users.role` column. No separate roles table exists.

| Role     | Access                                    |
|----------|-------------------------------------------|
| `admin`  | Full admin panel access                   |
| `cashier`| Admin panel; sees the **Booking/POS** page in the sidebar |
| `chef`   | Admin panel; kitchen order workflow       |
| `waiter` | Admin panel (added for serving customers) |
| `user`   | Customer panel only (`/user/*`)           |

### Middleware

- **`AdminMiddleware`** (`app/Http/Middleware/AdminMiddleware.php`) guards the `/admin/*` routes. It allows roles: `admin`, `chef`, `cashier`, `waiter`. Blocks `userRegister` / `userLogin` for logged-in staff.
- **`UserMiddleware`** (`app/Http/Middleware/UserMiddleware.php`) guards `/user/*` routes. Only allows role `user` (403 otherwise).
- Middleware aliases are registered in `bootstrap/app.php`:
  ```php
  $middleware->alias([
      'admin' => AdminMiddleware::class,
      'user'  => UserMiddleware::class,
  ]);
  ```

### Login Redirect Logic

`app/Http/Controllers/Auth/AuthenticatedSessionController.php` redirects based on role:

```php
return match ($user->role) {
    'admin', 'chef', 'cashier', 'waiter' => to_route('adminDashboard'),
    'user'                               => to_route('userDashboard'),
    default                              => back()... // "Unauthorized role access!"
};
```

A user whose `status` is not `Active` is logged out with "Your account is inactive!".

---

## 6. Routes Overview

Route files are loaded from `routes/web.php`:

```php
require __DIR__.'/auth.php';
require_once __DIR__.'/admin.php';
require_once __DIR__.'/user.php';
```

### Admin Routes (`routes/admin.php`) — prefix `/admin`, middleware `admin`

| Prefix  | Purpose                                | Controller(s)            |
|---------|----------------------------------------|--------------------------|
| `/`     | Home dashboard (`adminDashboard`)      | `AdminDashboardController` |
| `/profile` | User accounts, profiles, roles      | `ProfileController`      |
| `/password` | Password change / reset            | `AuthController`         |
| `/category` | Category CRUD                      | `CategoryController`     |
| `/product`  | Product CRUD, sizes, discount     | `ProductController`      |
| `/order`    | Order list, booking/POS, payment  | `OrderController`        |
| `/discount` | Discount page / add              | `ProductController`      |
| `/tax`      | Tax settings                       | `AdminDashboardController` |
| `/delivery` | Delivery fee management           | `AdminDashboardController` |
| `/report`   | Sales / inventory / purchase / asset / feedback reports | `ReportController` |
| `/purchase` | Suppliers & purchases             | `PurchaseController`     |
| `/assetcategory` | Asset categories             | `AssetController`        |
| `/assets`   | Asset management                  | `AssetController`        |

### User Routes (`routes/user.php`) — prefix `/user`, middleware `user`

All handled by `UserDashboardController` under `/user/menu/*`:

| Route                | Method          | Purpose                 |
|----------------------|-----------------|-------------------------|
| `/home`              | `userDashboard` | User home               |
| `menu/customerProfile`   | `customerProfile` | View / edit profile |
| `menu/about`         | `about`         | About page              |
| `menu/climenu/{category_id?}` | `climenu` | Cafe menu with category filter |
| `menu/cart`          | `cartPage`      | Cart page               |
| `menu/addToCart/{id}`| `addToCart`     | Add item to cart        |
| `menu/updateCart`    | `updateCart`    | Update cart qty         |
| `menu/removeCart/{cartId}` | `removeCart` | Remove cart item   |
| `menu/paymentConfirm`| `paymentConfirm`| Confirm payment → order |
| `menu/reviewOrder`   | `reviewOrder`   | Order review/history    |
| `menu/reviewPage`    | `reviewPage`    | Leave a review          |
| `menu/addReview`     | `addReview`     | Save review             |
| `menu/contactus`     | `contactus`     | Contact form            |
| `menu/addContact`    | `addContact`    | Save contact message    |

### Auth Routes (`routes/auth.php`, `routes/web.php`)

- Breeze standard auth: register, login, logout, password reset, email verification
- `GET /` redirects to `/auth/login`
- Google / GitHub OAuth: `/auth/{provider}/redirect`, `/auth/{provider}/callback`

### API Routes (`routes/api.php`) — prefix `/api/admin`

- `GET /category/list` → returns category list as JSON (`API\RouteController@categoryList`)

---

## 7. Database Schema (all migrations)

The project uses **22 migrations**. Key tables and their columns:

### `users`
`id, name, email, email_verified_at, password, phone, address, profile, role (default 'user'), provider, provider_id, provider_token, status (enum Active/Inactive), rememberToken, timestamps`

Also: `password_reset_tokens`, `sessions`.

### `categories`
`id, name(100), timestamps`

### `products`
`id, name(100), qty, category_id (FK→categories), description (longText), image, timestamps`
> Note: `products` has **no price/size column** — those live in `product_sizes`.

### `product_sizes`
`id, product_id (FK→products), size (string), price (decimal 10,2), timestamps`
> Each product can have multiple size/price rows (e.g. Small, Medium, Large).

### `carts`
`id, product_id (FK), user_id (FK), qty (default 1), orderCode (string), size (enum Small/Medium/Large, default Medium), notes (text), timestamps`

### `orders`
`id, product_id (FK), user_id (FK), status (char 1), order_code, quantity, totalprice (decimal), payment_method, order_type, size (enum), notes (text), delivery_location_id (nullable), timestamps`

Status meaning: `1` = pending, `2` = accepted, `3` = rejected.

### `order_rejects`
`id, product_id, user_id, order_code, reason (longText), timestamps`

### `discounts`
`id, product_id (FK), discount_percentage (decimal 5,2), start_date, end_date, timestamps`

### `reviews`
`id, user_id (FK), name, rating, subject, timestamps`

### `user_contacts`
`id, user_id (FK), name, phone, inquiry_type (enum issue/feedback/other), message (text), timestamps`

### `payment_records`
`id, user_id (FK), status (char), order_code, net_amount, paid_amount, change_amount, payment_method, timestamps`

### `tax_settings`
`id, tax_name, tax_rate (decimal 10,2), timestamps`

### `suppliers`
`id, name, contact, address, status (enum Active/Inactive), timestamps`

### `purchases`
`id, supplier_id (FK), total_amount, paid_amount, due_amount, payment_status (enum Paid/Partial/Due), timestamps`

### `ingredients`
`id, name, cost_price, unit, timestamps`

### `purchase__items`
`id, purchase_id (FK), ingredient_id (FK), quantity, cost_price, total_price, timestamps`

### `delivery_fees`
`id, city, township, fees (decimal 8,2), timestamps`

### `asset_categories`
`id, name, timestamps`

### `assets`
`id, name, asset_category_id (FK), assigned_user_id (nullable FK→users), purchase_date, purchase_value, depreciation_rate, status (in_use/maintenance/disposed/missing), unit (kitchen/cashier/admin), warranty_expiry_date, serial_number, notes (text), timestamps`

### Framework tables
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens` (Sanctum).

---

## 8. Models (with key attributes)

All located in `app/Models/`:

| Model            | Notable `$fillable` / purpose                                  |
|------------------|---------------------------------------------------------------|
| `User`           | name, email, password, provider, role, status, phone, address, profile, provider_id/token. `casts: password = hashed` |
| `Product`        | name, qty, category_id, description, image                    |
| `ProductSize`    | product_id, size, price                                       |
| `Category`       | name                                                          |
| `Cart`           | user_id, product_id, qty, orderCode, size, notes              |
| `Order`          | user_id, product_id, order_code, quantity, totalprice, status, payment_method, order_type, size, notes, delivery_location_id |
| `OrderReject`    | product_id, user_id, order_code, reason                       |
| `Discount`       | product_id, discount_percentage, start_date, end_date         |
| `Review`         | user_id, name, rating, subject                                |
| `UserContact`    | user_id, name, phone, inquiry_type, message                  |
| `PaymentRecord`  | user_id, status, order_code, net_amount, paid_amount, change_amount, payment_method |
| `TaxSetting`     | tax_name, tax_rate                                            |
| `Supplier`       | name, contact, address, status                                |
| `Purchase`       | supplier_id, total_amount, paid_amount, due_amount, payment_status |
| `Ingredient`     | name, cost_price, unit                                        |
| `Purchase_Item`  | purchase_id, ingredient_id, quantity, cost_price, total_price |
| `DeliveryFees`   | city, township, fees                                          |
| `AssetCategory`  | name                                                          |
| `Asset`          | name, asset_category_id, assigned_user_id, purchase_date, purchase_value, depreciation_rate, status, unit, warranty_expiry_date, serial_number, notes |
| `Review`         | (listed above)                                                |

---

## 9. Controllers

### Admin Controllers (`app/Http/Controllers/Admin/`)

- **`AdminDashboardController`** — home `/admin/home`, tax page & add tax rate, delivery info page & add delivery fees.
- **`AuthController`** — password change / password reset pages.
- **`CategoryController`** — category list/create/store/edit/update/delete.
- **`ProductController`** — product list (with computed `available_stock`), create/store, edit/update, delete, size management (`prodsize`, `prodsizestore`), discount page & add discount.
- **`OrderController`** — order list, view order detail, update order status (accept/reject), booking/POS page, get products by category, add items to cart, store/generate order code, clear cart, get order codes, confirm order (payment), generate payment slip, print slip, payment record/search.
- **`ProfileController`** — profile detail/update/overview, create new user, add new user, change profile/role page, update field (role/status).
- **`PurchaseController`** — suppliers CRUD, purchases & add items, store purchase, remove item.
- **`ReportController`** — report overview, sales report, inventory, product analysis, supplier purchase, purchase details, asset report, feedback report.
- **`AssetController`** — asset categories CRUD, asset create/edit/delete/list.

### User Controller (`app/Http/Controllers/User/`)

- **`UserDashboardController`** — user home, profile edit/save address, about, customer profile, menu (`climenu`), cart page, add/update/remove cart, payment confirm, review order, reviews, contact.

### Other Controllers

- `app/Http/Controllers/AuthController.php` — login/register page rendering for staff.
- `app/Http/Controllers/ProviderController.php` — Google/GitHub OAuth redirect & callback.
- `app/Http/Controllers/Auth/*` — Breeze auth flow (login, register, password, email verification).
- `app/Http/Controllers/API/RouteController.php` — API category list.

> **Note:** The admin login flow uses the Breeze `AuthenticatedSessionController` (`/auth/login`) plus the staff `AuthController`. The `Role`-based redirect is in `AuthenticatedSessionController::store`.

---

## 10. Views (Blade Templates)

### Admin (`resources/views/admin/`)

- **`layouts/master.blade.php`** — main sidebar/navbar layout (Element roles shown conditionally; e.g. the **Booking** menu is shown only to `cashier`).
- **`home.blade.php`** — admin dashboard.
- **`category/`** — list, create, edit.
- **`product/`** — prodlist, prodcreate, prodedit, productsize (size & price management), discount.
- **`order/`** — booking (POS screen), orderlist, orderdetail (kitchen/chef view), payment-slip, payment-record.
- **`profile/`** — adminProfile, overview, createprofile (add user), changeprofile (role/status), tax, adddelivery, passwordchange, passwordreset.
- **`supplier/`** — supplierList, createSupplier, editSupplier, purchase.
- **`asset/`** — category, list, create, edit, partials/form.
- **`report/`** — salesreport, inventory, productAnalysis, supplierpurchase, detailpurchase, assetreport, feedback.

### User (`resources/views/user/`)

- **`layouts/master.blade.php`** — customer layout.
- **`home.blade.php`** — user dashboard/home.
- **`menu.blade.php`** — cafe menu with category filter, size selection, add-to-cart, **note modal**, and checkout.
- **`cart.blade.php`** — shopping cart.
- **`order.blade.php`** — order tracking/history (shows notes).
- **`review.blade.php`** — review submission.
- **`customerProfile.blade.php`** — profile editing.
- **`about.blade.php`**, **`contact.blade.php`** — info & contact.

---

## 11. How the Order Flow Works

### A. Admin / POS (Cashier) Flow

1. Cashier opens **Booking** page (`/admin/order/booking`).
2. Click **New Order** to generate an order code (or select an existing one from the "Order Codes" dropdown).
3. Browse products by category and click **+ / −** to set quantity, choose a **size**, optionally add a **note**, then click **Add to Cart**.
4. Cart items appear in the bill/ticket area on the right.
5. Select **order type** (Eat-in / Take Away / Delivery). For Delivery, choose a location (delivery fee added).
6. Choose **payment method** (Cash, Card, Mobile). For Cash, enter cash received → change due is computed.
7. Click **Confirm Payment** → order rows are created in `orders`, cart is cleared, and a printable **payment slip** is generated.

### B. User / Customer Flow

1. Customer logs in (`/auth/login`) as role `user`.
2. Browse the menu (`/user/menu/climenu`) with category filter.
3. Pick size, set quantity, optionally add a **note**, and click **Add to Cart**.
4. Review cart (`/user/menu/cart`) → adjust quantity / remove items.
5. Click **Payment Confirm** → `paymentConfirm` converts cart items to orders (stored with notes).
6. Track order in `reviewOrder` (user history shows notes).

### C. Chef / Kitchen Flow

1. Chef opens the admin order list (`/admin/order/list`).
2. Views order detail (`/admin/order/viewOrder/{orderCode}`) — shows items, sizes, **notes**, prices.
3. Accept or reject the order; cancellation reason stored in `order_rejects` when rejected.
4. When accepted, `status` becomes `2` (rejected = `3`).

---

## 12. Price, Tax & Discount Calculation

- Each product has **multiple sizes with individual prices** stored in `product_sizes`.
- **Discount** is applied per product (percentage) or to all products. Discounted price:
  ```
  discountPrice = price - (price * discount_percentage / 100)
  ```
- **Tax** is a flat percentage (from `tax_settings`) applied to the subtotal:
  ```
  taxAmount = ceil((subtotal * taxRate / 100) / smallestUnit) * smallestUnit   // smallestUnit = 10
  ```
- **Delivery fee** is added when order type is `delivery`.
- **Total** is rounded up to the nearest 10:
  ```
  total = ceil((subtotal + tax + deliveryFee) / 10) * 10
  ```

---

## 13. Notes Feature (recently fixed)

Order/product **notes** flow end-to-end:

1. Collected from the note modal on the menu / booking page into a hidden `notes` input.
2. Stored into the `carts.notes` column by `addToCart` (user) / `addItems` (admin).
3. When `paymentConfirm` (user) or `orderConfirm` (admin) converts carts → orders, the note is copied to `orders.notes`.
4. Displayed on:
   - Admin **order detail** (`resources/views/admin/order/orderdetail.blade.php`) as a warning badge.
   - User **order history** (`resources/views/user/order.blade.php`).

---

## 14. Known Recent Fixes / Bugs Resolved

The following were corrected during maintenance of this project:

1. **Product sizes not stored properly** — validation in `ProductController::prodsizestore` restricted sizes to `Small | Medium | Large`; the size form inputs were free-text. Addressed so sizes save correctly.
2. **Admin "Add to Cart" did nothing** (`booking.blade.php`) — fixed 4 issues:
   - Invalid extra parameter passed to the `additems` route.
   - Quantity defaulted to `0` (controller validation requires `min:1`).
   - Undefined `$existingCart` variable in the blade.
   - `orderCode` defaulted to `N/A` and was rejected by `additems`. Both `getProductsByCategory` and `addItems` now auto-generate a code when missing.
3. **User-side order notes dropped** — `UserDashboardController@paymentConfirm` did not copy `carts.notes` into the `Order::create()` call. Added `carts.notes` to the select/groupBy and `'notes' => $cart->notes` to the create array so notes now appear on the chef and user sides.
4. **New role added: Waiter** — added `waiter` role to the user-creation form, `AdminMiddleware`, login redirect, and the change-profile roles list so waiters can access the staff panel.

---

## 15. Folder / Code Structure

```
.
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Staff controllers
│   │   │   ├── User/           # Customer controllers
│   │   │   ├── Auth/           # Breeze authentication
│   │   │   ├── API/            # JSON API
│   │   │   └── ...             # Auth, Profile, Provider
│   │   ├── Middleware/         # AdminMiddleware, UserMiddleware
│   │   └── Requests/           # LoginRequest, ProfileUpdateRequest, StoreUserContactRequest
│   ├── Models/                 # Eloquent models
│   └── ...
├── bootstrap/                  # app.php (middleware alias registration)
├── config/                     # Laravel config
├── database/
│   ├── migrations/             # 22 table migrations
│   └── seeders/                # DatabaseSeeder, DeliveryLocationSeeder, etc.
├── public/                     # productImages, adminProfile, admin CSS
├── resources/
│   ├── views/
│   │   ├── admin/              # Staff Blade views
│   │   └── user/               # Customer Blade views
│   └── js/                     # app.js, bootstrap.js
├── routes/                     # web, admin, user, auth, api, console
├── storage/                    # logs, sessions, cache
├── tests/                      # PHPUnit tests
├── composer.json
├── package.json
└── vite.config.js
```

---

## 16. Environment Variables (`.env`)

Key settings:

```
APP_NAME=Laravel (change to your app name)
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffeepos
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

DEFAULT_USER_PASSWORD=Password123   # default admin password (seeded)

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=

STRIPE_KEY=
STRIPE_SECRET=
```

---

## 17. Commands Cheat Sheet

```bash
# Setup
composer install
npm install
php artisan key:generate
php artisan migrate --seed

# Run
php artisan serve
npm run dev

# Misc
php artisan migrate:fresh --seed   # reset DB
php artisan tinker
php artisan route:list             # view all routes
php artisan make:model/migration/controller/request ...
```

---

## 18. Troubleshooting & FAQ

**Q: Login page redirects / wrong role?**
Check the user's `role` in the `users` table and their `status` (must be `Active`).

**Q: Products don't show prices?**
Each product needs at least one row in `product_sizes`. Assign size & price on the product "Add Sizes & Prices" page.

**Q: "Add to Cart" does nothing?**
Ensure an order code exists (click **New Order**). The current code auto-generates one if missing.

**Q: Notes missing on chef side / user history?**
Notes are only carried to `orders` if the cart→order conversion includes them. The code now does this on both admin and user sides.

**Q: Need to add a new staff role?**
- Add an option to `createprofile.blade.php`.
- Add the role to `AdminMiddleware` (allowed roles) and to the login `match` in `AuthenticatedSessionController`.
- Add it to the `$roles` array in `ProfileController@changeProfilePage`.

---

## 19. Contribution & License

- This is an open-source project (MIT). You are free to use, modify, and improve it.
- Give credit to the original author (Join Coder) / repository if you find it useful.
- Contributions and pull requests are welcome.

---

## 20. Notes / Limitations

- The **mobile payment** option only shows a QR code for display — no real payment gateway integration.
- **Stripe SDK** is installed but not fully wired to a live payment flow.
- OAuth (Google/GitHub) requires valid API credentials in `.env`.
- Some controllers use manual `DB::table` joins instead of Eloquent relationships — a known simplification in this learning project.
