# FreshBasket Grocery Booking System

FreshBasket is a Laravel 12 take-home implementation of a grocery catalogue, inventory, and transaction-safe booking API. It includes JWT authentication, Spatie RBAC, a repository/service architecture, MySQL pessimistic locking, Redis-backed catalogue caching, an AJAX Blade client, tests, and English/Bangla UI localization.

## Technical profile

- PHP 8.2+ (tested on PHP 8.3), Laravel 12
- `tymon/jwt-auth` 2.3
- `spatie/laravel-permission` 6.x using the `api` guard
- MySQL 8+ as the production source of truth
- Redis for versioned catalogue page caching
- PHPUnit feature tests; SQLite in memory for fast automated tests
- Dependency-free Blade/CSS/JavaScript frontend using `fetch`

Money is stored and calculated as integer minor units (`*_cents`). No frontend price, subtotal, total, role, or user ID participates in authoritative calculations.

## Setup

### Local PHP + MySQL

Requirements: PHP 8.2+, Composer 2, MySQL 8+, Redis, and the PHP extensions required by Laravel, MySQL and Redis.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

On PowerShell, use `Copy-Item .env.example .env` instead of `cp`. Before migrating, create the MySQL database and update `DB_*`, `REDIS_*`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in `.env`. `php artisan migrate --seed` creates permissions, the configured administrator, and sample groceries. Change the example admin password before using the application outside local development.

The UI is available at `http://127.0.0.1:8000`. No Node build is required because the small Blade client ships as static assets.

To verify the installation:

```bash
php artisan about
php artisan migrate:status
php artisan test
```

## Roles and permissions

| Role | Permissions |
|---|---|
| `admin` | All catalogue, dashboard, order, user, and role-management permissions |
| `user` | `groceries.view`, `orders.create`, `orders.view-own` |

Registration always assigns only the `user` role. Custom roles can be created from the admin panel with granular permissions. The built-in `admin` and `user` roles are protected, and an in-use custom role cannot be deleted. There is no `role_id` column on `users`; Spatie polymorphic tables own role assignments. Admin creation is configuration-driven through `AdminUserSeeder`.

### Admin panel

Signing in with any account that has an admin-panel permission automatically opens the responsive sidebar workspace. It includes an overview, catalogue and stock tools, order search/status workflow, user creation and activation controls, and custom role/permission management. The system prevents self-lockout, removal of the final active administrator, editing built-in roles, and deletion of roles that still have users. Inactive accounts cannot sign in or continue using an existing token. Admins can also switch to the Storefront preview. UI visibility is permission-aware, while every `/api/v1/admin/*` action remains independently protected by JWT and permission middleware.

Sidebar and button visibility are a user-experience aid, not the security boundary. JWT authentication, active-account enforcement, and Spatie permissions are applied to the routes through middleware. Controllers contain no hard-coded role checks, so direct API requests receive the same authorization enforcement as the UI.

## API

Base path: `/api/v1`. Send `Accept: application/json`. Protected routes require `Authorization: Bearer <token>`.

### Authentication

| Method | Endpoint | Access | Purpose |
|---|---|---|---|
| POST | `/auth/register` | Public; 10/minute | Register a normal user and issue JWT |
| POST | `/auth/login` | Public; 10/minute | Authenticate and issue JWT |
| GET | `/auth/me` | JWT | Current user and roles |
| POST | `/auth/refresh` | JWT; throttled | Rotate JWT within refresh TTL |
| POST | `/auth/logout` | JWT | Blacklist current JWT |

Register body:

