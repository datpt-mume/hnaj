---
applyTo: "hnaj-be/database/**/*.php,hnaj-be/app/Models/**/*.php,docs/database/**/*"
description: "Laravel migration and database documentation synchronization rules"
---

- Laravel migrations are the executable database schema source of truth.
- Every schema change must update the relevant migration and `docs/database/ERD.md` plus `docs/database/DATA_DICTIONARY.md` in the same change.
- Document keys, relationships, status values, money units, coordinate semantics, indexes, uniqueness, retention, and soft-delete behavior.
- Do not document a table or field as implemented when it is only proposed; label target designs clearly.
- Verify MySQL-compatible behavior for geospatial, constraints, indexes, and query semantics.
- **Container enforcement**: all migration and schema validation commands MUST run inside the `be` Docker container (`docker compose exec -T be php artisan migrate:status`, `docker compose exec -T be php artisan migrate --pretend`, `docker compose exec -T be php artisan db:show`). Never use SQLite on the host as evidence for MySQL behavior — the project database runs inside Docker.
