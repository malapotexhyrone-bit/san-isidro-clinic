# Reading-size adjustment

Installed in `C:\xampp\htdocs\san-isidro-clinic`; verified on 4 October 2026.

## How to use it

Click the **Aa** button beside the blur and light/dark buttons. Use the **Reading size** slider or the **+ / −** buttons to choose 100%, 125%, 150%, 175%, or 200%. **Reset size** restores 100%.

The setting enlarges the main reading area: text, table cells, row spacing, fields, buttons, cards, and their padding. Navigation labels and logout text also enlarge. Confirmation, success, and welcome cards follow the preference. The fixed navigation and appearance toolbar stay reachable. The control is available to signed-in Administrator, Receptionist, Nurse, and Physician users. The login page has no size control and always uses its original size, even when a larger size is saved for the signed-in system.

As the reading area becomes narrower, forms and cards stack vertically. Wide tables scroll within their panels. At very large sizes on narrow screens, the appointment calendar also scrolls within its own area. Enlarged headings wrap instead of extending outside cards, and confirmation buttons stack on small screens.

The preference is saved in this browser under `clinic-reading-size`, restored before paint, and shared with other open pages on the same origin. It is independent of blur and theme preferences. Different browsers, devices, hostnames, and ports retain separate settings. If storage is unavailable, the control still works on the current page and reports that accurately.

Arrow keys adjust the slider; Home/End select the limits. Escape closes the popup and returns focus to Aa. Clicking outside or tabbing out also closes it. The print layout retains its original size.

## Exact application files changed

| File | Change |
| --- | --- |
| `resources/views/layouts/app.blade.php` | Load the new shared assets and place Aa alongside the appearance buttons. |
| `resources/views/auth/login.blade.php` | Removed reading-size assets and the Aa control on 4 October 2026; login retains its original size. |
| `resources/views/components/reading-size-controls.blade.php` | New labelled slider, size display, increase/decrease controls, and reset button. |
| `public/js/reading-size-controls.js` | New live scaling, validated stored values, keyboard/popup behavior, and cross-tab updates. |
| `public/css/reading-size-controls.css` | New control styling, reading-area scaling, responsive container layouts, narrow dialog handling, and print preservation. |
| `docs/READING_SIZE_CONTROLS.md` | This usage guide, exact file manifest, and verification record. |

## Setup

No installation or database migration is needed. Compiled views were cleared. Press **Ctrl+F5** in an already open app page to load the new files. The existing clinic server can remain running.

## Verification

- JavaScript syntax check passed.
- Existing Laravel suite: **48 tests passed, 1,241 assertions**.
- Browser checks used fictional records with an isolated in-memory database and read-only requests.
- Measured enlarged table rows and headings at 125%, 150%, and 200%; Reset returned the row height to its original value.
- Checked increase/decrease buttons, slider limits, disabled boundary buttons, Escape/focus return, refresh persistence, and synchronization between two open pages.
- Checked all four roles, theme changes, and compatibility with the blur control.
- Checked 200% views at 320 and 390 pixels wide, including appointments, appointment verification, walk-ins, patient/user registration, schedules, profiles, and physician consultation.
- Verified wide-table overflow stays inside scrollable panels and enlarged form controls remain inside the page.
- Checked navigation and enlarged confirmation dialogs; both Cancel and Continue fit the narrow screen. A fictional success message also fit with its Okay button accessible.
- Temporary preview tabs and files were removed after checking. The actual clinic server was left running.
