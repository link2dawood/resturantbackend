---
name: init
description: Onboards onto this repository by reading project docs and mapping structure, routes, and domain areas. Use when the user types /init, asks to understand the codebase, project layout, or "how things work" before coding.
---

# /init — Codebase orientation

When the user invokes **/init** or asks for a project overview, **do not guess**. Read the repo and produce a concise, accurate picture of structure and dynamics.

## Workflow

1. **Read `CURSOR.md` (repo root)**  
   Single source of truth for stack, doc locations, important paths, nav/routes, and changelog. If it conflicts with code, note both and prefer code for behavior.

2. **Skim `docs/README.md`**  
   Index to deeper docs; follow links only if the user’s goal needs depth (e.g. architecture, permissions, a specific feature).

3. **Optional deep dive** (only if needed for the question):  
   - `docs/architecture/codebase-analysis.md` — broader architecture / schema / flows  
   - `docs/features/` or `docs/guides/` — feature-specific behavior

4. **Confirm live surface area**  
   Scan `routes/web.php` and `routes/api.php` for major groups (auth, daily reports, admin areas, APIs). Cross-check names with `app/Http/Controllers/`.

5. **Summarize “project dynamics”**  
   Cover in plain language:
   - **Stack** (Laravel, PHP, DB, front-end stack from `CURSOR.md` / `composer.json`)
   - **Tenancy / access** — multi-store, roles, `User` helpers (`getAccessibleStoreIds`, `hasStoreAccess`, middleware)
   - **Money / reporting domains** — daily reports, expenses, COA, bank, third-party statements, owner CC imports, P&L / merchant analytics (as present in routes and `CURSOR.md`)
   - **Where to change things** — controllers vs models vs Blade (`resources/views/`, `layouts/tabler.blade.php` for nav)

## Output format

Use short sections:

- **Overview** — what the app is for (1–2 sentences)
- **Entry points** — key web routes and API prefixes
- **Important directories** — table-style or bullets tied to this repo
- **Docs** — which file to open next for detail
- **Gotchas** — PHP version, env, hosting constraints only if documented in-repo

Do not dump entire file trees. Prefer pointers over walls of paths.

## Examples

**User:** `/init`  
**Agent:** Read `CURSOR.md` → `docs/README.md` → sample `routes/web.php` / `routes/api.php`, then reply with the structured summary above.

**User:** Where do third-party PDF imports live?  
**Agent:** Still follow this skill’s doc order, then point to `ThirdPartyImportController`, related models, `routes/api.php`, and `docs/features/` if relevant.
