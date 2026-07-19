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

## Data safety
- Treat the runtime MySQL database and all existing records as user data. Never delete, reset, truncate, recreate, refresh, reseed, or overwrite them unless the user explicitly requests that exact destructive operation and confirms the target database.
- Before running tests or any command that can migrate, refresh, seed, import, or otherwise mutate data, first verify the effective environment and database target. For PHPUnit, inspect `phpunit.xml` and relevant test configuration and confirm that `DB_CONNECTION`/`DB_DATABASE` resolve to an isolated test database such as SQLite `:memory:`.
- Do not run `migrate:fresh`, `migrate:refresh`, `db:wipe`, destructive SQL, broad cleanup scripts, or tests using `RefreshDatabase` when the effective database target is the runtime MySQL database.
- If test isolation cannot be proven, stop before executing the command and report the blocker. A passing test does not justify risking runtime data.
- Prefer read-only inspection before any repair. Before and after an approved data repair, verify the affected record and its relationships explicitly.
- Never create or store real account credentials in seeders, fixtures, migrations, source files, shell history, documentation, or test code. Create requested operational accounts only through an explicit one-off runtime operation, and do not rotate or reset a password unless the user requested it.

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

## Mandatory compliance footer
- End every final response with a `Tuân thủ` section, including answers that make no code changes.
- List every workspace instruction file actually applied, using workspace-relative paths. Always include `.github/copilot-instructions.md`; include scoped `*.instructions.md` files only when their `applyTo` pattern or description applied to the work.
- List every skill actually loaded and followed. Use the skill name and its `SKILL.md` path. If no skill was loaded, write `Skills: Không sử dụng`.
- Add a one-line `Kiểm chứng` entry naming the concrete checks performed. If no command or tool check was performed, write `Kiểm chứng: Không chạy kiểm chứng; chỉ trả lời tư vấn`.
- Never claim an instruction or skill was applied unless it was loaded into context and followed. This footer is an audit record, not a generic capability list.
