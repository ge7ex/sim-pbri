# SIM PBRI Full V1 QA — Working Report

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Baseline HEAD: `d2e84e71c33edc8842e0483ab7c7213194a38564`

## Verdict

**FULL V1 READY FOR SENIOR REVIEW: NOT YET READY.**
**REAL PILOT READY: NOT YET READY.**

The baseline feature suite, production frontend build, dependency audits, schema migrations and selected browser paths pass. The original `project_sim_flow_v12.md` and related gap analyses were not available, so requirements traceability is handoff-derived and incomplete. Browser approval could not be completed because Chrome's JavaScript confirmation dialog stopped responding to the computer-control bridge. The required 1440/1024/768/390 responsive matrix and whole-system role walkthrough remain incomplete.

## Fresh automated evidence

| Check | Result | Scope / limit |
|---|---|---|
| `php artisan test --no-ansi` | 175 passed / 1,595 assertions | Tests use guarded SQLite `:memory:`; no MySQL test run |
| `npm run build` | PASS, 686 modules | Frontend production compilation |
| `php vendor/bin/pint --test --dirty` | PASS | Changed PHP files |
| `composer audit --no-interaction` | No advisories | Current lockfile |
| `npm audit --no-fund` | 0 vulnerabilities | Current lockfile |
| `php artisan migrate:status` | All 19 migrations applied | Local runtime MySQL; migrations 000015/000016 applied additively |
| `php artisan route:list --json` + source label scan | No Export/download routes or UI labels found | Does not claim an exhaustive semantic review of every third-party dependency |
| `git diff --check` | Pending final rerun | Re-run before commit |

The MySQL schema change created `user_access_events`, added account flags, and added nullable booking-resource snapshots. No fixtures or institutional records were seeded into MySQL. Existing booking pivot snapshot columns remain null; no historical values were inferred from current Master Data.

## Browser evidence (isolated synthetic SQLite only)

| Role / flow | Result | Evidence / limitation |
|---|---|---|
| Admin | PASS: login, College label, user-management navigation, account creation, one-time credential visibility and hide action | Synthetic Chrome test database under TEMP; credential was hidden before screenshot; database removed after test |
| Staff | PASS: no user-management navigation; direct `/app/users` request returns 403; review displays requester and phone for same-College pending booking | Synthetic Chrome test database; no foreign College booking appears |
| Lecturer | PASS: 24-hour booking form, room availability, editable room user/coordinator, course/scenario/equipment selection, Pending submission, history and detail | Synthetic Chrome test database; room snapshot remains visible after changing Master Data in automated test |
| Staff approve | NOT VERIFIED in browser | Approval button opened a JavaScript confirmation; Chrome control timed out and the action was not completed. Automated transition tests pass. |
| Responsive breakpoints | NOT VERIFIED | The requested 1440, 1024, 768 and 390 CSS-pixel matrix was not completed in this run. |
| Password change/reset | NOT VERIFIED in browser | Feature tests pass; browser did not submit credential changes. |

No real user accounts or credentials were entered. The isolated Senior demo helper creates random credentials in a current-user-only TEMP directory and is not a MySQL seed path.

## Product gap decisions

- Implemented editable booking display name, preserving the immutable session-derived `requested_by_user_id` and College.
- Implemented same-College reviewer contact display on the permission-protected review page.
- Added future-only room/resource snapshots for name, location, building, floor, and capacity. Legacy requests remain readable and are marked as using current Master Data where shown.
- Draft retention and optional physical dimensions remain owner/source decision gates. Do not invent retention/expiry/booking semantics or room units while v12 is unavailable.
- Email invitations and self-service recovery remain unavailable because no approved mail or identity provider was configured. Admin provisioning uses a one-time credential delivered through an institution-approved private channel.

## Required follow-up before Senior review

1. Reconcile `docs/REQUIREMENTS_TRACEABILITY_V1.md` against the original v12 and gap analyses when supplied.
2. Complete browser approval/rejection flow and full role matrix at 1440/1024/768/390; inspect keyboard and zero/error/loading states on all modules.
3. Resolve Draft and room-dimension decisions from confirmed source/owner requirements.
4. Complete remaining cross-module checks, final Ponytail review, final `git diff --check`, and a fresh full suite/build after final edits.
5. Review the Senior demo script and operational/pilot gates with the Senior reviewer.

## Runtime and Git boundary

- Runtime MySQL was inspected and migrations were applied. Application test fixtures were never pointed at MySQL.
- The Senior demo uses `scripts/qa/phase-i-demo.ps1`, a new SQLite file under `%TEMP%`, a random APP_KEY, a local-only server and synthetic data only.
- The setup and cleanup script were exercised. Cleanup stops only recorded PHP processes matching the local repo/server command, then removes only the fixed demo directory under TEMP.
- No `main` merge or Export feature was performed.
- The current Phase I implementation is committed and pushed on `backend/inertia-foundation`; no `main` merge was made.
