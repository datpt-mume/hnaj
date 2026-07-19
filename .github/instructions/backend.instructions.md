---
applyTo: "hnaj-be/**/*.php,hnaj-be/**/*.json,hnaj-be/**/*.xml"
description: "Laravel backend rules, API implementation, validation, and MySQL integration"
---

- Follow existing Laravel application structure and conventions.
- Do not create a route, table, field, permission, or environment variable unless it exists in the approved contract or the task explicitly introduces it.
- API changes must update `docs/api/openapi.yaml`, relevant examples/policy, and `docs/api/CHANGELOG.md` in the same change.
- Database changes must update migrations and `docs/database/` in the same change.
- **All backend commands MUST run inside the `be` Docker container**: `docker compose exec -T be <command>`.
  - Examples: `docker compose exec -T be composer install`, `docker compose exec -T be php artisan test`, `docker compose exec -T be php artisan migrate`, `docker compose exec -T be php -l <file>`, `docker compose exec -T be php artisan route:list --path=api`, `docker compose exec -T be ./vendor/bin/pint --test`.
  - Never run `php artisan`, `composer`, or `php` directly on the host for this project — `vendor` is inside the Docker volume, not on the host filesystem.
- Prefer MySQL-compatible integration coverage for MySQL-specific queries; do not assume SQLite behavior is equivalent.
- Before every test command, inspect the effective PHPUnit database configuration and state in the progress update which isolated database will be used. Do not run the tests if this cannot be established.
- `RefreshDatabase`, `DatabaseMigrations`, `DatabaseTruncation`, migrations, seeders, imports, and cleanup commands are data-mutating operations. They may run automatically only against an isolated disposable test database, never against runtime MySQL `hnaj`.
- Before any command capable of mutating runtime MySQL, name the target connection/database and classify the operation as read-only, additive, or destructive. Destructive operations require the user's explicit approval for that exact command and target.
- Never use test execution as a way to create, repair, or validate operational accounts. Inspect operational users with read-only queries and perform requested repairs with a narrowly scoped one-off transaction.
