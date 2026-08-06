# Design System — HNAJ (Master)

> Global source of truth. Page-specific overrides live in `design-system/hnaj/pages/`.
> Generated from ui-ux-pro-max skill, reconciled with the existing Hallmark "Split Studio" system.

## Product type
- **Primary:** Local Events & Discovery → Vibrant & Block-based + Motion-Driven
- **Secondary:** Restaurant/Food Service → appetizing warm palette + hero-centric
- **Stack:** React 19 + Vite 8 + React Router 7 + react-icons (Remix), CSS modules via plain CSS + design tokens. No Tailwind, no UI library.

## Two-layer palette (semantic tokens in `hnaj-fe/tokens.css`)
The system keeps the existing Hallmark leaf-green identity for brand/auth and adds a warm discovery/food layer for the explore experience.

| Layer | Tokens | Use |
|---|---|---|
| Brand/auth (existing) | `--color-accent*`, `--color-paper`, `--color-ink` | Login/register, admin, wordmark |
| Discovery/food (Phase 0) | `--color-cream`, `--color-cream-soft`, `--color-ink-warm`, `--color-teal`, `--color-teal-deep`, `--color-flame`, `--color-flame-hover`, `--color-sun` | Home nav, filters, place cards, CTAs |

### CTA hierarchy
- **Primary CTA for discovery** ("Đi tới đó", "Random") → `--color-flame` (appetizing orange-red). Use a dedicated `.button--flame` variant; do NOT overload the green `.button--primary` used by auth.
- Trust/link/info → `--color-teal`.
- Highlight/badge → `--color-sun`.

### Contrast
- All new token pairs must meet WCAG AA (≥ 4.5:1 for text). Dark variants defined in `prefers-color-scheme: dark` block.

## Typography
- Keep project-local system stack (no Google Fonts dependency was approved):
  - Display: `Trebuchet MS, Aptos Display, sans-serif`
  - Body: `Aptos, Segoe UI, sans-serif`
  - Mono (kickers/labels): `Cascadia Mono, SFMono-Regular, monospace`
- Body ≥ 16px, line-height 1.5. Type scale from `--text-*` tokens.

## Spacing / density
- 4-point-derived `--space-*` scale.
- Discovery + marketing: spacious (uses default scale).
- Admin dashboards: denser (tighter scale) — apply per-page override when built.

## Motion
- Motion-cut project: CSS transitions only (120–220ms), transform/opacity, respect `prefers-reduced-motion` (already present in `App.css`).
- Roll feedback: result card fades/slides in on new place (meaningful, ≤ 300ms).

## Icons
- react-icons (Remix Icon), SVG only. No emoji as icons. Icon-only buttons require `aria-label`.

## Layout
- Mobile-first; breakpoints already used: 40rem, 60rem, 48rem, 32rem.
- Discovery is mobile-first because map-opening happens on mobile (PRD 9.4).

## UX states (mandatory)
- Every async flow: loading (skeleton >300ms), error (message + retry), empty (message + action), success, permission/unauthenticated.
- Touch targets ≥ 44px; keyboard accessible; visible focus; not color-only status.

## Anti-patterns to avoid
- Raw hex in components (use tokens). No new hard-coded palette.
- Emoji as icons. Color-only status conveying. Placeholder-only labels. Missing loading/empty/error states.
- Mixing the green auth accent and warm discovery accent inconsistently — keep the two-layer split explicit.