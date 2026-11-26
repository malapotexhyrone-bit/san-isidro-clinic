# Field-level validation

Installed in `C:\xampp\htdocs\san-isidro-clinic`.

Missing or invalid information is highlighted beside the relevant input with a specific correction message. The page focuses and scrolls to the first invalid field. Closed rescheduling sections are opened automatically. Local checks run before the existing save confirmation and prevent an invalid form from being submitted. Corrections clear the associated highlight. Server checks remain authoritative and receive the same password and ownership protections as before.

Password confirmation errors are now attached to `password_confirmation`, rather than the new password. Incorrect current passwords have a specific explanation. Login failures highlight the password and ask the user to check both the selected account and password without revealing whether an account exists. Password values remain excluded from flashed input.

Shared feedback supports forms throughout the application, including registration, bookings, profiles and administration. Hidden record IDs map to their visible lookup fields. A hidden form identifier maps errors to the submitted form when a page contains several forms with the same field names. Non-field errors retain a visible summary. Without JavaScript, native browser validation and the server summary remain available.

Refresh with Ctrl+F5. No migrations or packages are required.

Modified files:

- `app/Http/Controllers/AuthController.php`
- `app/Http/Controllers/ProfileController.php`
- `app/Http/Controllers/UserController.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/ClinicWorkflowTest.php`
- `tests/Feature/PasswordManagementTest.php`

Added files:

- `resources/views/components/validation-feedback.blade.php`
- `public/css/form-validation.css`
- `public/js/form-validation.js`
- `tests/Feature/FormValidationTest.php`
- `docs/FORM_VALIDATION.md`
