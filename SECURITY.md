# Security Policy

The ANC Clinic System stores sensitive personal health information about patients of Thanthwe Medical Centre, including pregnancy, HIV status, mental health and similar records, as well as staff HR and pay data. Treat all of it as confidential.

Health data is sensitive personal data under Malawi's data protection law. Confirm current requirements with the clinic's legal adviser.

## Rules for everyone working on this repository

### Patient and staff data

1. **No real patient or staff data in this repository.** This includes code, tests, seed data, fixtures, migrations, documentation and commit messages.
2. **No real data in dev, test or CI environments.** Use the fake seed data only. If real data is ever needed to reproduce a production problem, get written approval from the product owner and anonymise it first.
3. **No real data in issues, PRs, chat messages or screenshots.** Redact names, phone numbers, IDs and file numbers before sharing.
4. **Logs must not contain clinical details or personal identifiers.** Log record IDs, not names or results.
5. Treat these as restricted: HIV status, mental health, and staff pay and account data. Access is limited by role.

### Secrets

1. Never commit passwords, API keys, tokens, database URLs or SMS provider credentials.
2. Use `.env` locally (it is git-ignored). Commit only `.env.example` with dummy values.
3. In CI and hosting, use the platform's secret store.
4. Passwords are only ever stored as hashes. Never store plain text.

### Accounts and access

1. Two-factor authentication is **required** on every GitHub account with access to this repository.
2. Keep the repository **private**.
3. Give people the minimum access they need. Remove access when someone leaves the team.
4. Application users get a role (Admin, Clinician, Nurse/Midwife, Lab, Reception, HR, Read-only). Permissions are checked on the server, not only in the UI.

### Messaging

SMS reminders are only sent to patients who have given the "SMS reminders" consent. Message text must not contain clinical detail. Example: "You have an appointment at TMC on Monday at 9:00."

## If something goes wrong

If you accidentally commit a secret or real patient data, or you suspect a leak:

1. **Tell the team immediately** in the team chat. Do not try to quietly fix it.
2. **Do not just delete it in a new commit.** Git history keeps it. The maintainers will remove it from history and force the cleanup.
3. **Rotate any exposed secret** straight away (change the password or key).
4. Record what happened, what was exposed, and what was done. If real patient data was exposed, the product owner decides whether the clinic and the affected patients must be told.

## Reporting a vulnerability

If you find a security problem, do not open a public issue.

- Use GitHub's **private vulnerability reporting** (Security tab, "Report a vulnerability"), or
- Email: `<security contact email>`

We aim to acknowledge reports within 2 working days.

## Automated protections

These should be enabled on the repository:

- Branch protection on `main` (PR, review and passing CI required)
- Secret scanning and push protection
- Dependabot alerts and security updates
- A secret-detection check in CI or a pre-commit hook (for example gitleaks)
