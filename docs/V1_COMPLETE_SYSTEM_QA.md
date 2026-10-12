# SIM PBRI Full V1 QA — Working Report

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Application HEAD under review: `4d3fcb40d9e7242791a73d9d29e202034342b463` (verified 12 October 2026; branch equals origin; worktree clean)

## Verdict

**FULL V1 READY FOR SENIOR REVIEW: NOT YET READY.**
**REAL PILOT READY: NOT YET READY.**

The feature suite, production frontend build, dependency audits, schema migration status and demo setup/cleanup pass. The original `project_sim_flow_v12.md` and full gap analyses remain unavailable; a secondary 60-check HTML report was found, but its code evidence is from `d2e84e7`, so traceability is not source-complete. The current review reran automated checks but did not perform browser workflows. Browser approval, the 1440/1024/768/390 matrix and whole-system role walkthrough remain incomplete.

## Fresh automated evidence

| Check | Result | Scope / limit |
|---|---|---|
| `php artisan test --no-ansi` | 186 passed / 1,663 assertions | Tests use guarded SQLite `:memory:`; no MySQL test run |
| `php artisan test --no-ansi` with `SESSION_ENCRYPT=true` | 186 passed / 1,663 assertions | Confirms auth/session feature suite still passes with session payload encryption enabled |
| `npm run build` | PASS, 686 modules | Frontend production compilation |
| `php vendor/bin/pint --test --dirty` | PASS | Changed PHP files |
| `composer audit --no-interaction` | No advisories | Current lockfile |
| `npm audit --no-fund` | 0 vulnerabilities | Current lockfile |
| `php artisan migrate:status` | All 21 migrations applied | Local runtime MySQL; migrations 000017/000018 added an empty Draft table and nullable dimensions without populating existing records |
| Deployment runbook | Drafted from repository config/migrations | Not exercised on a target host; no deployment authorization |
| `php artisan route:list --json` + source label scan | No Export/download routes or UI labels found | Does not claim an exhaustive semantic review of every third-party dependency |
| `php scripts/qa/verify-phase-i-demo-restore.php` | PASS; integrity and row counts match across 26 tables | Synthetic SQLite only; does not verify a production MySQL backup |
| `phase-i-demo.ps1` caller environment restoration | PASS | Sentinel APP/DB values were restored after setup; server retained isolated SQLite settings |
| PowerShell parser for `phase-i-demo.ps1` | PASS | After process identity and environment restoration changes |
| `git diff --check` | PASS | Verified on the pre-commit diff; committed branch is clean |

The prior additive MySQL schema change created `user_access_events`, added account flags, and added nullable booking-resource snapshots. This review applied migration 000017 (empty `booking_drafts`, 0 rows) and 000018 (nullable room dimensions). No existing app row was updated/deleted or populated; no fixture seed or snapshot backfill was run. Existing booking pivot snapshot columns remain null; no historical values were inferred from current Master Data.

## Browser evidence (isolated synthetic SQLite only)

| Role / flow | Result | Evidence / limitation |
|---|---|---|
| Admin | PASS: login, College label, user-management navigation, account creation, one-time credential visibility and hide action | Synthetic Chrome test database under TEMP; credential was hidden before screenshot; database removed after test |
| Staff | PASS: no user-management navigation; direct `/app/users` request returns 403; review displays requester and phone for same-College pending booking | Synthetic Chrome test database; no foreign College booking appears |
| Lecturer | PASS: 24-hour booking form, room availability, editable room user/coordinator, course/scenario/equipment selection, Pending submission, history and detail | Synthetic Chrome test database; room snapshot remains visible after changing Master Data in automated test |
| Staff approve | NOT VERIFIED in browser | Approval button opened a JavaScript confirmation; Chrome control timed out and the action was not completed. Automated transition tests pass. |
| Responsive breakpoints | NOT VERIFIED | The requested 1440, 1024, 768 and 390 CSS-pixel matrix was not completed in this run. |
| Password change/reset | NOT VERIFIED in browser | Feature tests pass; browser did not submit credential changes. |
| Draft browser flow | NOT VERIFIED | Draft save/resume/delete and Staff non-visibility are covered by feature tests, but the new UI was not exercised in a browser. |
| Room-dimension UI | NOT VERIFIED | Validation/persistence and build pass; room create/edit, derived area and Lecturer display were not exercised in a browser. |
| Current review browser run | NOT RUN | Browser workflows were not repeated in this review; earlier computer-use policy rejection is recorded above as prior evidence only. |

