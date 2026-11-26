# Compact appointment booking

Applied to `C:\xampp\htdocs\san-isidro-clinic`.

Booking follows three sections: Patient, Date, Time. Patient and physician lookups share a row, with a compact confirmation-email line underneath. The physician is still required because availability is specific to that physician. A small month calendar sits beside a compact grid of available times. On phones, the date and time sections stack in order. Unavailable dates are disabled; occupied and elapsed times are omitted. Long time lists scroll inside the time panel.

Changing the patient, physician or month clears the selected date and time. Previous input is restored only if the slot is still available. Late availability responses cannot overwrite newer choices. The clinic supplies date boundaries in Philippine time; existing authentication, booking checks and confirmation emails are unchanged. The existing field-level error feedback is retained.

Changed files:

- `resources/views/appointments/form.blade.php`
- `public/js/form-validation.js`
- `tests/Feature/ClinicWorkflowTest.php`

Added files:

- `public/css/appointment-booking.css`
- `public/js/appointment-booking.js`
- `docs/APPOINTMENT_BOOKING_DESIGN.md`

Refresh the browser with Ctrl+F5. No migration, package installation or configuration changes are required.
