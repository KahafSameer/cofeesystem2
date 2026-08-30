# AGENTS.md

## Critical: Never run the full test suite against the live database

- `phpunit.xml` has the SQLite in-memory DB commented out, so `php artisan test`
  runs against the **real MySQL `coffeepos` database**.
- The pre-existing Breeze test `tests/Feature/Auth/RegistrationTest` uses
  `RefreshDatabase` (migrate:fresh -> drops/truncates all tables). Running the
  full suite will **wipe all business data** (users, branches, categories,
  products, orders, etc.). This has happened and data was lost.
- **Safe testing:** only run `php artisan test --filter=<YourTest>` for a test
  that uses a transaction + `DB::rollBack()` in setUp/tearDown (e.g.
  `ChefPortalKotTest.php`). Do NOT run `php artisan test` (full suite).

## Chef / Kitchen Portal + KOT summary

- `orders.status` is an integer field:
  - `1` = NEW (submitted to kitchen), `2` = completed/served, `3` = rejected
  - `4` = PREPARING (chef started), `5` = READY (chef finished)
- Chef role is branch-scoped: `order.branch_id == user.branch_id` (enforced in
  the controller). Chefs only see/modify their own branch's orders and cannot
  touch prices/quantity/session/payment.
- KOT printing is one-time: `session('kotOrderCode')` is flashed only after a
  successful order submission; the waiter layout auto-opens
  `route('kitchen.kotPrint', ...)` once. Re-print is manual and never creates
  duplicates.
- MySQL PDO returns integer columns as **strings** (no casts on `orders.status`),
  so always compare with `(int)`/loose comparison, e.g.
  `(int) $order->status !== self::STATUS_NEW`. Strict `!==` against an int
  constant will never match and silently break transitions.
- Chef redirects on login are handled in `AuthenticatedSessionController::store`
  and `ProviderController` (`chef -> chef.dashboard`).

## Restoring demo data after a wipe

`php artisan db:seed --class=DemoDataSeeder` from the project root recreates:
2 branches, staff users (admin/waiter/2 chefs/cashier), a Coffee category,
5 products with sizes, mapped to the surviving `public/productImages/*` files.

## Commands

- Lint a file: `php -l <file>`
- List chef routes: `php artisan route:list --path=chef`