No real user accounts or credentials were entered. The isolated Senior demo helper creates random credentials in a current-user-only TEMP directory and is not a MySQL seed path. Setup and cleanup passed end-to-end after cleanup began verifying the recorded process name, command line, and exact creation ticks, and requiring port 8766 to be closed before removing TEMP data. The first cleanup attempt safely refused to stop an unverified PID; after verifying the current server identity, only the isolated TEMP directory was removed. Setup also restored caller APP/DB environment values after launching the server.

## Code and UX review

Ponytail and Ponytail Review instructions were available and used for code/change review. Impeccable and UI/UX Pro Max skills were present; `.impeccable.md` supplied the established institutional design context. Impeccable’s referenced `references/pro-rules.md` is absent. UI/UX Pro Max guidance for this review emphasized keyboard focus, complete keyboard navigation, visible submit feedback, and responsive form inspection. The Vue-specific lookup returned no match on the first query and three relevant form/component/testing entries on a single broader retry. UI source changed to add Draft controls and Room Master dimensions; browser observations below are prior isolated-browser evidence, not a fresh browser run in this review.

## Product gap decisions

- Implemented editable booking display name, preserving the immutable session-derived `requested_by_user_id` and College.
- Implemented same-College reviewer contact display on the permission-protected review page.
- Added future-only room/resource snapshots for name, location, building, floor, and capacity. Legacy requests remain readable and are marked as using current Master Data where shown.
- The secondary report records a Draft requirement and width/length/area in the v12 model example. Drafts are private to the owner, capped at 10, and remain until submission or owner deletion; automatic expiry is pending owner retention policy. Room width/length use an accepted metric assumption, with area derived; original v12 remains needed to validate this convention. Existing rooms were not backfilled.
- `config/app.php` sets UTC; booking inputs are local `datetime-local` values parsed without an explicit timezone, and JS renders in browser-local time. This can shift displayed times for Thai users. Timezone confirmation is pending; no timestamp interpretation or schema was changed.
- `php artisan tinker --execute 'echo config("app.timezone");'` returned `UTC`; parsing 09:00 as UTC and rendering that ISO value in an Asia/Bangkok browser produces 16:00. This reproduces the seven-hour risk, but does not establish the institution's approved timezone.
- Email invitations and self-service recovery remain unavailable because no approved mail or identity provider was configured. Admin provisioning uses a one-time credential delivered through an institution-approved private channel.
- Changed only `.env.example` to enable session payload encryption for new environment setup. The local `.env` and database were not read or changed; the full suite passes with `SESSION_ENCRYPT=true`.

## Required follow-up before Senior review

1. Obtain original v12 and full gap analyses; reconcile every requirement in `docs/REQUIREMENTS_TRACEABILITY_V1.md`.
2. Confirm institutional timezone before changing parsing/serialization behavior; protect interpretation of existing MySQL booking times.
3. Confirm the Draft retention policy and validate the metric/derived-area assumption against original v12 or owner decision.
4. Complete browser Draft save/resume/submit, approval/rejection and the full role matrix at 1440/1024/768/390, plus keyboard and zero/error/loading checks.
5. Test backup/restore against the approved deployment backup mechanism; the synthetic SQLite drill does not cover MySQL operations.
6. Complete remaining cross-module checks, Ponytail review of the final patch, and fresh suite/build after final edits.
7. Review the Senior demo and operational gates with the Senior reviewer.

## Runtime and Git boundary

- Runtime MySQL was inspected and migrations were applied. Application test fixtures were never pointed at MySQL.
- The Senior demo uses `scripts/qa/phase-i-demo.ps1`, a new SQLite file under `%TEMP%`, a random APP_KEY, a local-only server and synthetic data only.
- The setup and cleanup script were exercised end-to-end. Cleanup stops only verified demo processes, requires port 8766 to be closed, then removes only the fixed demo directory under TEMP; a PID-reuse failure path was corrected and rerun.
- MySQL received only the additive `booking_drafts` table migration; the table is empty. No existing application rows were changed; tests and demo seeding remained isolated.
- No `main` merge or Export feature was performed.
- The current Phase I implementation is committed and pushed on `backend/inertia-foundation`; no `main` merge was made.


## Verification refresh — 12 October 2026

After Draft and Room Master dimension implementation, the full PHP suite passed on the working branch with the default test configuration and again with `SESSION_ENCRYPT=true` (186 tests / 1,663 assertions each). `npm run build` passed (686 modules), Pint passed, Composer and npm audits reported no advisories/vulnerabilities, migrations 000017 and 000018 are `Ran` with 21 total applied migrations, and the registered-route export/download scan returned no matches. Runtime schema changes created the empty Draft table (0 rows) and nullable room-dimension columns; no existing room was populated. Browser workflows were not run. The Draft implementation commit `3bef07e` and room-dimensions commit `4d3fcb4` are pushed to `origin/backend/inertia-foundation`. Browser workflows remain unverified for this feature.
