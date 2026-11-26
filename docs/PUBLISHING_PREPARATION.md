# File privacy and publishing preparation

Prepared on 4 October 2026 for `C:\xampp\htdocs\san-isidro-clinic`. The application has not been published. Local XAMPP credentials and local HTTP settings were left in place. Real patient/appointment/consultation records were not edited.

## What is prepared

The project root is denied to web visitors. Apache serves the `public/` entry point and intended assets; directory listings, dotfiles, source/configuration files, database files, logs and backup/archive files are blocked. This was checked against the running local Apache server. Laravel requires a public-directory document root on the eventual server; `.htaccess` does not configure Nginx or the hosting provider for you. [Laravel deployment guidance](https://laravel.com/framework/docs/12.x/deployment).

Uploaded profile photos now use `storage/app/private/profile-photos`. An authenticated `/profile-photos/{user}` endpoint allows the active owner or an active administrator to view them. File containment and actual image contents are checked, and responses disable caching. Two existing photos were moved without changing their contents, filenames or database references. Their previous public URLs are forbidden. The pictures in `public/images` remain public because they are interface artwork, backgrounds and default illustrations.

Login rejects further attempts for 60 seconds after five failed attempts for an account/IP combination. All protected application routes require an active account and a matching password session. Changing/resetting a password invalidates other sessions on their next protected request. Older sessions require one fresh sign-in after this update; no passwords were changed.

## Connections checked

| Connection or data check | Result |
| --- | --- |
| Laravel to local MariaDB/MySQL | Connected; strict SQL mode and foreign-key checks enabled |
| Migrations | All current migrations already applied |
| Database engines | InnoDB for all 18 tables |
| Relationships | 15 foreign keys checked; no orphan relationships found |
| Record consistency | No duplicate active slot groups, missing completion records, or patient/physician assignment mismatches found in current records |
| App routes, views and assets | Registered controller methods resolve; referenced views and local assets exist |
| Functional/security suite | 64 tests, 1,516 assertions passed using isolated SQLite and test mail/session/cache |
| Apache private-file access | Forbidden; public login, health page, CSS, JavaScript and background image still accessible |
| SMTP | Connected and authenticated using TLS 1.3; no email sent |
| Locked PHP dependencies | No known advisories or abandoned packages reported at the time checked |

The implemented flow is receptionist registration/booking/verification/arrival, nurse vital signs, and consultation/completion by the assigned physician. The administrator manages staff, physicians, schedules and reports. Browser requests use Laravel's appointment-availability and administrator password-reveal endpoints. There is currently no chatbot/Odysseus/Ollama integration or separate public API in this application.

The database checks establish connection and consistency of the records inspected; they do not prove every future concurrent operation. SMTP authentication does not prove delivery to a recipient's inbox. These checks are preparation, not a certification of an unconfigured production host.

## Files changed

All paths below are relative to the application folder.

| File | Purpose |
| --- | --- |
| `.htaccess` | Deny direct web access to the private project tree |
| `public/.htaccess` | Permit public entry point/assets and deny sensitive files/listings |
| `.gitignore` | Exclude environment variants, database exports and backups |
| `.env.production.example` | Production settings with placeholders, not real secrets |
| `app/Http/Controllers/AuthController.php` | Login limits and secure session initialization |
| `app/Http/Middleware/EnsureAccountIsActive.php` | Inactive-account and old-session enforcement |
| `bootstrap/app.php` | Register/order active-account middleware |
| `routes/web.php` | Protect routes and add private profile-photo endpoint |
| `app/Http/Controllers/ProfileController.php` | Own profile photo uploads/removals use private storage |
| `app/Http/Controllers/UserController.php` | Administrator photo uploads/removals use private storage |
| `app/Http/Controllers/ProfilePhotoController.php` | Authorized image serving with safe paths and content checks |
| `resources/views/layouts/app.blade.php` | Private avatar URL |
| `resources/views/profile/edit.blade.php` | Private current-photo URL |
| `resources/views/profile/show.blade.php` | Private profile-photo URL |
| `tests/TestCase.php` | Direct test actors emulate fresh login sessions |
| `tests/Feature/AuthenticationSecurityTest.php` | Real-session login, deactivation and password-change regression checks |
| `tests/Feature/PrivateProfilePhotoTest.php` | Private uploads, ownership, MIME and path checks |
| `docs/PUBLISHING_PREPARATION.md` | This preparation guide |
| `README.md` | Link to this publishing guide |

There were also two file moves from `storage/app/public/profile-photos/` to the matching private folder. Backups and the exact move manifest are retained outside the web root in the local project workspace's `connection-check-2026-10-04/backup` and `private-photo-move.json`.

## When you are ready to publish

1. Choose the host/domain and use a maintained, patched PHP runtime compatible with Laravel. Point the web document root only to `<app>/public`; never expose the entire Laravel folder. Apache needs the supplied access rules enabled. Nginx needs equivalent server configuration.
2. Create the actual server `.env` privately, using `.env.production.example` as a checklist. Replace every placeholder. Use HTTPS, `APP_ENV=production`, `APP_DEBUG=false`, secure/HTTP-only session cookies, and the correct public `APP_URL`. Do not apply the HTTPS-only cookie example to your current HTTP local setup.
3. Create a dedicated database account with a strong password and access only to the clinic database. Keep the database port private. Use a separate migration account for schema changes; give the runtime account only the permissions it needs. The current local root/blank-password account must not become the hosted runtime account.
4. Keep database/SMTP credentials and `APP_KEY` outside shared repositories and public directories. Preserve the correct existing `APP_KEY` when carrying over encrypted data. Do not run `key:generate` over an existing dataset without a planned re-encryption procedure. A completely new installation gets its own key. If credentials were previously exposed outside a trusted environment, rotate them with that data/key plan.
5. Replace demo passwords before real hosted use. Review the administrator password-reveal policy: it currently retains encrypted, recoverable staff passwords because that was the requested behavior. Prefer password reset with one-way hashing for production. The current feature is admin-only, rate limited and logged, but it still needs a deliberate security-policy decision; it was not silently removed. [OWASP password storage guidance](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html).
6. Back up the database, private photo folder and deployment secrets securely. Test restoration. Transfer the private photos into the private folder; do not restore their old public copies. Keep uploaded data and writable storage permissions limited to the application service account.
7. Install locked production dependencies, apply pending migrations with the migration account, restore the runtime database configuration, and cache production configuration/routes/views. Use `migrate --force`, not `migrate:fresh` or demo seeding on existing clinic records.
8. Verify the actual host: login/logout and all four role flows, private photos, forbidden `.env`/logs/backups, database connection, HTTPS cookies and a confirmation email to an approved test recipient. Check delivery/backup monitoring and redo dependency advisory checks at deployment time.

Hosting/domain/credentials are not required now. Those values and the live-host verification happen on publishing day. File permissions and application safeguards have been prepared locally; the remaining host settings and password policy must be completed before describing the public deployment as ready for real clinic use.
