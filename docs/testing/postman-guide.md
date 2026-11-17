# Testing auth and user registration with Postman

This guide walks through logging in, creating ("registering") a staff login
and managing access by hand in Postman, so you can see each response for
yourself. The ready-made collection is
[`docs/postman/tmc-anc-api.postman_collection.json`](../postman/tmc-anc-api.postman_collection.json).

For the automated test suite, see [laravel-tests-guide.md](laravel-tests-guide.md).

## How auth works in this API

- There is **no public sign-up**. Staff records come from HR; an administrator
  "registers" a staff member by creating a login for them (`POST /users`).
- You log in with `username` and `password` and get back a **bearer token**
  (Laravel Sanctum). Send it on every other request as
  `Authorization: Bearer <token>`.
- A token lasts **12 hours** (`SANCTUM_EXPIRATION`, in minutes). Each login
  creates a new token, so a user can be logged in on several devices.
- What you may do comes from your **roles**, each of which grants
  **permissions** such as `patient.view` or `user.manage`. `GET /auth/me`
  shows your permission codes.
- Usernames are not case-sensitive. They are stored in lowercase, so
  `Nurse1` and `nurse1` are the same login.
- Deactivating a user, resetting their password or "log out everywhere" kills
  their tokens straight away.

## 1. Set up

Start the API against a freshly seeded database. This deletes and rebuilds
your local database with fake data only.

```bash
cd backend
php artisan migrate:fresh --seed
php artisan serve
```

Already have a database seeded before user admin was added? You can add the
new `user.view` and `user.manage` permissions without wiping it:

```bash
php artisan db:seed --class=AccessControlSeeder
```

Seeded logins (password `password` for all): `admin`, `receptionist`,
`nurse_midwife`, `clinician`. Five staff members have no login yet, ready for
you to register.

## 2. Import the collection

1. In Postman choose **Import** and pick
   `docs/postman/tmc-anc-api.postman_collection.json`.
2. Open the collection's **Variables** tab. `base_url` defaults to
   `http://127.0.0.1:8000`; change it if your server runs elsewhere.
3. Look at the collection's **Authorization** tab: it is set to
   *Bearer Token* with `{{token}}`. Every request inherits this, and the login
   requests save their token into `token` for you.

The collection also adds `Accept: application/json` to every request. The API
answers in JSON without it, but it is a good habit for any Laravel API.

## 3. Run everything at once

Open the collection, choose **Run**, keep the default order and run it. You
should see every test pass. From a terminal the same run is:

```bash
npx newman run docs/postman/tmc-anc-api.postman_collection.json
```

| Folder | What it proves |
|---|---|
| 1. Auth | Admin login saves a token; `me` lists permissions; a wrong password gives 422; no token gives 401 |
| 2. User admin | Lists roles and staff without a login; registers a new login; searches; changes roles; resets the password; admin cannot deactivate themselves (403) |
| 3. Permissions | Logs in as the new nurse: can list patients, cannot register a patient or list users (403); changes their own password |
| 4. Patients | Registers, shows, updates and searches a patient as admin |
| 5. Deactivation and logout | Deactivating the nurse kills their token at once and blocks login; logout revokes the admin token |

Each run registers one of the seeded staff, so after five runs none are left
and "List staff without a login" fails. Run `php artisan migrate:fresh --seed`
again.

## 4. Walk through it by hand

Send these one at a time and read each response. All paths start with
`{{base_url}}/api/v1`.

### Log in

`POST /auth/login`

```json
{ "username": "admin", "password": "password", "device_name": "postman" }
```

Expect **200** with a `token` and a `user` holding `permissions`. In the
collection the token is saved for you; by hand, copy it into the `token`
collection variable. `device_name` is optional and just labels the token.

Try also:

- the wrong password, or a username that does not exist: both give the same
  **422** "These credentials do not match our records", so nobody can find out
  which usernames exist;
- `"username": "ADMIN"`: still logs in;
- more than 10 attempts in one minute from the same machine: **429 Too Many
  Requests**. Wait a minute.

