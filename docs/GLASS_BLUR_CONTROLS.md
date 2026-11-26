# Shared background blur control

Applied to the active application at `C:\xampp\htdocs\san-isidro-clinic` on 3 October 2026.

## Use

Click the frosted-pane icon beside the light/dark button. Move **Background blur** toward **Frosted** to soften the image behind cards and improve text visibility. The range is 0–40 pixels; **Reset blur** restores 22 pixels. Text stays sharp. Stronger blur also strengthens the panel tint.

The control is present on the login page and every page using the shared Administrator, Receptionist, Nurse, and Physician layout. It affects statistic cards, form/table panels, page headers, profile cards, navigation, badges, alerts, and confirmation surfaces. Existing padding and borders are retained.

The value is stored in this browser under `clinic-glass-blur`, applies before the first paint, persists across refreshes and theme changes, and updates other open pages on the same origin. Different browsers, devices, hostnames, or ports keep separate preferences. Storage failures do not stop the slider from working on the current page.

Use the arrow keys for fine adjustment, Home/End for the limits, and Escape to close and return focus to the icon. Clicking outside or tabbing out closes the popup. The popup fits small screens. Print layouts retain their existing opaque paper backgrounds and hide the controls. Existing fallbacks for browsers without backdrop-filter remain intact.

The shared stylesheet releases the completed entrance-animation layers by using backwards animation fill. Those layers previously prevented nested glass panels from sampling the page background. Entrance animations still play.

## Exact application files changed

| File | Change |
| --- | --- |
| `resources/views/layouts/app.blade.php` | Load shared appearance assets and show the control beside the staff theme button. |
| `resources/views/auth/login.blade.php` | Load the same assets and group the control with the login theme button. |
| `resources/views/components/glass-controls.blade.php` | New shared icon, labelled slider, value display, and reset button. |
| `public/js/glass-controls.js` | New live adjustments, validated preferences, persistence, cross-tab synchronization, and keyboard/popup behavior. |
| `public/css/glass-controls.css` | New clinic-themed controls and shared blur/tint rules, mobile layout, animation-layer fix, and print handling. |
| `docs/GLASS_BLUR_CONTROLS.md` | This usage, file manifest, and verification record. |

No database migration or package installation is needed. Compiled Blade views were cleared. Refresh an already open app page with **Ctrl+F5** to pick up the changes; the existing server can remain running.

## Verification

- Existing Laravel suite: **48 tests passed, 1,241 assertions**.
- JavaScript syntax check passed.
- Browser checks used fictional records in an isolated in-memory database, with read-only preview requests.
- Confirmed live card blur at 0, 22, and 40 pixels, stronger panel tint, and sharp foreground text.
- Confirmed refresh persistence, theme continuity, cross-role/page persistence, and updates across two open tabs.
- Checked Administrator, Receptionist, Nurse, Physician, and login; representative appointments, walk-ins, profiles, schedules, and patient-visit pages.
- Checked Reset, arrow keys, Home/End, Escape and focus return, and closing by clicking the theme button outside the popup.
- Confirmed the popup fits staff and login screens at 320 and 390 pixels wide.
- Confirmed the previous white-strip fix remains intact after entrance animations finish.

Temporary preview files and preview tabs were removed after verification. The actual clinic server and clinic records were not used for UI test transactions.
