# HNAJ frontend

React + TypeScript + Vite frontend for HNAJ.

## Development

The Docker development environment serves the SPA at `http://localhost:8082` and proxies `/api` requests to the backend container. Use the bootstrap steps in [`hnaj-docker/README.md`](../hnaj-docker/README.md) to start the complete stack.

The frontend uses the repository's existing npm scripts:

```bash
npm run lint
npm run build
```

## Authentication token storage

The browser intentionally persists user and admin Sanctum bearer tokens in `window.localStorage` through the central [`tokenStorage.ts`](src/services/tokenStorage.ts) module. This preserves the current SPA behavior across page reloads and is not an HttpOnly-cookie authentication flow.

Because JavaScript can read localStorage, an XSS vulnerability could expose a stored bearer token. The production Nginx configuration therefore sends a restrictive Content Security Policy and related security headers, while the application must still prevent XSS at the source:

- Do not log, render, interpolate, or include tokens in URLs.
- Keep token reads/writes/removal inside [`tokenStorage.ts`](src/services/tokenStorage.ts).
- Do not add inline scripts, `eval`, or other CSP-relaxing behavior to the production bundle.
- Treat user-controlled content as untrusted and preserve React's escaped rendering defaults.
- Clear a token when an authenticated restore or request proves that the session is no longer valid.

CSP reduces the likelihood and impact of XSS but cannot eliminate the residual risk of localStorage bearer-token persistence. A future migration to HttpOnly, SameSite cookies would require an explicit authentication-architecture decision and coordinated backend/frontend changes.
