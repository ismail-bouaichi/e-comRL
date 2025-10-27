# Backend & Database — README

This document explains the Laravel backend and the database structure for this project (e-comRL).
It is intended to help you (or another developer) run, maintain, and extend the backend and understand the main DB models and relationships.

---

## Quick overview
- Framework: Laravel 10.x
- API types: REST + GraphQL (Lighthouse)
- Auth: Laravel Passport (OAuth2)
- Payment: Stripe Checkout + webhook processing
- Business logic: Action classes in `app/Actions/*` (single responsibility)
- GraphQL schema: `graphql/schema.graphql`
- Migrations: `database/migrations`
- Seeders: `database/seeders`

---

## Useful project paths
- `app/Models` — Eloquent models
- `app/Actions` — Reusable business logic (Payment, Product, etc.)
- `app/GraphQL/Queries` — GraphQL query resolvers
- `app/GraphQL/Mutations` — GraphQL mutation resolvers
- `app/Http/Controllers/Api` — REST API controllers
- `graphql/schema.graphql` — GraphQL types, queries, mutations
- `routes/api.php` — REST api routes
- `routes/web.php` — web routes (payment success/cancel)
- `config/cors.php` — CORS configuration
- `config/services.php` — 3rd party service config (e.g. stripe)

---

## How to run (development)
1. Install composer dependencies:

```powershell
cd C:\laragon\www\pass
composer install
```

2. Configure `.env` (copy from `.env.example` and set values). Important env variables listed below.

3. Run database migrations and seeders:

```powershell
php artisan migrate
php artisan db:seed
```

4. Start Laravel on all interfaces (useful when testing from another device):

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

5. Validate GraphQL schema (Lighthouse):

```powershell
php artisan lighthouse:validate-schema
```

6. Clear cache if you change config:

```powershell
php artisan config:clear
php artisan cache:clear
```

7. Debugging/Tinker:

```powershell
php artisan tinker
```

---

## Important environment variables
Add or verify these in your `.env` file (example values):

```env
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=

# Stripe (must be single-line values, no line breaks)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# OAuth / Passport
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=...
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=...
```

---

## Database model summary & relationships
Below is a concise explanation of the main models and their key fields. Use `graphql/schema.graphql` and the migrations for exact field names.

Note: field names below are the ones used in the GraphQL schema and Eloquent models.

### Product
- Core columns: `id`, `name`, `description`, `price`, `stock_quantity`, `category_id`, `brand_id`, `slug`, `created_at`, `updated_at`
- Relations:
  - belongsTo Category (`category_id`)
  - belongsTo Brand (`brand_id`)
  - hasMany Image
  - hasMany Rating
  - belongsToMany Discount (pivot `discount_product`)
- Computed / appended attributes: `discounted_price`, `is_discounted` (via `currentDiscount()`)

### Category
- Core columns: `id`, `name`, `icon`, `created_at`, `updated_at`
- Relations:
  - hasMany Product

### Brand
- Core columns: `id`, `name`, `logo_path`, `slug`, `created_at`, `updated_at`
- Relations:
  - hasMany Product
- Extra accessors: `getLogoUrlAttribute()` builds storage URL

### Image
- Core columns: `id`, `product_id`, `file_path`, `created_at`, `updated_at`
- Relations:
  - belongsTo Product

### Rating
- Core columns: `id`, `product_id`, `user_id`, `rating` (float), `comment`, `created_at`, `updated_at`
- Relations:
  - belongsTo Product
  - belongsTo User

### Discount
- Core columns: `id`, `name`, `code`, `discount_type` (percentage|fixed), `discount_value`, `is_active`, `start_date`, `end_date`
- Relations:
  - belongsToMany Product (pivot `discount_product`)
- Scopes:
  - `scopeCurrent()` returns active discounts for `now()`

### User
- Core columns: typical Laravel `users` table columns + role relationship
- Relations:
  - hasMany Order
  - belongsToMany Product for favorites

