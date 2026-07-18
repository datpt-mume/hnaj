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
