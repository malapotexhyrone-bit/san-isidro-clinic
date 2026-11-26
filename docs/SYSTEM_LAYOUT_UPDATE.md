# Shared clinic layout and admin backgrounds

Applied to the active app: `C:\xampp\htdocs\san-isidro-clinic`.

## Result

All authenticated clinic pages use the compact appointment/walk-in layout: a centered content area up to 960px wide, consistent glass cards, smaller headings and fields, compact filters, and clean record tiles. Patient, staff, physician, schedule, vital-sign, consultation and profile forms use numbered sections. Save controls are grouped at the bottom. Queues, lists, dashboard and reports receive the same spacing and card styling through the shared layout.

The Administrator background uses the supplied images in light and dark mode. Other roles retain their own background images. Count/status/availability badges, profile badges, the workspace status panel and login information badges use rose glass backgrounds instead of opaque gray or green blocks. These shared styles apply to Administrator, Receptionist, Nurse and Physician access. Their text still identifies the status. Phone screens stack form fields and contain horizontal scrolling inside wider tables. Print styling uses readable neutral colors.

## Exact files added or changed

1. `public/images/admin-light-bg.jpeg` — copy of `C:\Users\Admin\Documents\CLINICIMAGEBG\adminligtbg.jpeg`.
2. `public/images/admin-dark-bg.jpeg` — copy of `C:\Users\Admin\Documents\CLINICIMAGEBG\admindarkbg.jpeg`.
3. `public/css/admin.css` — Administrator theme backgrounds.
4. `public/css/system-layout.css` — shared compact layout, responsive sizing, rose glass badges and workspace status panel, and print styles.
5. `resources/views/layouts/app.blade.php` — shared stylesheet and content wrapper for all staff roles.
6. `resources/views/components/workflow-heading.blade.php` — reusable numbered section heading.
7. `resources/views/patients/form.blade.php` — patient information, contact details and emergency contact sections.
8. `resources/views/users/form.blade.php` — staff information, picture, access/security sections and save footer.
9. `resources/views/physicians/form.blade.php` — physician information and linked account/availability sections.
10. `resources/views/schedules/index.blade.php` — grouped schedule creation form.
11. `resources/views/visits/_vital-signs-form.blade.php` — compact measurements and nursing notes sections.
12. `resources/views/visits/show.blade.php` — grouped consultation fields and save footer, retaining draft/completion actions.
13. `resources/views/profile/edit.blade.php` — numbered information/access/password sections; removed obsolete picture placeholder text.
14. `public/css/login.css` — matching rose glass backgrounds for Secure staff access and Clinic-ready workflow.
15. `docs/SYSTEM_LAYOUT_UPDATE.md` — this change record.

Existing appointment/walk-in templates receive the shared layout through the app wrapper. Their separate stylesheets were not changed. No controller, route, database schema, credentials, authentication or authorization code was changed. Login retains its existing dedicated layout with matching badge backgrounds; appointment email templates were not changed.

## Verification

- Existing automated suite: **48 tests passed, 1241 assertions** after the final form grouping changes. Includes form actions/internal links for 53 pages across four roles, patient/appointment/walk-in workflows, draft/completion handling, ownership, role checks and password management.
- Browser verification used fictional records in a temporary in-memory database, with isolated sessions, cache, mail and compiled views. Real clinic records were not modified.
- Inspected Administrator light/dark backgrounds, count/availability badges and expanded Administration center panel; patient registration, staff/physician forms, schedules, profiles, queues and reports; Nurse vital-sign form; Physician consultation; Receptionist appointment controls.
- Verified all four roles use identical dark queue badge/workspace backgrounds and identical light profile badge/workspace backgrounds. Visually checked the Nurse queue and both login themes, including Secure staff access and Clinic-ready workflow.
- Verified missing patient fields focus the first error, consultation completion highlights required notes, and the draft button opens its confirmation while those fields are blank. Save confirmation was cancelled in the browser preview; persistence is covered by the automated tests.
- Verified phone layouts at 390px width for profile, consultation, reports and patient form, with no page-wide horizontal overflow. Checked the compact appointment email row and password-eye spacing.
- No physical print job was submitted. Print styles were preserved, including readable badge colors.
- Temporary preview was closed and removed after verification.

## Manual steps

No migration, dependency installation or additional configuration is required. Press **Ctrl+F5** in the clinic browser to refresh cached styles. Use the theme button to see the matching light or dark Administrator background.
