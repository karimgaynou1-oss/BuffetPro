# BuffetPro Backend — Laravel 11 API

Multi-tenant SaaS REST API for buffet management. PHP 8.2 + Laravel 11 + MySQL 8.

## Quick Start

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:generate-secret
# Edit .env: DB_*, JWT_SECRET, STRIPE_*
php artisan migrate
php artisan db:seed
php artisan serve
```

## Tests

```bash
php artisan test   # 24 tests, all pass (SQLite in-memory)
```

## Seeded accounts (hotel_slug=demo-hotel)

| Email | Password | Role |
|---|---|---|
| admin@buffetpro.com | Admin@123456 | super_admin |
| admin@demo-hotel.com | Admin@123456 | hotel_admin |
| chef@demo-hotel.com | Chef@123456 | chef |
| coord@demo-hotel.com | Coord@123456 | coordinator |

See [API documentation in the PR description](../docs/api.md) for full endpoint reference.
