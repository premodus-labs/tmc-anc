# ANC Clinic System

Antenatal Care (ANC) management system for Thanthwe Medical Centre (TMC), Lilongwe.

The system tracks women currently in antenatal care, their next visit and expected delivery date, expected deliveries per month, and ANC clinician availability. It also covers visits, labs, ultrasound, delivery and postnatal records, the maternity register indicators, and reminder/follow-up outreach.

**Status:** Sprint 0 (foundation). See the project board for current work.

> **Important:** this system handles health data. Read [SECURITY.md](SECURITY.md) before contributing. Real patient data never goes into this repository or into dev/test environments.

## Core idea

A woman (`patient`) exists once. All clinical data hangs off her `pregnancy`. The database design is in [`docs/data-model-v2.md`](docs/data-model-v2.md) and the schema in [`db/migrations/0001_init.sql`](db/migrations/0001_init.sql).

## Prerequisites

> TODO: fill in once the stack is chosen (see `docs/decisions/0001-stack.md`).

- Git
- Docker and Docker Compose (for local PostgreSQL 15+)
- `<language runtime and version>`
- `<package manager>`

## Getting started

> TODO: replace the placeholder commands once the stack is chosen. Goal: a new developer is running locally in under 10 minutes.

```bash
# 1. Clone
git clone <repo-url>
cd <repo-name>

# 2. Configure environment
cp .env.example .env          # then edit values if needed

# 3. Start the database
docker compose up -d db

# 4. Apply migrations
<migration command>

# 5. Load fake seed data (never real patient data)
<seed command>

# 6. Start the app
<run command>
```

## Running tests

```bash
<test command>
```

## Repository layout

> TODO: adjust to the chosen stack.

```
.github/            PR template, issue templates, CI workflows, CODEOWNERS
db/migrations/      Numbered, forward-only SQL migrations
db/seed/            Fake seed data for development and tests
docs/               Data model, conventions, decision records
docs/decisions/     Architecture decision records (ADRs)
```

## How we work

- Process: Scrum, with a mini-SDLC per sprint. See [CONTRIBUTING.md](CONTRIBUTING.md).
- Each developer owns a feature slice end to end (schema, API, UI).
- Schema changes go through numbered migrations only.

## Documentation

- [Data model v2](docs/data-model-v2.md)
- [Conventions](docs/conventions.md) (to be written in Sprint 0)
- [Decision records](docs/decisions/)

## Contacts

- Product owner (clinic): `<name, role at TMC>`
- Tech lead / maintainers: `<names>`