### Order
- Core columns: `id`, `first_name`, `last_name`, `email`, `phone`, `status`, `session_id`, `shipping_cost`, `latitude`, `longitude`, `customer_id`, `delivery_worker_id`, `created_at`, `updated_at`
- Relations:
  - belongsTo User (customer)
  - belongsTo User (deliveryWorker) via `delivery_worker` relation
  - hasMany OrderDetail

### OrderDetail
- Core columns: `id`, `order_id`, `product_id`, `quantity`, `total_price`, `address`, `city`, `zip_code`, `created_at`, `updated_at`
- Relations:
  - belongsTo Order
  - belongsTo Product

---

## GraphQL specifics
- Schema file: `graphql/schema.graphql` (types, queries, mutations)
- Query resolvers are in `app/GraphQL/Queries` (e.g., `ProductsQuery`)
- Mutation resolvers in `app/GraphQL/Mutations`
- Important Notes:
  - The `products` query returns a `ProductPaginator` object with `data` and `paginatorInfo` fields. Previously the resolver returned a `LengthAwarePaginator` directly; the code now returns a GraphQL-friendly array shape.
  - Use `php artisan lighthouse:validate-schema` to validate schema changes.

---

## Payment flow (Stripe)
- Checkout sessions are created by `app/Actions/Payment/CreateStripeCheckoutAction.php`
- Stripe webhooks are handled by `app/Actions/Payment/ProcessWebhookAction.php` (with idempotency checks)
- Refunds handled by `RefundPaymentAction.php`
- Important: Stripe keys in `.env` must be on a single line (no line breaks) or webhook signature verification will fail.

Webhook endpoint (example):
```
POST http://localhost:8000/api/orders/webhook
```

When testing locally, use the Stripe CLI:
```powershell
stripe listen --forward-to localhost:8000/api/orders/webhook
stripe trigger checkout.session.completed
```

---

## Actions pattern & where business logic lives
- All complex business logic is extracted into Action classes under `app/Actions/` (e.g., Product actions, Payment actions). This ensures controllers and GraphQL resolvers stay thin and reuse logic across endpoints.

Examples:
- `app/Actions/Product/CalculateProductDiscountAction.php`
- `app/Actions/Payment/CreateStripeCheckoutAction.php`

---

## Migrations & Seeding
- Migrations live under `database/migrations` and follow Laravel conventions.
- Seeds are under `database/seeders`.
- Use `php artisan migrate --seed` to build and populate test data if seeders exist.

---

## Common Artisan commands
```powershell
# Start server
php artisan serve --host=0.0.0.0 --port=8000

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# GraphQL schema validation
php artisan lighthouse:validate-schema

# Run tests
php artisan test
# or
vendor/bin/phpunit
```

---

## Troubleshooting tips
- CORS errors: check `config/cors.php` and ensure the frontend or mobile origin is allowed. For local mobile testing set `php artisan serve --host=0.0.0.0` and ensure firewall allows port.
- "Failed to fetch" in frontend: confirm backend is running, GraphQL endpoint is correct (`http://YOUR_IP:8000/graphql`), and CORS configured.
- GraphQL errors about missing fields: update `graphql/schema.graphql` and run `php artisan lighthouse:validate-schema`.
- Stripe issues: ensure `.env` contains single-line keys and that webhook secret is correct.
- Log files: check `storage/logs/laravel.log` for server exceptions and stack traces.

---

## ER-style summary (text)
- Product (many) <-- Category (one)
- Product (many) <-- Brand (one)
- Product (many) --> Image (many)
- Product (many) --> Rating (many)
- Product (many) <--> Discount (many-to-many pivot `discount_product`)
- Order (one) --> OrderDetail (many)
- OrderDetail (many) --> Product (one)
- User (one) --> Order (many)

---

## Next steps & tips for contributors
- Keep business logic inside `app/Actions` for reuse
- When changing GraphQL types, update `graphql/schema.graphql` and validate schema
- Add migrations when modifying DB structure
- Use `test-graphql.php` (exists in repo) or `php artisan tinker` for quick resolver tests

---

If you want, I can also:
- generate a small SQL schema file showing `CREATE TABLE` statements for the main tables,
- or create a simple ER diagram (text or Mermaid) for inclusion in the README.

File created: `README_BACKEND.md`
