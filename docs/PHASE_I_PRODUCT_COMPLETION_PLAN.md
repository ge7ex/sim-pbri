# Phase I Product Completion Plan

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Latest verified product-code commit: `25d8a20f3c8badf9f829df664d59b47d742fbf49` (`fix: refresh account management csrf token`) on `backend/inertia-foundation`. The branch has local commits not yet pushed and has not been merged to `main`. The CSRF fix and duplicate-email localization are covered by 189 tests / 1,690 assertions; a separate Codex Security diff scan of `883b744..048828f` completed with 0 findings across the three changed source files. This is not a full-repository scan.

## Evidence boundary

The corrected Phase I handoff is the active scope. The original `project_sim_flow_v12.md` and full gap analysis are still unavailable. A user-provided explanatory HTML report with 60 analytical checkpoints exists in Downloads, but it references the older `d2e84e7` implementation snapshot and is not authoritative wording. `REQUIREMENTS_TRACEABILITY_V1.md` uses it as a gap-finding aid and does not claim exhaustive source traceability. Add v12 and reconcile it before calling the matrix source-complete.

No uncommitted user changes were present at the original baseline. The branch and origin matched then. Tests are guarded to SQLite `:memory:`. The local MySQL migration status now shows 21 applied migrations, including additive migrations 000015–000018; 000017 created the empty Draft table and 000018 added nullable room dimensions. No application fixtures or measurements were seeded into MySQL.

## Increments

| Increment | Scope | Entry evidence | Ready condition | Status |
|---|---|---|---|---|
| I-0 | Baseline and traceability | Audit starting HEAD `697635d`; fresh suite 187 tests / 1,672 assertions; v12 source absent; secondary 60-check report found | Every v12 core requirement mapped to evidence/decision | In progress: handoff and secondary-report gaps mapped; exact v12 wording and full gap analysis remain unavailable |
| I-1 | Real account operations | Route inventory had login/logout only; local dev seeder is environment-guarded | College-scoped Admin account create/edit/disable/reset; bootstrap/recovery; forced password change; last-Admin protection; feature tests and isolated browser QA | Done for code: 11 focused tests within fresh full suite; Admin login/menu/create/one-time-secret-hide verified in isolated Chrome. Browser password-rotation flow not performed; feature tests cover it. |
| I-2 | Room, booking, and review gaps | Editable requester display, room snapshots, review contact, Drafts, and room dimensions implemented | Implement clear safe requirements; document owner-dependent retention; retain no-auto-classification rule | In progress: dimensions remain stored but are hidden from every page, including Admin, by explicit owner instruction; Drafts persist until owner submits/deletes, confirmed by owner; timezone is Asia/Bangkok by explicit owner confirmation |
| I-3 | End-to-end UX and reliability | Role-specific route and test inventory plus I-1/I-2 | Admin, Staff, Lecturer flows verified in isolated browser at 1440/1024/768/390; responsive/accessibility defects fixed | In progress: prior Admin flows remain evidenced; this refresh verified Lecturer Draft save/resume/submit, Staff approval and Approved history in the same synthetic database, as well as mobile Escape close + focus return at 390px. Staff report privacy is documented from the previous isolated run. This slice also verified Staff rejected-history zero state, keyboard navigation from skip link through navigation, labeled status/date filters, no console errors on the Staff review page, and Lecturer form accessible names/capacity status; error/loading and full keyboard/screen-reader checks remain open. |
| I-4 | Security and operations | Isolated QA, no-Export audit, dependency and migration baseline | Fresh suite/audits; deployment, backup/restore drill when safe, rollback and timezone docs evidence actual results | In progress: fresh full suites pass both with normal sessions and `SESSION_ENCRYPT=true` (187 tests / 1,672 assertions each), build, Pint and dependency audits pass; no audit advisories/vulnerabilities; route scan pass; fresh read-only `migrate:status` reports all 21 migrations Ran with 0 pending; no migration was executed and no application rows changed. The user-authorized isolated MySQL round-trip created/dropped only a temporary schema, and source row counts matched before/after. A separate aggregate-only count of current MySQL tables found 3 users, 1 College, 1 room and 0 bookings/assets/drafts; no row contents were read. Synthetic SQLite restore and an authorized local encrypted MySQL restore drill passed; institution-approved production backup operations and full browser role/accessibility matrix remain open; booking/report timezone decision is implemented without altering global UTC or database timestamps. A literal-only read-only MySQL check validated the report DATE_ADD syntax and returned the Bangkok date expected. |
| I-5 | Senior Review Candidate | Completed application and isolated repeatable demo environment | Script, feedback log, QA report/JSON, pilot gates and final review verdict | In progress: SQLite setup/cleanup and same-run Lecturer Draft→Pending→Staff approval workflow pass. Senior readiness remains NOT YET READY pending source-complete traceability, full keyboard/screen-reader and cross-role error/loading QA, plus rehearsal of the integrated Admin/Staff/Lecturer walkthrough (Admin→Staff ran through review details; approval click uses native window.confirm, which CUA CDP timed out on; no request was submitted in that run; prior Lecturer→Staff approval flow passed). Senior feedback occurs after delivery; institution-approved recovery operations and owner acceptance are separate Real Pilot gates. |