```json
{
  "name": "Amina Rahman",
  "email": "amina@example.com",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

### User catalogue and orders

| Method | Endpoint | Permission | Purpose |
|---|---|---|---|
| GET | `/groceries?search=&page=1&per_page=15` | Public | Paginated in-stock active catalogue |
| GET | `/groceries/{id}` | Public | View an available grocery |
| POST | `/orders` | user + `orders.create`; 15/minute | Atomically book multiple items |
| GET | `/orders?page=1&per_page=15` | user + `orders.view-own` | Own paginated order history |
| GET | `/orders/{id}` | user + `orders.view-own` | View an order only when owned |

Checkout body contains only identifiers and quantities:

```json
{
  "items": [
    {"grocery_item_id": 1, "quantity": 2},
    {"grocery_item_id": 3, "quantity": 1}
  ]
}
```

### Admin catalogue and inventory

| Method | Endpoint | Permission |
|---|---|---|
| GET | `/admin/groceries?search=&is_active=&page=1&per_page=15` | `groceries.view` |
| POST | `/admin/groceries` | `groceries.create` |
| GET | `/admin/groceries/{id}` | `groceries.view` |
| PUT/PATCH | `/admin/groceries/{id}` | `groceries.update` |
| DELETE | `/admin/groceries/{id}` | `groceries.delete` |
| PATCH | `/admin/groceries/{id}/stock` | `inventory.update` |

Catalogue writes accept `unit_price_cents`; stock replacement uses `{ "stock_quantity": 20 }`. General product update prohibits `stock_quantity`, keeping inventory behind its separately authorized endpoint.

### Admin operations and access control

| Method | Endpoint | Permission | Purpose |
|---|---|---|---|
| GET | `/admin/dashboard` | `dashboard.view` | Metrics, recent orders, and low-stock items |
| GET | `/admin/orders?search=&status=&date_range=&page=` | `orders.view-all` | Search and filter all orders |
| GET | `/admin/orders/{id}` | `orders.view-all` | Inspect an order and its item snapshots |
| PATCH | `/admin/orders/{id}` | `orders.update` | Advance or cancel an order |
| GET | `/admin/users?search=&role=&is_active=&page=` | `users.view` | Search and filter users |
| GET | `/admin/users/role-options` | `users.view` | Roles available to user management |
| POST | `/admin/users` | `users.manage` | Create a user and assign roles |
| GET | `/admin/users/{id}` | `users.view` | View a managed user |
| PATCH | `/admin/users/{id}` | `users.manage` | Edit profile, status, password, or roles |
| GET | `/admin/roles` | `roles.view` | Roles and permission catalogue |
| POST | `/admin/roles` | `roles.manage` | Create a custom role |
| PATCH | `/admin/roles/{id}` | `roles.manage` | Rename a custom role or sync permissions |
| DELETE | `/admin/roles/{id}` | `roles.manage` | Delete an unused custom role |

Order status transitions are `confirmed → processing → completed`; confirmed or processing orders may also be cancelled. Completed and cancelled orders are terminal.

Example role body:

```json
{
  "name": "order-manager",
  "permissions": ["orders.view-all", "orders.update"]
}
```

Example order-status body:

```json
{"status": "processing"}
```

Successful single resources use `{ "data": {...} }`; paginated resources include Laravel `links` and `meta`. Validation uses HTTP 422, authentication 401, authorization 403, missing resources 404, stock conflicts 409, creation 201, and deletion 204. Domain errors include a stable `error` code.

## Architecture decisions

```text
HTTP -> JWT / active-user / Spatie middleware -> Form Request -> Controller
     -> Service -> Repository contract -> Eloquent / MySQL
```

- Controllers only translate HTTP inputs/outputs. Role and permission policy is route middleware, never controller conditionals.
- Domain-specific repository contracts are bound in `DomainServiceProvider`; there is no generic base repository.
- `OrderService` sorts grocery IDs, opens a short transaction, locks rows with `SELECT ... FOR UPDATE`, validates live database stock, creates the order and immutable item snapshots, deducts stock, and commits. Retry count is three for transient deadlocks.
- Both checkout and absolute admin stock replacement lock the grocery row, preventing lost updates between inventory management and booking.
- `order_items` stores historical name, unit, unit price, quantity, and subtotal. Product edits or soft deletion cannot corrupt history.
- Catalogue reads use a cache abstraction with versioned keys and five-minute entries. Catalogue/order writes change the version only after successful commit. Checkout never reads authoritative stock from cache.
- Query-driven indexes cover active catalogue traversal, grocery name search, user order history, and foreign-key access. Order history eager-loads items to avoid N+1 queries.
- Database foreign keys, unsigned columns, and MySQL `CHECK` constraints supplement request/domain validation.

### Important business rules

- Registration ignores any supplied role and always assigns `user`; the initial administrator is configuration-seeded.
- Checkout accepts grocery IDs and quantities only. Prices and totals are recalculated from locked database rows using integer minor units.
- Multiple grocery rows are locked in deterministic ID order inside a short transaction, preventing overselling and reducing deadlock risk.
- Stock and catalogue cache versions change only after a successful commit. Cached stock is never authoritative during checkout.
- A user can only read orders owned by that authenticated user; administrators require explicit all-order permissions.
- At least one active administrator must remain, and an administrator cannot deactivate or demote themselves.
- Built-in roles are immutable; custom roles must be unused before deletion.

## Frontend and localization

The Blade screen lets guests browse, search, and build a cart. JWT authentication is requested only when the visitor signs in, places an order, or opens order history. After successful authentication, the pending checkout or history action resumes without a full page reload. Use `?lang=en` or `?lang=bn`. The design reference is stored at [`docs/design/freshbasket-concept.png`](docs/design/freshbasket-concept.png).

## Quality checks

```bash
vendor/bin/pint --test
php artisan test
php artisan route:list --except-vendor
```

The automated suite currently contains 18 tests with 84 assertions. It covers registration role safety, login/me, token refresh, logout blacklisting, inactive-account blocking, middleware RBAC, custom-role lifecycle, last-administrator protection, admin inventory separation, order status transitions, database-authoritative totals, snapshot history, transaction rollback, oversell prevention, and own-order isolation.

For a true parallel-lock integration check, run the checkout suite against MySQL in CI; SQLite is used locally for speed and does not emulate `FOR UPDATE` contention.
