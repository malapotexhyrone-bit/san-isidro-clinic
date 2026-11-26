# San Isidro Clinic QA, security, and benchmark report

Run date: 2026-11-05 (Asia/Manila)

## Summary

The Laravel 12 application was tested locally with an isolated, freshly migrated and seeded SQLite database. Source review, the existing automated suite, low-rate HTTP checks, dependency audits, and browser journeys for Receptionist, Nurse, Physician, and Administrator were included.

Two confirmed security findings were remediated:

1. Eight current Symfony advisories affected five locked packages. The affected components were upgraded to patched 7.4 releases. Final Composer and npm audits report no known advisories.
2. Dynamic responses lacked core browser security headers and no-store caching. A global Laravel middleware now provides CSP, anti-framing, MIME-sniffing, referrer, permissions, and private no-store controls. Two regression tests cover public and authenticated responses.

Final automated result: **67 tests passed with 1,543 assertions**. The production asset build passed. Browser route and role checks passed with no observed console warnings or errors.

Release assessment: **Hold for production use**. Local fixes are verified, but the intentional administrator password-reveal design keeps recoverable staff passwords and needs an explicit product-owner risk decision or replacement with a reset-only workflow. HTTPS/hosting controls and the real MySQL deployment were not verified in this run.

## Scope and methods

- Project: `C:\xampp\htdocs\san-isidro-clinic`
- Runtime: PHP 8.2.12, Laravel 12.69.2, Node 24.19.0, npm 11.17.0
- Test URL: `http://127.0.0.1:8765`
- Data: isolated SQLite database created with `artisan migrate:fresh --seed`; the real MySQL clinic database was not changed
- Browser scope: login/logout, dashboards, primary read pages, role denials, and physician record isolation
- Automated scope: all Unit and Feature tests, Vite production build, Composer audit, npm audit
- Excluded or blocked: real SMTP delivery, production HTTPS/HSTS, field Core Web Vitals, sustained load, real MySQL runtime, restore testing, and external security checker acceptance

## Findings and remediation ledger

| Finding ID | Severity | Root cause | Change / affected files | Verification | Status | Remaining action |
|---|---|---|---|---|---|---|
| SEC-001 | High | Locked Symfony packages predated patched releases for eight published advisories | `composer.lock` and `vendor/` updated to patched Symfony 7.4 releases | Final `composer audit --locked` reports no advisories; 67 tests pass | Fixed and verified | Deploy the updated lockfile/vendor set through the normal release process |
| SEC-002 | Medium | Laravel dynamic responses had no CSP, anti-framing, MIME-sniffing, referrer, or permissions headers and did not use `no-store` | `app/Http/Middleware/SecurityHeaders.php`, `bootstrap/app.php`, `tests/Feature/SecurityHeadersTest.php` | Live response header check plus 27 focused assertions covering 200 and 403 responses; browser console clean | Fixed and verified | Move inline scripts/styles to nonce or external assets before removing CSP `unsafe-inline` allowances |
| SEC-003 | High | Staff passwords are retained in reversible encrypted form and exposed to administrators through a reveal endpoint | Existing `User` password ciphertext model and password-reveal endpoint; unchanged | Code and tests confirm admin-only, rate-limited behavior | Unresolved; blocked | Product owner must explicitly accept this risk or authorize migration to reset-only, one-way password storage |
| OPS-001 | Informational | Local XAMPP MySQL could not start under this task's filesystem restrictions | No application change | UI ran against isolated seeded SQLite; existing suite also uses SQLite | Unresolved; blocked | Repeat critical journeys against the intended MySQL staging/production environment |

## Functional acceptance

- Existing regression tests: 64/64 before; 67/67 after.
- Receptionist: dashboard, patient list, appointments, schedules, visits, and create forms loaded; administrator route denied.
- Nurse: dashboard, patient/visit/schedule pages loaded; appointment-management route denied.
- Physician: own appointment/visit/patient views loaded; a physician assigned to Dr. Angela Lim could not open Dr. Rafael Reyes's visit and saw only Ana Garcia in the patient list.
- Administrator: dashboard, users, physicians, schedules, and reports loaded.
- Browser console: zero warnings or errors observed across the tested journeys.
- State-changing clinic workflows were covered by automated tests; the browser pass remained non-destructive.

## Non-functional acceptance

- Security headers: passed after remediation.
- Dependency advisories: 8 before, 0 after for Composer; npm 0 before/after after dependency installation.
- Production build: initially blocked because `node_modules` was absent; passed after locked dependency installation.
- Performance: authenticated dashboard median was 236.58 ms before and 221.69 ms after over five local runs. Ranges overlap, so this is **No clear change**, not a claimed optimization.
- Accessibility: labels and keyboard-addressable controls were visible through the accessibility tree during critical journeys, but a full WCAG/axe audit was not run.
- Capacity and field performance: not measured; local single-request timings do not establish production capacity or field Core Web Vitals.

## Before-and-after benchmarks

See `benchmark-results.csv` for the machine-readable results.

| Category | Metric / fixed scope | Before | After | Comparison / target |
|---|---|---:|---:|---|
| Functional | Existing regression tests | 64/64 | 67/67 | Pass / all tests pass |
| Security | Known dependency advisories | 8 | 0 | Improved / pass |
| Security | Required dynamic response headers present | 0/5 | 5/5 | Improved / pass |
| Quality | Production build | Blocked: dependencies absent | Pass | Improved environment readiness |
| Web lab | Authenticated dashboard response, median of 5 | 236.58 ms | 221.69 ms | No clear change; ranges overlap |
| Reliability | Browser console warnings/errors | 0 | 0 | Pass |

## Changed files

- `app/Http/Middleware/SecurityHeaders.php`: new response-hardening middleware.
- `bootstrap/app.php`: registers the middleware in the web group.
- `tests/Feature/SecurityHeadersTest.php`: public and authenticated header regression tests.
- `composer.lock`: patched Symfony dependency versions.
- `package-lock.json`: generated by installing the existing frontend dependency manifest.
- `public/build/`: regenerated production assets.

## Limitations and next actions

1. Decide whether to remove administrator password reveal and migrate away from recoverable password ciphertext.
2. Retest on a MySQL staging environment behind the final HTTPS reverse proxy; validate secure cookies, trusted proxy handling, HSTS readiness, and actual response headers there.
3. Run approved SMTP delivery and backup/restore checks before production use.
4. Perform a dedicated WCAG audit and representative desktop/mobile browser matrix if those are release requirements.
5. Replace inline scripts and styles with external assets or CSP nonces, then remove `unsafe-inline` from the enforced CSP.

