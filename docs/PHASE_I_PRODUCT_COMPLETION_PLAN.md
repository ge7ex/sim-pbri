# Phase I Product Completion Plan

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Baseline HEAD: `d2e84e71c33edc8842e0483ab7c7213194a38564`

## Evidence boundary

The corrected Phase I handoff is the active scope. The handoff names `project_sim_flow_v12.md` and two gap-analysis documents, but none were present in the repository, Downloads, Documents, Desktop, or Codex attachment filenames searched on 12 October. Therefore `REQUIREMENTS_TRACEABILITY_V1.md` starts with requirements explicitly stated in the handoff and confirmed current code behavior; it does not claim exhaustive traceability to v12 or to 60 analytical checkpoints. Add the owner-provided v12 source and reconcile it before calling the matrix source-complete.

No uncommitted user changes were present. Local branch and origin branch matched at baseline. Tests are guarded to SQLite `:memory:`; the fresh baseline suite passed. The local MySQL migration inventory was read-only and all 17 known migrations were applied. No application data was written.

## Increments

| Increment | Scope | Entry evidence | Ready condition | Status |
|---|---|---|---|---|
| I-0 | Baseline and traceability | Starting HEAD `d2e84e7`; fresh initial run 161 tests / 1,436 assertions; v12 source absent | Handoff requirements mapped to code/tests/decision, source gaps stated | In progress: handoff-derived matrix recorded; v12 and gap-analysis sources remain unavailable |
| I-1 | Real account operations | Route inventory had login/logout only; local dev seeder is environment-guarded | College-scoped Admin account create/edit/disable/reset; bootstrap/recovery; forced password change; last-Admin protection; feature tests and isolated browser QA | Done for code: 11 focused tests within fresh full suite; Admin login/menu/create/one-time-secret-hide verified in isolated Chrome. Browser password-rotation flow not performed; feature tests cover it. |
| I-2 | Room, booking, and review gaps | Editable requester display, room snapshots, review contact, Draft and dimensions reviewed | Implement clear safe requirements; disputed Draft behavior logged with impact | In progress: requester display and contact implemented; additive room snapshot implemented; old data remains null; Draft and physical dimensions await source/owner decision |
| I-3 | End-to-end UX and reliability | Role-specific route and test inventory plus I-1/I-2 | Admin, Staff, Lecturer flows verified in isolated browser at 1440/1024/768/390; responsive/accessibility defects fixed | In progress: Admin and Lecturer screens exercised at desktop; Staff role boundary/review data verified. Browser approval stopped at Chrome JS confirmation; other breakpoints and remaining domains still pending. |
| I-4 | Security and operations | Isolated QA, no-Export audit, dependency and migration baseline | Fresh suite/audits; deployment, backup/restore drill when safe, rollback and configuration docs evidence actual results | In progress: 175 tests / 1,595 assertions; build and audits pass; additive migrations applied to local runtime MySQL; backup/restore drill and full browser matrix remain open. |
| I-5 | Senior Review Candidate | Completed application and isolated repeatable demo environment | Script, feedback log, QA report/JSON, pilot gates and final review verdict | In progress: repeatable SQLite demo setup/cleanup and review artifacts created and setup/cleanup exercised; system remains NOT YET READY because source traceability and full browser QA remain open. |

## Work rules

- Continue only on `backend/inertia-foundation`; preserve local MySQL; add only additive migrations tied to verified gaps.
- Never add user-accessible Export, merge `main`, invent institutional data, or claim a live deployment, real UAT, or restore drill that did not occur.
- Every slice follows inspect → reproduce → implement → focused tests → independent review → commit/push to the existing branch.
- Significant P0/P1 gaps remain on the backlog until fixed. Owner-dependent decisions are recorded while independent work proceeds.

## Latest verified implementation slice

- Added `2026_10_12_000016_add_booking_resource_snapshots` with nullable snapshots for resource name/kind/location and room building/floor/capacity. Existing rows are not backfilled; UI marks missing legacy snapshots as current Master Data.
- Booking `requester_name` can now be supplied independently from immutable `requested_by_user_id`; omitting it preserves the prior actor-name behavior. College and actor IDs remain server-derived.
- Same-College Staff/Admin review receives requester contact fields on the already permission-protected review page; foreign-College bookings are excluded by query scope.
- Final full suite: 175 passed / 1,595 assertions. Final Vite build: passed (686 modules). Pint changed-file test and `git diff --check` pass after all code and documentation edits.
- Isolated browser fixture lives under the OS temp directory and uses SQLite only. It is temporary, not the I-5 repeatable setup.
- Approval flow reached the browser confirmation modal, but Chrome automation could not operate the modal after focus-emulation timeouts. No claim of browser approval is made; feature tests cover state transitions.
- Runtime migrations 000015 and 000016 were applied after confirming the pending SQL with `php artisan migrate --pretend`; `migrate:status` then showed all 19 migrations applied. No fixtures or institutional data were seeded into runtime MySQL.
- Added a guarded Senior demo setup/cleanup pair under `scripts/qa`; the setup refuses MySQL and uses a TEMP-only synthetic SQLite database with random credentials.
