# Phase I Product Completion Plan

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Latest implementation commit: `4cddbafc0baa9b5da7674b430e7391486516d5ec` on `backend/inertia-foundation`; QA documentation is being finalized in a follow-up commit.

## Evidence boundary

The corrected Phase I handoff is the active scope. The original `project_sim_flow_v12.md` and full gap analysis are still unavailable. A user-provided explanatory HTML report with 60 analytical checkpoints exists in Downloads, but it references the older `d2e84e7` implementation snapshot and is not authoritative wording. `REQUIREMENTS_TRACEABILITY_V1.md` uses it as a gap-finding aid and does not claim exhaustive source traceability. Add v12 and reconcile it before calling the matrix source-complete.

No uncommitted user changes were present at the original baseline. The branch and origin matched then. Tests are guarded to SQLite `:memory:`. The local MySQL migration status now shows 21 applied migrations, including additive migrations 000015–000018; 000017 created the empty Draft table and 000018 added nullable room dimensions. No application fixtures or measurements were seeded into MySQL.

## Increments

| Increment | Scope | Entry evidence | Ready condition | Status |
|---|---|---|---|---|
| I-0 | Baseline and traceability | Current base `f3660f3`; fresh suite 187 tests / 1,672 assertions; v12 source absent; secondary 60-check report found | Every v12 core requirement mapped to evidence/decision | In progress: handoff and secondary-report gaps mapped; exact v12 wording and full gap analysis remain unavailable |
| I-1 | Real account operations | Route inventory had login/logout only; local dev seeder is environment-guarded | College-scoped Admin account create/edit/disable/reset; bootstrap/recovery; forced password change; last-Admin protection; feature tests and isolated browser QA | Done for code: 11 focused tests within fresh full suite; Admin login/menu/create/one-time-secret-hide verified in isolated Chrome. Browser password-rotation flow not performed; feature tests cover it. |
| I-2 | Room, booking, and review gaps | Editable requester display, room snapshots, review contact, Drafts, and room dimensions implemented | Implement clear safe requirements; document owner-dependent retention; retain no-auto-classification rule | In progress: dimensions remain stored but are hidden from every page, including Admin, by explicit owner instruction; Drafts persist until owner submits/deletes, confirmed by owner; timezone is Asia/Bangkok by explicit owner confirmation |
| I-3 | End-to-end UX and reliability | Role-specific route and test inventory plus I-1/I-2 | Admin, Staff, Lecturer flows verified in isolated browser at 1440/1024/768/390; responsive/accessibility defects fixed | In progress: browser verified Admin resource create/edit dimension hiding, Staff contact/snapshot review and approval, Lecturer Draft save/resume at 09:00–11:00, Staff Draft isolation, Lecturer booking form 1440/1024/768/390 and Staff review 390 with no horizontal overflow. Admin responsive matrix, keyboard/screen-reader, and reject/cancel browser journeys remain open. |
| I-4 | Security and operations | Isolated QA, no-Export audit, dependency and migration baseline | Fresh suite/audits; deployment, backup/restore drill when safe, rollback and timezone docs evidence actual results | In progress: current tests/build/audits pass; read-only `migrate:status` rerun shows all 21 migrations Ran; no migration or MySQL data write occurred in this slice. Synthetic SQLite restore passed and a target-independent deployment runbook was drafted; production MySQL backup/restore and full browser role/accessibility matrix remain open; booking/report timezone decision is implemented without altering global UTC or database timestamps. A literal-only read-only MySQL check validated the report DATE_ADD syntax and returned the Bangkok date expected. |
| I-5 | Senior Review Candidate | Completed application and isolated repeatable demo environment | Script, feedback log, QA report/JSON, pilot gates and final review verdict | In progress: SQLite setup/cleanup rerun passed after cleanup PID-reuse guard fix; Senior readiness remains NOT YET READY pending original v12 traceability, remaining browser role/accessibility QA, production MySQL restore validation and final walkthrough. |

## Work rules

- Continue only on `backend/inertia-foundation`; preserve local MySQL; add only additive migrations tied to verified gaps.
- Never add user-accessible Export, merge `main`, invent institutional data, or claim a live deployment, real UAT, or restore drill that did not occur.
- Every slice follows inspect → reproduce → implement → focused tests → independent review → commit/push to the existing branch.
- Significant P0/P1 gaps remain on the backlog until fixed. Owner-dependent decisions are recorded while independent work proceeds.

