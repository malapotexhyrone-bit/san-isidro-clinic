# Requirements traceability

| Requirement ID | Type | Requirement and source | Acceptance criterion / budget | Test IDs and environment | Before | Fix / evidence | After | Blocker / remaining action |
|---|---|---|---|---|---|---|---|---|
| FR-001 | Functional | Documented role-based access | Required role pages load; forbidden role routes return 403 | F-ROLE-01 through F-ROLE-04, isolated local UI | Pass | Browser evidence and automated authorization tests | Pass | None in tested scope |
| FR-002 | Functional | Physician assignment isolation | Physician sees only assigned records | F-ISO-01, isolated local UI | Pass | Physician2 saw only Ana Garcia and received 403 for visit 1 | Pass | None in tested scope |
| FR-003 | Functional | Core clinic workflow | Appointment, walk-in, vitals, consultation, completion persist correctly | Existing `ClinicWorkflowTest` and `InteractionFlowTest` | Pass | 67-test final suite | Pass | Browser pass was non-destructive |
| NFR-001 | Security | Current dependency advisories must be resolved | Zero applicable known advisories in final locked dependency audit | S-DEP-01 | Fail: 8 | Patched five Symfony components | Pass: 0 | Recheck at deployment time |
| NFR-002 | Security | Dynamic pages send browser hardening headers | CSP, XFO, XCTO, Referrer-Policy, Permissions-Policy present; private no-store caching | S-HDR-01, S-HDR-02 | Fail | Middleware, live response check, 21 assertions | Pass | CSP retains documented inline allowances |
| NFR-003 | Security | Passwords use one-way storage only | No recoverable password copy or reveal endpoint | S-PWD-01 code review | Fail | No change because behavior is documented as intentional | Fail | Product-owner decision required |
| NFR-004 | Quality | Automated tests pass | All project tests pass | Q-TEST-01 | Pass: 64/64 | Added focused header coverage | Pass: 67/67 | None |
| NFR-005 | Quality | Production build succeeds | `npm run build` exits 0 | Q-BUILD-01 | Blocked | Installed locked frontend dependencies | Pass | Keep lockfile in release |
| NFR-006 | Performance | Provisional local dashboard budget | Median under 500 ms, five serial warm local requests | P-DASH-01 | Pass: 236.58 ms | No performance-specific change | Pass: 221.69 ms | Not a production SLO |
| NFR-007 | Operability | Final MySQL/HTTPS environment verified | Critical journeys pass on intended deployment with HTTPS controls | O-DEPLOY-01 | Not tested | Isolated SQLite substituted safely | Not tested | Staging/production access required |

