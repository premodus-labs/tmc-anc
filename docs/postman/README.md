# Testing the API with Postman

`tmc-anc-api.postman_collection.json` exercises auth, user administration and
patients end to end. Every request has tests, and login requests save the
bearer token for the requests after them.

## Run it

1. Start the API against a freshly seeded database (fake data only):

   ```bash
   cd backend
   php artisan migrate:fresh --seed
   php artisan serve
   ```

2. In Postman: **Import** → choose `tmc-anc-api.postman_collection.json`.
3. Open the collection → **Run** → keep the default order → **Run TMC ANC API**.

The `base_url` collection variable defaults to `http://127.0.0.1:8000`; change
it under the collection's **Variables** tab if your server runs elsewhere.

From the command line, the same run is:

```bash
npx newman run docs/postman/tmc-anc-api.postman_collection.json
```

## What the run checks

| Folder | Checks |
|---|---|
| 1. Auth | Admin login saves a token; `me` returns permissions; wrong password gives 422; no token gives 401 |
| 2. User admin | List roles and free staff; create a login; search; change roles; reset password; admin cannot deactivate themselves (403) |
| 3. Permissions | Log in as the new nurse/midwife: can list patients, cannot register one or list users (403); changes own password |
| 4. Patients | Register, show, update and search a patient as admin |
| 5. Deactivation and logout | Deactivating the nurse kills their token at once and blocks login; logout revokes the admin token |

Each run creates a new user from one of the seeded staff without a login. After
five runs there are none left; run `php artisan migrate:fresh --seed` again.

## Dev logins

All seeded logins use the password `password`:
`admin`, `receptionist`, `nurse_midwife`, `clinician`.

## Endpoints

| Method | Path | Permission |
|---|---|---|
| POST | `/api/v1/auth/login` | none (10 per minute per IP) |
| GET | `/api/v1/auth/me` | any logged-in user |
| POST | `/api/v1/auth/logout` | any logged-in user |
| POST | `/api/v1/auth/logout-all` | any logged-in user |
| PUT | `/api/v1/auth/password` | any logged-in user |
| GET | `/api/v1/users`, `/users/{id}`, `/roles`, `/staff` | `user.view` |
| POST | `/api/v1/users` | `user.manage` |
| PATCH | `/api/v1/users/{id}` | `user.manage`; changing `is_active` not on yourself |
| PUT | `/api/v1/users/{id}/roles` | `user.manage`, not on yourself |
| PUT | `/api/v1/users/{id}/password` | `user.manage`, not on yourself |
| GET | `/api/v1/patients`, `/patients/{id}` | `patient.view` |
| POST | `/api/v1/patients` | `patient.create` |
| PATCH | `/api/v1/patients/{id}` | `patient.update` |

Full request and response shapes are in the generated OpenAPI docs at
`http://127.0.0.1:8000/docs/api`.
