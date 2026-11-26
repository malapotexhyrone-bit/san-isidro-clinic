# Password controls and administrator access

The main application is `C:\xampp\htdocs\san-isidro-clinic`.

Staff use **My Profile → Edit profile → Password security**. Current password, new password and confirmation fields have eye buttons. Saving requires the correct current password and matching confirmation. Administrators can also reset passwords through **User Management → Edit**. A blank new password leaves the existing password unchanged. Login has the same show/hide control.

Administrators can reveal the latest saved password on a staff profile or the Edit User page. This is an explicit administrator feature: passwords are recoverable by the application. Login continues to use a one-way hash, and a separate authenticated encrypted copy is stored in `users.password_ciphertext`. Both values update together through the User model when a staff member or administrator changes a password. Serialized users and normal pages exclude both values. A POST endpoint checks the authenticated active administrator role, uses CSRF protection and a 10-per-minute limit, returns no-store responses, verifies the decrypted copy against the current hash, and logs administrator/target IDs without the password. Each click fetches the current value; closing, leaving the page or waiting 30 seconds clears the visible copy. Use HTTPS when hosting outside this local development machine.

Existing hashes cannot be reversed. Older accounts show an explanation until the staff member or administrator saves a new password. Existing credentials are not reset or guessed. A direct database hash update makes any old encrypted copy unavailable rather than displaying an incorrect password.

Keep the application's existing `APP_KEY` with secured backups. It is needed to decrypt saved copies. If the key is lost or a copy is damaged, an administrator must set a new password. Do not regenerate the key to activate this feature.

## Setup and checks

The additive migration has been applied to the main application. It only adds a nullable encrypted-copy column and does not alter existing hashes. For another installation, apply this migration:

```cmd
C:\xampp\php\php.exe artisan migrate --path=database/migrations/2026_10_03_000001_add_password_ciphertext_to_users_table.php
C:\xampp\php\php.exe artisan view:clear
```

Run the test suite with `C:\xampp\php\php.exe artisan test`. Tests use an isolated in-memory SQLite database, including staff/admin changes, login with the new password, rejection of old passwords, permissions, invalid changes, legacy accounts, encrypted storage and throttling.

## Exact changed files

Modified:

- `app/Models/User.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/UserController.php`
- `routes/web.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/profile/edit.blade.php`
- `resources/views/profile/show.blade.php`
- `resources/views/users/form.blade.php`

Added:

- `app/Http/Controllers/PasswordRevealController.php`
- `database/migrations/2026_10_03_000001_add_password_ciphertext_to_users_table.php`
- `resources/views/components/password-input.blade.php`
- `resources/views/components/admin-password-reveal.blade.php`
- `public/css/password-controls.css`
- `public/js/password-controls.js`
- `tests/Feature/PasswordManagementTest.php`
- `docs/PASSWORD_MANAGEMENT.md`
