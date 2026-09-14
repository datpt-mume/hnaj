# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT.md`** at the repo root, or
- **`CONTEXT-MAP.md`** at the repo root if it exists: it points at one `CONTEXT.md` per context. Read each one relevant to the topic.
- **`docs/adr/`**: read ADRs that touch the area you're about to work in. In multi-context repos, also check `src/<context>/docs/adr/` for context-scoped decisions.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them upfront. The `/domain-modeling` skill (reached via `/grill-with-docs` and `/improve-codebase-architecture`) creates them lazily when terms or decisions actually get resolved.

## Layout in this repo

Single-context layout (this repo):

```
/
├── CONTEXT.md          ← created lazily by /domain-modeling, do NOT pre-create
├── docs/adr/           ← created lazily, do NOT pre-create
├── docs/migration/knowledge-base/   ← existing migration KB (source of truth for the BE port)
└── plans/project-progress.md        ← progress ledger required by AGENTS.md §5.2
```

Until `CONTEXT.md` exists, treat `docs/migration/knowledge-base/` as the domain glossary and behavior source for the Laravel→Spring Boot port (endpoint inventory, API contract, schema, domain rules, auth/security, test catalog, traceability matrix).

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `CONTEXT.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal: either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0007 (event-sourced orders), but worth reopening because…_

## Flag AGENTS.md conflicts

This repo also has `AGENTS.md` (mandatory workflow rules) and `.agents/skills/` (skills). If a skill's default behavior conflicts with `AGENTS.md`, `AGENTS.md` wins unless the user explicitly overrides — surface the conflict instead of silently picking one side.
