# Contributing

This is how the three of us work together. Keep it short, follow it, and change it through a PR when the team agrees it should change (usually after a retro).

## 1. Principles

- Each developer owns a **feature slice end to end** (schema, API, UI, tests). We split work by slice, not by layer.
- `main` is always deployable. Nothing lands on `main` without a PR, a review and green CI.
- Small PRs, merged often. Aim for under a day of work per PR.
- Shared things (database schema, shared UI components, auth) are changed deliberately and announced.

## 2. Workflow

1. Pick a ticket from **Ready** on the project board and move it to **In progress**.
2. Branch off the latest `main`:
   ```bash
   git switch main && git pull
   git switch -c feat/ANC-12-register-patient
   ```
3. Commit as you go. Push early so others can see progress.
4. Open a PR using the template. Link the ticket (`Closes #12`).
5. Get **one approval** from a developer who does **not** own your slice. CI must pass.
6. **Squash-merge**. The branch is deleted automatically.
7. Move the ticket to **Done** only when it meets the Definition of Done below.

### Branch names

`<type>/ANC-<ticket>-<short-description>`

| Type | Use for |
|---|---|
| `feat` | New functionality |
| `fix` | Bug fix |
| `chore` | Tooling, config, dependencies |
| `docs` | Documentation only |
| `refactor` | Behaviour-preserving cleanup |

### Commit and PR titles

Short, imperative, with ticket ID: `ANC-12: Add patient registration form`.

## 3. Reviews

- Review within **one working day**. If you can't, say so and ask someone else.
- Review for: correctness, tests, security (data exposure, permissions, injection), consistency with `docs/conventions.md`, and whether the PR does what the ticket says.
- Don't argue about personal style. If it matters, it belongs in the linter or the conventions doc.
- Be specific and kind. Prefix non-blocking comments with `nit:`.
- Authors resolve all comments (or discuss them) before merging. Never merge your own PR without approval.

## 4. Database migrations

The database schema is the source of truth for the data model.

- Migrations live in `db/migrations/`, numbered and **forward-only**: `0002_add_patient_index.sql`.
- **Never edit a migration that has been merged.** Add a new one.
- Every migration must apply cleanly to an empty database and to the current state. CI checks this.
- Changes to **shared tables** (`patient`, `pregnancy`, `anc_visit`, lookups) must be **announced in the team chat before you write the migration**, so the whole team can object or adjust. The migration PR then needs review from a teammate (CODEOWNERS requests it automatically and blocks the merge until one owner approves). Link the chat discussion in the PR if there was one.
- Number clash (two PRs both create `0005`)? Whoever merges second renumbers.
- Keep triggers, views and constraints in migrations too, not applied by hand.

## 5. Definition of Ready (a story may enter a sprint when...)

- [ ] It is written as a user story with testable acceptance criteria.
- [ ] Schema impact is identified (tables touched, new migration needed or not).
- [ ] API endpoints/contract are sketched (request/response).
- [ ] Any clinical question is answered by the product owner, or the story is split so it doesn't depend on it.
- [ ] It fits within one sprint for one developer. If not, split it.

## 6. Definition of Done (a story is finished when...)

- [ ] All acceptance criteria are demonstrated.
- [ ] Code reviewed and squash-merged to `main`.
- [ ] CI passes (lint, tests, migrations from scratch, build).
- [ ] Tests cover the new behaviour, including failure cases.
- [ ] Permissions and audit logging are in place where the data is sensitive.
- [ ] API spec and docs updated.
- [ ] No known critical bugs. Remaining issues are logged as tickets.
- [ ] No real patient data in code, seed data, screenshots or logs.
- [ ] Demonstrated at sprint review (or accepted by the product owner).

## 7. Sprint rhythm

| Event | When | Purpose |
|---|---|---|
| Refinement | Before the sprint | Turn backlog items into Ready stories |
| Planning + design kickoff | Day 1 | Choose stories, confirm schema and API contract, break into tasks |
| Daily standup (10 min) | Daily | What I finished, what I'm doing, what shared thing I'm about to change, blockers |
| Review / demo | Last day | Show working software to the product owner |
| Retro | After review | Improve how we work |

## 8. Data and security reminders

- Fake data only in dev and test. See [SECURITY.md](SECURITY.md).
- Secrets never go in the repo. Use `.env` locally (git-ignored) and the secret store in CI and hosting.
- Dates and times are stored as UTC (`timestamptz`) and displayed in Africa/Blantyre.
- Phone numbers are stored as text, never numbers.

## 9. Getting help

Blocked for more than an hour? Say so in the team chat. Stuck on a decision that affects others? Raise it at standup, or write a short ADR in `docs/decisions/`.
