# San Isidro Clinic Appointment and Patient Record Management System

A Laravel 12 and MySQL clinic system based on the approved paper and wireframes. It supports Receptionist, Nurse, Physician, and Administrator workspaces with server-side role checks.

## Open the application

Start MySQL in XAMPP, then run the Laravel development server from Command Prompt:

```cmd
cd /d C:\xampp\htdocs\san-isidro-clinic
C:\xampp\php\php.exe artisan serve
```

Then visit:

`http://127.0.0.1:8000`

## User accounts

| Role | Assigned physician | Username | Password |
| --- | --- | --- | --- |
| Receptionist | Both physicians | `reception` | `reception123` |
| Nurse | All clinic visits | `nurse` | `nurse123` |
| Physician | Dr. Rafael Reyes | `physician1` | `physician123` |
| Physician | Dr. Angela Lim | `physician` | `physician1234` |
| Administrator | Full system access | `admin` | `Clinic123!` |

The single receptionist can register patients and create appointments or walk-ins for either physician. Once the receptionist selects a physician, that patient information is routed to the selected physician. Each physician can see only patients, appointments, and visits assigned to their own physician record. Administrators retain full visibility.

The login screen provides a username dropdown for the five clinic accounts, so staff only need to select their account and enter the password. Each physician also has a read-only My Appointments page with Needs Review, Clinic Day, and Upcoming Schedule groups for their own assigned patients.

## Included workflows

- Patient registration, search, update, profile, and visit history
- Physician records, account linking, schedules, availability, and deactivation
- Appointment creation, conflict checking, rescheduling with history, cancellation, arrival, and no-show
- Exact appointment statuses: Scheduled, Arrived, Completed, Cancelled, Rescheduled, No-show, Walk-in
- Walk-in visits that never create, reserve, or overwrite appointment slots
- Nurse vital signs and physician consultation/completion workflows
- Date-range daily/monthly reports and physician appointment counts
- User creation, role assignment, activation, and deactivation

## Database

The configured XAMPP database is `san_isidro_clinic`, using the default local `root` account with no password. To rebuild the demo database:

```text
C:\xampp\php\php.exe artisan migrate:fresh --seed
```

## Tests

```text
C:\xampp\php\php.exe artisan test
```

The feature suite verifies role access, conflict rejection, rescheduling history, walk-in isolation, arrival, vital signs, and consultation completion.

## Publishing preparation

Private project files and uploaded profile photos are protected. Before hosting the system, follow [the publishing guide](docs/PUBLISHING_PREPARATION.md) and use [.env.production.example](.env.production.example) for the production settings. Local XAMPP credentials remain local; configure HTTPS and a dedicated database account on the actual host. Existing sessions require one fresh sign-in after the security update.
