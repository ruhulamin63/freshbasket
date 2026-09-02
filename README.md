# FreshBasket Grocery Booking System

FreshBasket is a Laravel 12 take-home implementation of a grocery catalogue, inventory, and transaction-safe booking API. It includes JWT authentication, Spatie RBAC, a repository/service architecture, MySQL pessimistic locking, Redis-backed catalogue caching, an AJAX Blade client, tests, and English/Bangla UI localization.

## Technical profile

- PHP 8.3, Laravel 12
- `tymon/jwt-auth` 2.3
- `spatie/laravel-permission` 6.x using the `api` guard
- MySQL 8.4 as the production source of truth
- Redis for versioned catalogue page caching
- PHPUnit feature tests; SQLite in memory for fast automated tests
- Dependency-free Blade/CSS/JavaScript frontend using `fetch`

Money is stored and calculated as integer minor units (`*_cents`). No frontend price, subtotal, total, role, or user ID participates in authoritative calculations.

## Setup

### Local PHP + MySQL

Requirements: PHP 8.3+, Composer 2, MySQL 8+, and Redis (recommended).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

On PowerShell, use `Copy-Item .env.example .env` instead of `cp`. Before migrating, update the database and Redis values in `.env`. The seeded admin credentials come from `ADMIN_EMAIL` and `ADMIN_PASSWORD`; change the example password before using the application outside local development.

The UI is available at `http://127.0.0.1:8000`. No Node build is required because the small Blade client ships as static assets.

## Roles and permissions

| Role | Permissions |
|---|---|
| `admin` | `groceries.view`, `groceries.create`, `groceries.update`, `groceries.delete`, `inventory.update`, `orders.create`, `orders.view-own` |
| `user` | `groceries.view`, `orders.create`, `orders.view-own` |

Registration always assigns only the `user` role. There is no `role_id` column on `users`; Spatie polymorphic tables own role assignments. Admin creation is configuration-driven through `AdminUserSeeder`.

### Admin panel

Signing in with an account that has the `admin` role automatically opens the responsive Admin Panel. It supports server-side search, active/inactive filtering, pagination, product creation and editing, soft deletion, and a separately authorized absolute stock update. Admins can switch to a read-only Storefront preview; customer ordering controls are not exposed in that view. The UI role check controls presentation only—the `/api/v1/admin/*` routes remain protected by JWT role and permission middleware.

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

Successful single resources use `{ "data": {...} }`; paginated resources include Laravel `links` and `meta`. Validation uses HTTP 422, authentication 401, authorization 403, missing resources 404, stock conflicts 409, creation 201, and deletion 204. Domain errors include a stable `error` code.

## Architecture decisions

```text
HTTP → JWT / Spatie middleware → Form Request → Controller
     → Service → Repository contract → Eloquent / MySQL
```

- Controllers only translate HTTP inputs/outputs. Role and permission policy is route middleware, never controller conditionals.
- Domain-specific repository contracts are bound in `DomainServiceProvider`; there is no generic base repository.
- `OrderService` sorts grocery IDs, opens a short transaction, locks rows with `SELECT ... FOR UPDATE`, validates live database stock, creates the order and immutable item snapshots, deducts stock, and commits. Retry count is three for transient deadlocks.
- Both checkout and absolute admin stock replacement lock the grocery row, preventing lost updates between inventory management and booking.
- `order_items` stores historical name, unit, unit price, quantity, and subtotal. Product edits or soft deletion cannot corrupt history.
- Catalogue reads use a cache abstraction with versioned keys and five-minute entries. Catalogue/order writes change the version only after successful commit. Checkout never reads authoritative stock from cache.
- Query-driven indexes cover active catalogue traversal, grocery name search, user order history, and foreign-key access. Order history eager-loads items to avoid N+1 queries.
- Database foreign keys, unsigned columns, and MySQL `CHECK` constraints supplement request/domain validation.

## Frontend and localization

The Blade screen lets guests browse, search, and build a cart. JWT authentication is requested only when the visitor signs in, places an order, or opens order history. After successful authentication, the pending checkout or history action resumes without a full page reload. Use `?lang=en` or `?lang=bn`. The design reference is stored at [`docs/design/freshbasket-concept.png`](docs/design/freshbasket-concept.png).

## Quality checks

```bash
vendor/bin/pint --test
php artisan test
php artisan route:list --except-vendor
```

The automated suite covers registration role safety, login/me, token refresh, logout blacklisting, route RBAC, admin inventory separation, database-authoritative totals, snapshot history, transaction rollback, oversell prevention, and own-order isolation.

For a true parallel-lock integration check, run the checkout suite against MySQL in CI; SQLite is used locally for speed and does not emulate `FOR UPDATE` contention.
