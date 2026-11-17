# Testing auth and user registration with Laravel tests

The feature tests in `backend/tests/Feature/Api/V1` exercise the API the way
a client does: they send real HTTP requests to the app and check the status
codes, JSON and database. Reading them is the quickest way to see exactly how
login, tokens and registering logins behave.

To try the same things by hand, see [postman-guide.md](postman-guide.md).

## Run the tests

From `backend/`:

```bash
php artisan test
```

You do not need a database server or a running app. Tests use an in-memory
SQLite database (set in `phpunit.xml`), built fresh for each test, so your
local data is never touched.

Useful variations:

```bash
php artisan test --compact
```

```bash
php artisan test tests/Feature/Api/V1/AuthTest.php
```

```bash
php artisan test --filter=deactivat
```

`--filter` matches part of a test name, so `--filter=deactivat` runs every
test about deactivating users. `composer test` also works; it clears cached
config first, which helps if tests behave as if `.env` changed.

## What is covered

### `AuthTest.php`: logging in and tokens

| Test | Shows that |
|---|---|
| `user_can_log_in_and_receives_a_token` | Login returns a token, the user and their permissions, and records `last_login_at` |
| `wrong_password_is_rejected` | A wrong password gives 422 |
| `unknown_username_gets_the_same_error_as_a_wrong_password` | You cannot tell from the error whether a username exists |
| `login_requires_username_and_password` | Both fields are required |
| `username_is_not_case_sensitive_at_login` | `" Nurse1 "` logs in as `nurse1` |
| `device_name_labels_the_token` | `device_name` becomes the token's name |
| `login_is_throttled_after_ten_attempts_a_minute` | The 11th attempt in a minute gets 429, even with the right password |
| `inactive_user_cannot_log_in` | Deactivated users cannot log in |
| `token_gives_access_to_me_and_logout_revokes_it` | The token works on `/auth/me`; logout deletes it |
| `logout_only_revokes_the_token_it_was_sent_with` | Logging out on one device leaves the others logged in |
| `guest_cannot_reach_protected_routes` | No token gives 401 on auth and user endpoints |
| `guest_gets_a_json_401_even_without_an_accept_header` | Clients that forget `Accept: application/json` still get a JSON 401, not a server error |
| `a_made_up_token_is_rejected` | A forged token gives 401 |
| `me_returns_the_acting_user` | `/auth/me` returns whoever is logged in |
| `deactivated_user_is_locked_out_with_an_existing_token` | Deactivation blocks a token that was already issued |
| `token_expires_after_the_configured_lifetime` | Tokens stop working after `sanctum.expiration` minutes (12 hours) |

### `PasswordTest.php`: your own password and sessions

| Test | Shows that |
|---|---|
| `user_changes_own_password_and_other_devices_are_logged_out` | The current device stays logged in; others are logged out; the new password works |
| `change_requires_the_current_password` | A wrong current password gives 422 |
| `new_password_must_be_strong_and_confirmed` | At least 8 characters with letters and numbers, typed twice |
| `logout_all_revokes_every_token` | "Log out everywhere" deletes every token |

### `UserManagementTest.php`: registering logins and managing access

| Test | Shows that |
|---|---|
| `admin_creates_a_login_for_a_staff_member` | Registering a login returns 201, records who assigned the roles, and the new user can log in with the right permissions |
| `create_validates_input` | Duplicate staff or username, a weak password and unknown roles are all rejected |
| `usernames_are_stored_lowercase_and_unique_regardless_of_case` | `MBanda` is refused when `mbanda` exists; `JPhiri` is saved as `jphiri` |
| `new_user_without_roles_can_log_in_but_has_no_permissions` | Roles are optional; without them the user can do nothing |
| `create_rejects_unknown_staff` | `staff_id` must exist |
| `shows_a_user_and_404s_for_a_missing_one` | Show never returns the password hash |
| `reactivated_user_can_log_in_again` | Deactivation can be undone |
| `reset_password_must_be_confirmed_and_strong` | Admin resets follow the same password rules |
| `user_view_alone_is_read_only` | `user.view` can look but not create, rename or reset |
| `lists_and_searches_users` | Search by username; filter by `is_active` |
| `deactivating_a_user_logs_them_out` | Deactivation deletes their tokens |
| `sync_roles_replaces_roles_and_keeps_unchanged_assignments` | Roles kept keep their original `assigned_by` |
| `admin_resets_a_password_and_old_tokens_stop_working` | A reset logs the user out everywhere |
| `admin_cannot_change_own_access` | Admins cannot deactivate themselves, change their own roles or reset their own password |
| `non_admins_cannot_manage_users` | Other roles get 403 on every user endpoint |
| `users_cannot_be_deleted` | There is no delete (405) |
| `lists_roles_with_permissions` | The role picker lists every seeded role |
| `lists_staff_without_a_login` | The staff picker can show only staff still needing a login |

## How the tests are written

Read one test next to its endpoint in `backend/routes/api.php` and you will
recognise these patterns.

**Fresh database per test.** `use RefreshDatabase;` runs the migrations once
and wraps each test in a transaction that is rolled back afterwards.

**Two ways to be logged in.**

```php
// The real way: log in and use the returned token
$token = $this->postJson('/api/v1/auth/login', [
    'username' => 'nurse1',
    'password' => 'password',
])->json('token');

$this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

// The shortcut, when the test is about something other than logging in
Sanctum::actingAs($user);
$this->getJson('/api/v1/users')->assertOk();
```

Use the real way when tokens matter (logout, expiry, deactivation); use
`Sanctum::actingAs` otherwise.

**`$this->app['auth']->forgetGuards()`** appears between requests that use
different tokens. Laravel remembers the logged-in user for the rest of a test,
so without this the second request would reuse the first user instead of
checking its own token.

**Giving a user permissions.** The factory makes users whose password is
`password`:

```php
AppUser::factory()->create();                                  // no roles
AppUser::factory()->inactive()->create();                      // deactivated
AppUser::factory()->withPermissions('user.view')->create();    // a throwaway role with exactly these permissions
```

`UserManagementTest` instead seeds the real roles
(`$this->seed(AccessControlSeeder::class)`) and attaches the `admin` role, so
it tests the role catalogue the clinic actually uses.

**Moving the clock.** `$this->travel(721)->minutes();` jumps time forward to
test token expiry without waiting.

## Adding a test

Put it in the file for that area, name it as a sentence and check the
response, then the database or a follow-up request:

```php
public function test_receptionist_cannot_create_logins(): void
{
    Sanctum::actingAs(AppUser::factory()->withPermissions('patient.create')->create());

    $this->postJson('/api/v1/users', [
        'staff_id' => Staff::factory()->create()->id,
        'username' => 'someone',
        'password' => 'Welcome123',
    ])->assertForbidden();

    $this->assertDatabaseMissing('app_user', ['username' => 'someone']);
}
```

Then run `php vendor/bin/pint` to format your changes before committing.

## When a test fails

- The failure message shows the response body. A 422 lists the validation
  `errors`; a 500 includes the exception.
- `->dump()` on a response prints it mid-test, e.g.
  `$this->postJson(...)->dump()->assertOk();`
- If many tests fail at once with database errors, check that the
  `pdo_sqlite` PHP extension is enabled (`php -m`).
