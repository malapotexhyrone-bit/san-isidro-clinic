# Measurement manifest

## Environment

- Date/time zone: 2026-11-05, Asia/Manila
- Source folder: `C:\xampp\htdocs\san-isidro-clinic`
- Test server: Laravel development server at `http://127.0.0.1:8765`
- Test database: isolated SQLite file, freshly migrated and seeded
- PHP: 8.2.12
- Laravel: 12.69.2
- Node: 24.19.0
- npm: 11.17.0
- Vite: 7.3.7
- Final `composer.lock` SHA-256: `77F08CEC31B2882903F3701CD9942DF8521E91D24B0E39D0BD482F71E09F94D1`
- Final `package-lock.json` SHA-256: `C5C3C11526998AFB7D51222B4C9357F4CECB1D2B8C362E6F78E31C3B3023B8E5`

## Fixed benchmark protocol

- Five serial, warm local HTTP requests per measured route.
- Same seeded Administrator account and `/dashboard` route for the comparable authenticated timing.
- Median and min-max retained; no outliers removed.
- No concurrent or sustained load.
- Provisional local budget: median below 500 ms. This is not a production SLO.

## Commands

```text
C:\xampp\php\php.exe artisan test --do-not-cache-result
npm.cmd install
npm.cmd run build
composer audit --locked --no-interaction
composer update symfony/http-foundation symfony/http-kernel symfony/mime symfony/routing symfony/yaml --with-all-dependencies --no-interaction
npm.cmd audit --audit-level=low
```

The HTTP timing used PowerShell `Invoke-WebRequest`, one warm-up/authentication sequence, and five serial requests timed with `Diagnostics.Stopwatch`.

## Repeat procedure

1. Create a new empty SQLite file outside the real project database.
2. Set `DB_CONNECTION=sqlite` and `DB_DATABASE` to that file; run `artisan migrate:fresh --seed --force`.
3. Start `artisan serve` on an unused localhost port with array cache, cookie session, array mailer, stderr logging, and an isolated compiled-view directory.
4. Run the full tests and production build.
5. Audit Composer and npm locked dependencies.
6. Repeat the role journeys in `coverage-matrix.md` without mutating real records.
7. Authenticate the seeded Administrator and run five serial `/dashboard` requests, retaining every timing.

## Comparability notes

- Dashboard timings are comparable: same route, account, server, dataset, sample count, and serial workload.
- Timing ranges overlap; the small median decrease is reported as No clear change.
- Login-page timing was not used for a before/after claim because the first baseline script followed an authenticated redirect and therefore measured the dashboard body.
- Real MySQL, SMTP, HTTPS proxy/CDN, and production host behavior remain outside this measurement.

