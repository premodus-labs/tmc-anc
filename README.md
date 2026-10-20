# ANC Clinic System

Antenatal Care (ANC) management system for Thanthwe Medical Centre (TMC), Lilongwe.

The system tracks women currently in antenatal care, their next visit and expected delivery date, expected deliveries per month, and ANC clinician availability. It also covers visits, labs, ultrasound, delivery and postnatal records, the maternity register indicators, and reminder/follow-up outreach.

**Status:** Sprint 0 (foundation). See the project board for current work.

> **Important:** this system handles health data. Read [SECURITY.md](SECURITY.md) before contributing. Real patient data never goes into this repository or into dev/test environments.

## Stack

| Layer | Choice |
|---|---|
| Frontend | Next.js (React, TypeScript) in `frontend/` |
| Backend API | Laravel (PHP) in `backend/` |
| Database | PostgreSQL 16 |
| Migrations | Laravel migrations, with raw SQL for triggers, views and constraints |

Why these choices: [docs/decisions/0001-stack.md](docs/decisions/0001-stack.md).

## Core idea

A woman (`patient`) exists once. All clinical data hangs off her `pregnancy`. The database design is in [`docs/data-model-v2.md`](docs/data-model-v2.md). The schema is the first migration, with its SQL in `backend/database/sql/`.

## Prerequisites

- Git
- Docker and Docker Compose (runs PostgreSQL)
- PHP 8.3+ and Composer
- Node.js 22+ and npm
- `make` (optional, but the commands below use it)

## Getting started

```bash
# 1. Clone
git clone <repo-url>
cd <repo-name>

# 2. Start PostgreSQL
cp .env.example .env                       # database settings for Docker
docker compose up -d db

# 3. Backend (once backend/ exists)
cd backend
cp .env.example .env                       # use the same DB_* values as the root .env
composer install
php artisan key:generate
php artisan migrate --seed                 # create tables, load fake seed data
php artisan serve

# 4. Frontend (once frontend/ exists), in another terminal
cd frontend && npm install && npm run dev
```

Goal: a new developer is running locally in under 10 minutes. If a step is wrong or missing, fix this file in your PR.

## Useful commands

```bash
make help           # list all commands
make db-migrate     # apply pending migrations
make db-status      # show which migrations have run
make db-reset       # destroy and rebuild the local database (migrate + seed)
```

## Running tests

```bash
cd backend && php artisan test
cd frontend && npm test
```

CI runs these on every PR, plus a migration check against an empty database.

## Repository layout

```
.github/                    PR template, issue templates, CI workflow, CODEOWNERS
backend/                    Laravel API
backend/database/migrations/  Laravel migrations (forward-only)
backend/database/sql/       SQL run by migrations (triggers, views, constraints)
backend/database/seeders/   Fake seed data for development and tests
frontend/                   Next.js app
docs/                       Data model, conventions, decision records
docs/decisions/             Architecture decision records (ADRs)
docker-compose.yml          Local PostgreSQL
Makefile                    Shortcuts for database tasks
```

## How we work

- Process: Scrum, with a mini-SDLC per sprint. See [CONTRIBUTING.md](CONTRIBUTING.md).
- Each developer owns a feature slice end to end (schema, API, UI).
- Schema changes go through migrations only, never by hand.

## Documentation

- [Data model v2](docs/data-model-v2.md)
- [Conventions](docs/conventions.md) (to be written in Sprint 0)
- [Decision records](docs/decisions/)

## Contacts

- Product owner (clinic): `<name, role at TMC>`
- Tech lead / maintainers: `<names>`