## Latest verified implementation slice

- Added `2026_10_12_000016_add_booking_resource_snapshots` with nullable snapshots for resource name/kind/location and room building/floor/capacity. Existing rows are not backfilled; UI marks missing legacy snapshots as current Master Data.
- Booking `requester_name` can now be supplied independently from immutable `requested_by_user_id`; omitting it preserves the prior actor-name behavior. College and actor IDs remain server-derived.
- Same-College Staff/Admin review receives requester contact fields on the already permission-protected review page; foreign-College bookings are excluded by query scope.
- Fresh suite on current candidate: 187 passed / 1,672 assertions. Vite build passed (686 modules). Scoped Pint and `git diff --check` pass after the verified edits; dependency audits are clean.
- Isolated browser fixture lives under the OS temp directory and uses SQLite only. It is temporary, not the I-5 repeatable setup.
- Approval flow reached the browser confirmation modal, but Chrome automation could not operate the modal after focus-emulation timeouts. No claim of browser approval is made; feature tests cover state transitions.
- Runtime migrations 000015 and 000016 were applied after confirming the pending SQL with `php artisan migrate --pretend`; `migrate:status` then showed all 21 migrations applied. No fixtures or institutional data were seeded into runtime MySQL.
- Added a guarded Senior demo setup/cleanup pair under `scripts/qa`; the setup refuses MySQL and uses a TEMP-only synthetic SQLite database with random credentials.
- The current timezone and dimension-privacy implementation is committed as `4cddbafc0baa9b5da7674b430e7391486516d5ec`; QA docs capture the remaining browser and source-traceability gates. Cleanup checks process identity and refuses deletion while port 8766 remains open.
- Fresh checks on 12 October 2026: 187 tests / 1,672 assertions; Vite 686 modules; scoped Pint, Composer audit, and npm audit pass. Migration status was not rerun in this slice. Browser review covered Lecturer Draft resume and Staff queue; approval action did not complete due to browser-control timeout.
- Owner confirmed Asia/Bangkok. Booking input/display, calendar date grouping, booking filters and report boundaries now use Bangkok while persisted instants and global app timezone remain UTC. No existing timestamp was converted.
- `scripts/qa/verify-phase-i-demo-restore.php` passed against synthetic demo data: SQLite integrity passed and table row counts matched across 26 tables after backup and restore-copy. This does not verify production MySQL backup/restore.
- Drafted `docs/DEPLOYMENT_RUNBOOK_V1.md` from the current environment template, configs, migrations, access-operations guide, and storage/logging code. It is not target-tested and does not authorize deployment.
- Changed only `.env.example` so new local/deployment setup defaults to encrypted session payloads; the local `.env` and database were not read or changed. The full guarded SQLite suite passed with `SESSION_ENCRYPT=true`.
- Implemented Draft as a separate College/user-scoped JSON table; it cannot be reviewed or reserve resources. Incomplete drafts can be saved, edited, resumed, deleted; successful submission revalidates through the existing booking action and deletes the draft in the same transaction. Failed submission preserves it. Limit is 10 per user; drafts persist until user deletion/submission, with no automatic expiry. Migration `000017` created the new empty table in MySQL; no existing table rows were modified.
- Room width/length columns remain nullable and preserve existing values, but dimensions and derived area are now hidden from every page, including Admin, per owner instruction. Admin and booking Inertia payloads omit both values; regression tests assert omission. No schema or runtime MySQL change was made in this slice.

- Synthetic SQLite browser run verified Admin room dimension hiding, Staff review and approval, Lecturer Draft save/resume, and responsive overflow for the Lecturer booking form at 1440/1024/768/390. Staff review passed at 390; Admin responsive, reject/cancel, and accessibility runs remain outstanding.
- The current slice implements Asia/Bangkok date/time semantics for booking input/display, calendar, filters and report boundaries while retaining UTC persistence. JavaScript self-check passed under UTC and America/Los_Angeles host time zones.
- Dimensions are omitted from both Admin and booking page payloads and hidden in UI; values/schema are preserved. No MySQL command or migration was run during this slice.
