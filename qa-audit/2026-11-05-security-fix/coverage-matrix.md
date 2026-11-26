# Coverage matrix

| Test ID | Feature / route | Role | Scenario | Expected result | Layer | Risk | Status | Evidence |
|---|---|---|---|---|---|---|---|---|
| F-AUTH-01 | `/`, `/login`, `/logout` | All roles | Sign in and sign out with seeded account | Session established then invalidated | UI/API | High | Pass | Browser journeys; authentication tests |
| F-ROLE-01 | Primary reception routes | Receptionist | Open dashboard, patients, appointments, schedules, visits, create forms | 200 and correct page heading | UI | High | Pass | Browser route results |
| F-ROLE-02 | `/appointments` | Nurse | Direct navigation | 403 | UI/API | High | Pass | Browser returned Forbidden |
| F-ROLE-03 | `/users` | Receptionist | Direct navigation | 403 | UI/API | High | Pass | Browser returned Forbidden |
| F-ROLE-04 | `/users`, `/physicians`, `/reports` | Administrator | Direct navigation | 200 | UI | High | Pass | Browser route results |
| F-ISO-01 | `/patients`, `/visits/1` | Physician2 | Inspect assigned patients and another physician's visit | Only assigned patient shown; other visit denied | UI/data | High | Pass | Ana Garcia only; visit 1 returned 403 |
| F-WORK-01 | Appointment lifecycle | Receptionist/Nurse/Physician | Conflict, verify, arrival, vitals, consultation, complete | Rules and persistence pass | API/data | High | Pass | `ClinicWorkflowTest` |
| F-WORK-02 | Administration workflows | Administrator | User, physician, schedule, and report operations | Changes persist with role checks | API/data | High | Pass | `InteractionFlowTest` |
| S-HDR-01 | `/` | Guest | Read response headers | Required hardening headers present | HTTP/code | Medium | Pass | Live response plus test |
| S-HDR-02 | `/dashboard` | Authenticated | Read response headers | Same protections plus no-store | HTTP/code | Medium | Pass | Live response plus test |
| S-DEP-01 | Locked dependencies | N/A | Composer and npm advisory audits | Zero known advisories | Dependency | High | Pass | Final audit outputs |
| S-PWD-01 | Password storage/reveal | Administrator | Review storage and reveal design | One-way storage only | Code | High | Fail | Recoverable ciphertext remains by documented design |
| Q-TEST-01 | Full automated suite | N/A | Run all tests | Exit 0 | Test | High | Pass | 67 tests, 1,543 assertions |
| Q-BUILD-01 | Vite production build | N/A | Build optimized assets | Exit 0 | Build | Medium | Pass | Vite 7.3.7, 61 modules |
| P-DASH-01 | `/dashboard` | Administrator | Five serial warm requests | Provisional median <500 ms | HTTP | Medium | Pass | 221.69 ms final median |
| A11Y-01 | Critical pages | All roles | Accessibility-tree inspection | Named headings, labels, and controls exposed | UI | Medium | Pass (limited) | Browser accessibility snapshots |
| A11Y-02 | Full WCAG automated/manual audit | All roles | Axe plus keyboard/focus matrix | Required criteria pass | UI | Medium | Not tested | Dedicated accessibility tooling not run |

