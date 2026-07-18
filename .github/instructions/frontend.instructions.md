---
applyTo: "hnaj-fe/**/*.{ts,tsx,css}"
description: "React Vite frontend rules and API contract alignment"
---

- Follow the existing React, Vite, and TypeScript setup before introducing patterns.
- Client types, request payloads, response handling, error codes, and message keys must match `docs/api/openapi.yaml`.
- Do not hard-code backend routes, tags, permissions, or response fields outside the approved contract.
- Keep public flows mobile-first and admin flows desktop-oriented unless the task says otherwise.
- **All frontend commands MUST run inside the `fe` Docker container**: `docker compose exec -T fe <command>`.
  - Examples: `docker compose exec -T fe npm ci`, `docker compose exec -T fe npm run lint`, `docker compose exec -T fe npm run build`, `docker compose exec -T fe npm run dev`.
  - Never run `npm` or `npx` directly on the host for this project — `node_modules` is inside the Docker volume.
- Do not add a package without documenting its reason, alternatives, and waiting for approval.