### Who am I

`GET /auth/me` → **200** with your username, staff name and permissions.
Remove the token (Authorization → *No Auth*) and send it again → **401**.

### Register a staff member (create a login)

1. `GET /staff?has_login=0` → staff who have no login yet. Note an `id`.
2. `GET /roles` → the role codes you can give, with their permissions.
3. `POST /users`

   ```json
   {
     "staff_id": 6,
     "username": "mbanda",
     "password": "Welcome123",
     "roles": ["receptionist"]
   }
   ```

   Use a `staff_id` from step 1. Expect **201** with the new user, their staff details and roles. The
   password is never returned.

Rules you can try breaking (each gives **422** with an `errors` object):

| Field | Rule |
|---|---|
| `staff_id` | Must exist, and that staff member must not already have a login |
| `username` | 3–50 characters: letters, numbers, `-` and `_`; unique, ignoring case |
| `password` | At least 8 characters with letters and numbers |
| `roles` | Optional list of role codes from `GET /roles`; leave it out and the user can log in but do nothing |

4. Log in as the new user with `POST /auth/login`. Their `permissions` match
   the roles you gave them.

### Manage the login

As admin:

| Request | Body | Result |
|---|---|---|
| `GET /users?search=mban` | | Finds by username or staff name; add `is_active=0` for deactivated users |
| `PUT /users/{id}/roles` | `{ "roles": ["nurse_midwife"] }` | Replaces their roles exactly; `[]` removes all |
| `PUT /users/{id}/password` | `{ "password": "Reset12345", "password_confirmation": "Reset12345" }` | **204**; their existing tokens stop working |
| `PATCH /users/{id}` | `{ "is_active": false }` | Deactivates; their tokens stop working at once and they cannot log in |
| `PATCH /users/{id}` | `{ "is_active": true }` | Reactivates; they can log in again |
| `PATCH /users/{id}` | `{ "username": "m.banda" }` | 422: dots are not allowed; try `m_banda` |

An admin cannot change their **own** roles, active state or password through
these endpoints (**403**), so they cannot lock themselves out. They change
their own password with `PUT /auth/password` like everyone else.

There is no `DELETE /users/{id}` (**405**): users are deactivated, never
deleted, because clinical records point at them.

### Your own account

| Request | Body | Result |
|---|---|---|
| `PUT /auth/password` | `{ "current_password": "password", "password": "NewSecret123", "password_confirmation": "NewSecret123" }` | **204**; your other devices are logged out, this token keeps working. Limited to 5 tries a minute |
| `POST /auth/logout` | | **204**; only this token is revoked |
| `POST /auth/logout-all` | | **204**; every token you hold is revoked |

### See permissions at work

Log in as `nurse_midwife` and try `GET /patients` (**200**),
`POST /patients` (**403**) and `GET /users` (**403**). Log in as
`receptionist` and `POST /patients` works.

## Status codes you will see

| Code | Meaning here |
|---|---|
| 200 / 201 | Worked / created |
| 204 | Worked, nothing to return (logout, password changes) |
| 401 | No token, a wrong or expired token, or the user was deactivated |
| 403 | Logged in, but your roles do not allow this, or it is your own account |
| 404 | No such record |
| 405 | Method not allowed, e.g. deleting a user |
| 422 | Validation failed or wrong credentials; see `errors` |
| 429 | Too many attempts; wait a minute |

## Troubleshooting

- **403 on `/users`, `/roles` or `/staff` as admin:** your database was seeded
  before the `user.*` permissions existed. Run
  `php artisan db:seed --class=AccessControlSeeder`.
- **HTML instead of JSON:** check the URL starts with `/api/v1`. Only API
  paths answer in JSON.
- **401 straight after logging in:** check the request's Authorization tab is
  *Inherit auth from parent*, and that `token` holds the new token.
- **Everything 401 after running the collection:** its last folder logs the
  admin out. Send "Login as admin" again.

Full request and response shapes are in the generated OpenAPI docs at
`http://127.0.0.1:8000/docs/api` while the server is running.
