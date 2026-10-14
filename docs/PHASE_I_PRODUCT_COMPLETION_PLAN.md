# Phase I Product Completion Plan

Date: 14 October 2026 (I-0 source baseline refresh; prior dated execution evidence retained)
Branch: `backend/inertia-foundation`
Historical automated QA product-code commit: `bebd6de4d0368c09438a937091826cf848179aa3` (`fix: confirm staff booking approval in review queue`) on `backend/inertia-foundation`. The branch has local commits not yet pushed and has not been merged to `main`. The CSRF fix, duplicate-email localization, and approval confirmation are covered by 189 tests / 1,700 assertions; a separate Codex Security diff scan of `883b744..048828f` completed with 0 findings across the three changed source files. This is not a full-repository scan.


## I-0 checkpoint — 14 October 2026

Fresh read-only Git baseline: `backend/inertia-foundation`, HEAD `e3983e8450cbeab2123eebf54e3579a1cb922d1e`, clean, ahead 9 after successful git fetch origin (origin HEAD 883b744; 9 local-only, 0 remote-only); no pull, push or merge. The earlier dated sections below retain historical evidence and counts.

I-0 now maps all explicit handoff scope and confirmed owner decisions to 35 canonical requirements, including 60/60 secondary analytical topics. Added explicit verification/backlog and artifact registers; corrected the resolved dimensions/Draft decisions and the unsupported Simulator CRUD wording. Source fingerprints and evidence-file checks are in `docs/qa/I0_BASELINE_VERIFICATION.json`. No new test/build/browser/database execution took place for this documentation-only slice.

I-0 is DONE against the accepted V1 baseline. On 14 October the owner explicitly approved the 12 October handoff, confirmed decisions and requirements matrix as the replacement baseline. Exact v12 comparison remains unavailable and is not claimed. This does not mark I-1 through I-5 complete; remaining implementation/verification stays in its own package. Full V1 and Real Pilot verdicts remain NOT YET READY.

## Evidence boundary

The corrected Phase I handoff is the active scope. The original `project_sim_flow_v12.md` and full gap analysis are still unavailable. A user-provided explanatory HTML report with 60 analytical checkpoints exists in Downloads, but it references the older `d2e84e7` implementation snapshot and is not authoritative wording. `REQUIREMENTS_TRACEABILITY_V1.md` uses it as a gap-finding aid and does not claim exhaustive source traceability. The owner approved the handoff, confirmed decisions and matrix as the current V1 baseline on 14 October; compare recovered v12 if it becomes available, without claiming original-source completeness.

No uncommitted user changes were present at the original baseline. The branch and origin matched then. Tests are guarded to SQLite `:memory:`. The recorded 12 October MySQL migration-status snapshot shows 21 applied migrations, including additive migrations 000015–000018; 000017 created the empty Draft table and 000018 added nullable room dimensions. No application fixtures or measurements were seeded into MySQL.

## Increments

| Increment | Scope | Entry evidence | Ready condition | Status |
|---|---|---|---|---|
| I-0 | Baseline and traceability | Source baseline e3983e8; handoff and confirmed owner decisions; missing v12 | Complete accepted-source mapping and evidence/decision register | DONE: owner-approved handoff/decisions/matrix baseline on 14 October; 35 canonical requirements, 60/60 secondary analytical topics mapped and evidence-file checks recorded. Exact v12 comparison unavailable; not claimed. See REQUIREMENTS_TRACEABILITY_V1.md and docs/qa/I0_BASELINE_VERIFICATION.json. |
| I-1 | Real account operations | Existing account module plus fresh 14 October checks | College-safe provision/edit/deactivate/reset, bootstrap/recovery, forced rotation, last-Admin protection and browser evidence | DONE for accepted local account workflow: 13 account tests included in 64 tests / 475 assertions; 15 Chrome checks passed including forced rotation, reset invalidating two sessions, deactivation/reactivation and direct Staff 403. See I1_I2_VERIFICATION.md; email integration remains an operational gate. |
| I-2 | Room, booking, and review gaps | Accepted I-0 decisions plus fresh 14 October checks | Required gaps implemented; optional differences explicitly tracked | DONE for accepted scope: requester/actor separation, snapshots/contact and hidden stored dimensions covered by fresh targeted tests; 7 Chrome checks passed including owner Draft delete, non-reservation, stale-conflict preservation and corrected submission. Draft retention/Bangkok/dimension decisions remain as approved. I-3/I-4/I-5 verification gates are separate. |
| I-3 | End-to-end UX and reliability | Owner-approved I-0 baseline and 14 October evidence | Cross-role responsive, keyboard, labels, loading/error checks | COMPLETE for agreed scope: 220 page samples, 110 Tab walks, 114 Chrome accessibility-tree samples, 19 tests / 132 assertions; five reproduced defects fixed. Native NVDA/Narrator speech is optional additional assurance, NOT VERIFIED. See docs/I3_VERIFICATION.md. |
| I-4 | Security and operations | Originalscan6a914e7 plus appfixc4502ce/sidebar01ac3c6 | Finish exact-source scan/delta and final QA | IN PROGRESS: actualcontroller race red3/green6 fixed with Draft atomicity; full190/1705 twice pass; sidebar12 Chrome samples/build/noExport51routes pass. Same scan has acceptedmodel+1validatedoriginalfinding, remaining18paths. Stop10% quota; resume I4_I5_RESUME_CHECKPOINT_2026-10-14.md then I5. |
| I-5 | Senior Review Candidate | Completed application and isolated repeatable demo environment | Script, feedback log, QA report/JSON, pilot gates and final review verdict | In progress: SQLite setup/cleanup and same-run Lecturer Draft→Pending→Staff approval workflow pass. Senior readiness remains NOT YET READY pending source-complete traceability, full keyboard/screen-reader and cross-role error/loading QA, plus rehearsal of the integrated Admin/Staff/Lecturer walkthrough (Admin→Staff ran through review details; approval click uses native window.confirm, which CUA CDP timed out on; no request was submitted in that run; prior Lecturer→Staff approval flow passed). Senior feedback occurs after delivery; institution-approved recovery operations and owner acceptance are separate Real Pilot gates. |

