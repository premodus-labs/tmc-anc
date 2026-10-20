# 0001: Technology stack

- **Status:** Accepted
- **Date:** 2026-10-20
- **Deciders:** the three developers

## Context

We are building a clinical management system for Thanthwe Medical Centre, starting with Antenatal Care. Three developers each own feature slices end to end, so everyone works across the database, API and UI. The database design (triggers, views, partial unique indexes, generated columns, a dedicated `anc` schema) is already written as SQL and tested. Speed of execution is a priority, so we avoid adding tools we don't need.

## Decision

| Layer | Choice |
|---|---|
| Frontend | Next.js (React, TypeScript) |
| Backend API | Laravel (PHP) |
| Database | PostgreSQL 15+ (16 in local dev and CI) |
| Migrations | Laravel's built-in migrations |

## Consequences

- **Raw SQL inside Laravel migrations.** Laravel's schema builder cannot express triggers, views, partial unique indexes, generated columns or our `anc` schema. For those, a migration is a thin PHP file that runs a SQL file (see CONTRIBUTING.md, section 4). Simple changes (add a column, add an index) may use the schema builder.
- Set `'search_path' => 'anc,public'` in `config/database.php` so every connection uses the `anc` schema.
- Eloquent models map to existing tables. Generated and trigger-maintained columns are read-only in models.
- Verify early (Sprint 0) that `migrate:fresh` rebuilds the schema cleanly twice in a row, since it drops tables and views but not functions or custom types.
- Two languages (PHP, TypeScript) means every developer needs to be productive in both. We accept this for end-to-end slice ownership.
- Auth between Next.js and Laravel (Sanctum, cookie or token) is settled in the Sprint 0 walking skeleton.
- API contract: OpenAPI generated from Laravel, with TypeScript types generated for Next.js.

## Alternatives considered

- **Flyway (plain SQL migrations):** native SQL files, checksum validation of applied migrations, and independence from the app. Rejected for now because it adds a tool to learn and operate, and it would take over Laravel's migration workflow (`migrate:fresh`, test database refresh). Revisit if raw-SQL migrations become painful or schema drift becomes a problem.
- **Single-language stack (TypeScript, or Laravel with Livewire/Inertia):** fewer moving parts, but not chosen by the team.
