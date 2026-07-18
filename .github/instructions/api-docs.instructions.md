---
applyTo: "docs/api/**/*,hnaj-be/routes/**/*.php,hnaj-be/app/Http/**/*.php,hnaj-fe/src/**/*.{ts,tsx}"
description: "API contract synchronization and documentation rules"
---

- Treat `docs/api/openapi.yaml` as the machine-readable HTTP contract.
- Any client-observable change to route, method, status, request, response, schema, authentication, permission, error, pagination, idempotency, or business-visible metadata must update OpenAPI in the same change.
- Update `docs/api/CHANGELOG.md` for every API contract change. Update `recommendation-policy.md` when recommendation behavior changes.
- Keep stable error codes and message keys in the API; user-facing Vietnamese text belongs to the frontend translation layer.
- Validate YAML syntax, `$ref` targets, operation IDs, examples, and security requirements before finishing.
- Never invent an endpoint or field to make documentation look complete; mark unresolved decisions as TBD and report them.
- **Container enforcement**: when an API change involves BE code, run `php artisan route:list`, `php artisan test`, and `php -l` inside `docker compose exec -T be`. When it involves FE code, run `npm run build` and `npm run lint` inside `docker compose exec -T fe`. OpenAPI YAML static checks may run on the host only if they use no project dependencies.