### Follow-up defect repair — 12 October 2026

- Synthetic Admin UAT reproduced a 419 on account POST immediately after login because the account form read a stale CSRF token from the root HTML meta tag after Laravel regenerated the session token. `UserManagementController::index` now supplies the active session token in the page props, and the account page submits that current value.
- `StoreUserRequest` now reports duplicate email in Thai. Browser validation after a post-login submission exposed the inline message and its `aria-invalid` / `aria-describedby` relationship.
- Regression coverage and current full suite: 189 tests / 1,690 assertions. Production Vite build: 686 modules. Pint and `git diff --check` pass.
- Browser validation and Admin report checks used only synthetic SQLite. A fresh synthetic browser fixture completed Staff approval after accepting the native confirmation dialog; the visible success status and approved Lecturer history were verified.
- The recorded Codex Security diff scan covers only its stated earlier base/head and does not include this UI-only patch; no claim is made that this patch had a new Codex Security scan. Fresh Composer audit was not run in this slice. The temporary browser fixture used SQLite and was removed; MySQL was not accessed or changed.

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

### Staff approval feedback fix — 12 October 2026

- Staff approval previously redirected successfully but the review page did not display the flashed success message. The review page now renders the confirmation as an accessible `role="status"`; a feature test asserts the session message and next-page Inertia prop.
- Browser UAT on the guarded synthetic SQLite fixture confirmed the approval POST redirected (302), the status “อนุมัติคำขอเรียบร้อยแล้ว” appeared, the queue empty state rendered, and the Lecturer approved history contained the request. No browser JavaScript errors; 390px layout had no horizontal overflow.
- Fresh verification: 189 tests / 1,700 assertions, Vite build 686 modules, Pint, and `git diff --check` pass. The temporary fixture was removed and port 8766 closed.
- Implemented and committed locally as `bebd6de`; branch is five commits ahead of origin. Not pushed or merged. The prior Codex Security scan does not cover this patch.

### Handoff update — 12 October 2026

- Current implementation commit: `0b3bf6c536a0805a68c1b121a53ab49003ec2fa3`; eight commits ahead of origin, not pushed or merged.
- Admin filtered empty results now use an accessible status region, verified in the browser. Build and diff-check pass; a new PHP-suite run was blocked by automatic approval review (429), so prior PHP results remain tied to the preceding commit.
- A MySQL cache-clear incident occurred during local UI QA; see `docs/incidents/2026-10-12-cache-clear-during-ui-qa.md`. The cache baseline before clearing is unknown. No restoration was attempted.
- Phase I remains incomplete pending source-complete traceability, a real screen-reader run, remaining cross-role error/loading QA, and review of the cache-clear incident.

## I-1 / I-2 closure — 14 October 2026

Accepted functional packages I-1 and I-2 passed the fresh targeted and synthetic Chrome checks in docs/I1_I2_VERIFICATION.md and its JSON evidence. No application changes were required. Initial runner/locator issues were corrected in the QA harness, not patched into the app. Demo/test storage was cleaned; port 8766 closed. I-3 through I-5 and Full V1/Real Pilot verdicts remain open. No MySQL access, build, push or main merge occurred in this slice.
