# HNaj Workspace Instructions

## Source of truth
- Product behavior: `docs/PRD_TECH_SPEC.md`.
- HTTP contract: `docs/api/openapi.yaml`.
- Recommendation rules: `docs/api/recommendation-policy.md`.
- Database schema: Laravel migrations; `docs/database/` explains the intended model.
- When sources conflict, stop and report the conflict. Do not invent routes, fields, tables, environment variables, or dependencies.

## Workflow
- Search and read existing code before editing.
- Keep changes focused and preserve existing public APIs unless the task explicitly changes them.
- For every client-observable API change, update OpenAPI, relevant examples/policy, and `docs/api/CHANGELOG.md` in the same change.
- For every database schema change, update the migration and the database ERD/data dictionary in the same change.
- Do not add a dependency without listing its package name, reason, alternatives, and waiting for approval.
- Keep current scaffold and target architecture clearly separated in documentation.

## Validation
- **All runtime commands MUST run inside Docker containers**, not on the host. Use:
  - Backend: `docker compose exec -T be <command>` (e.g. `composer install`, `php artisan test`, `php artisan migrate`, `php -l`, `./vendor/bin/pint --test`, `php artisan route:list`)
  - Frontend: `docker compose exec -T fe <command>` (e.g. `npm ci`, `npm run lint`, `npm run build`, `npm run dev`)
  - Run `docker compose ps` first to verify containers are running.
- **NEVER run on host for this project**: `composer`, `php artisan`, `npm`, `sudo apt install <package>`. These will fail or produce incorrect results because dependencies (`vendor`, `node_modules`) live inside Docker volumes.
- Host-only exceptions (no container needed): `git`, `git diff`, `git status`, `grep`, file read, markdown check, or OpenAPI static validation with Node built-ins that do not require project dependencies.
- Backend: `docker compose exec -T be php artisan test` and relevant Laravel checks.
- Frontend: `docker compose exec -T fe npm run lint` and `docker compose exec -T fe npm run build`.
- Check imports, types, syntax, references, and dead code introduced by the change.
- Validate OpenAPI YAML and `$ref` targets when the API contract changes.
- Review changed files and report remaining risks in Vietnamese.

## Language
- Documentation and final reports are written in Vietnamese unless an API/tool convention requires English.