### Follow-up defect repair — 12 October 2026

- Synthetic Admin UAT reproduced a 419 on account POST immediately after login because the account form read a stale CSRF token from the root HTML meta tag after Laravel regenerated the session token. `UserManagementController::index` now supplies the active session token in the page props, and the account page submits that current value.
- `StoreUserRequest` now reports duplicate email in Thai. Browser validation after a post-login submission exposed the inline message and its `aria-invalid` / `aria-describedby` relationship.
- Regression coverage and current full suite: 189 tests / 1,690 assertions. Production Vite build: 686 modules. Pint and `git diff --check` pass.
- Browser validation and Admin report checks used only synthetic SQLite. This slice did not repeat Staff or Lecturer login; see `V1_COMPLETE_SYSTEM_QA.md` for the failed synthetic Staff login attempt and cleanup evidence.
- The current Codex Security scan and fresh Composer audit remain incomplete; neither is represented as a clean result. MySQL was not accessed or changed.

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
- `scripts/qa/verify-phase-i-demo-restore.php` passed against synthetic demo data: SQLite integrity passed and table row counts matched across 26 tables after backup and restore-copy. A temporary local MySQL encrypted backup/restore drill was later completed successfully on 12 Oct 2026; it does not verify institution-approved production backup operations.
- Drafted `docs/DEPLOYMENT_RUNBOOK_V1.md` from the current environment template, configs, migrations, access-operations guide, and storage/logging code. It is not target-tested and does not authorize deployment.
- Changed only `.env.example` so new local/deployment setup defaults to encrypted session payloads; the local `.env` and database were not read or changed. The full guarded SQLite suite passed with `SESSION_ENCRYPT=true`.
- Implemented Draft as a separate College/user-scoped JSON table; it cannot be reviewed or reserve resources. Incomplete drafts can be saved, edited, resumed, deleted; successful submission revalidates through the existing booking action and deletes the draft in the same transaction. Failed submission preserves it. Limit is 10 per user; drafts persist until user deletion/submission, with no automatic expiry. Migration `000017` created the new empty table in MySQL; no existing table rows were modified.
- Room width/length columns remain nullable and preserve existing values, but dimensions and derived area are now hidden from every page, including Admin, per owner instruction. Admin and booking Inertia payloads omit both values; regression tests assert omission. No schema or runtime MySQL change was made in this slice.

- Latest synthetic SQLite browser refresh additionally verified Admin resources no-overflow at 1440/1024/768/390, Staff rejection with reason, and Lecturer cancellation with confirmation/status history. Keyboard/accessibility and zero/error/loading remain outstanding.
- The current slice implements Asia/Bangkok date/time semantics for booking input/display, calendar, filters and report boundaries while retaining UTC persistence. JavaScript self-check passed under UTC and America/Los_Angeles host time zones.
- Dimensions are omitted from both Admin and booking page payloads and hidden in UI; values/schema are preserved. No MySQL command or migration was run during this slice.

### Follow-up QA on 12 October 2026

- `php artisan test --no-ansi`: 187 passed / 1,672 assertions on guarded SQLite `:memory:`; `npm run build`: pass, 686 modules; registered route scan: 0 Export/download matches among 51 routes; user-facing source scan: 0 matching UI/control terms.
- Synthetic SQLite browser: Staff approved the pending fixture and a resumed Lecturer Draft after submission; the new booking appeared in Approved history. The earlier Staff-report privacy/empty-state checks remain recorded in the QA report.
- Reproduced and fixed mobile sidebar Escape behavior. At 390px, Escape closes the open menu, returns focus to the toggle, and keeps `:focus-visible` true.
- Demo cleanup closed port 8766 and removed `%TEMP%\sim-pbri-phase-i-demo`; this slice made no MySQL data changes.
- The Senior Review Candidate remains NOT YET READY; see `V1_COMPLETE_SYSTEM_QA.md` for open gates.

- Authorized local MySQL drill on 12 Oct: an AES-256-GCM encrypted mysqldump was decrypted/authenticated in memory, restored into a uniquely named separate schema, and exact row counts matched across 27 tables. The source sim_pbri counts matched before and after. The temporary backup and schema were removed; institutional backup schedule, off-host retention and recovery targets remain unverified.
