# Login backgrounds and shared glass theme

Applied to the main application at `C:\xampp\htdocs\san-isidro-clinic`.

The supplied `loginlightbg.jpeg` and `loginbgdarkupd.jpeg` are copied unchanged into the public image files below. Login automatically selects the matching background using the existing light/dark theme button and saved theme preference. The login panel is wider, with larger inputs and a centered two-column desktop layout. On phones, the introduction becomes compact and the form uses the available width.

`glass.css` provides shared translucent surfaces, borders, shadows and blur for all Receptionist, Nurse, Physician and Administrator pages, including panels, profile cards, navigation, forms, tables and confirmation dialogs. Each role retains its current background. Status colours and the appointment verification highlight are preserved. Browsers without backdrop blur receive solid fallback surfaces for readability.

No database, authentication, password or appointment rules were changed. Refresh the browser with Ctrl+F5 after installation. No new packages or migrations are needed.

Exactly changed files:

- Modified `public/css/login.css`
- Modified `resources/views/layouts/app.blade.php`
- Replaced `public/images/login-light-bg.jpeg` with the supplied light image
- Replaced `public/images/login-dark-bg.jpeg` with the supplied dark image
- Added `public/css/glass.css`
- Added `docs/GLASS_THEME.md`
