# Phase I Product Completion Plan

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Current reviewed HEAD: `d439e6f393eb513f23b24a13a034e34344275d3e`

## Evidence boundary

The corrected Phase I handoff is the active scope. The original `project_sim_flow_v12.md` and full gap analysis are still unavailable. A user-provided explanatory HTML report with 60 analytical checkpoints exists in Downloads, but it references the older `d2e84e7` implementation snapshot and is not authoritative wording. `REQUIREMENTS_TRACEABILITY_V1.md` uses it as a gap-finding aid and does not claim exhaustive source traceability. Add v12 and reconcile it before calling the matrix source-complete.

No uncommitted user changes were present at the original baseline. The branch and origin matched then. Tests are guarded to SQLite `:memory:`. The first local MySQL migration inventory showed 17 migrations; the current read-only status shows 19, including additive migrations 000015/000016. No application fixtures were seeded into MySQL.

## Increments

| Increment | Scope | Entry evidence | Ready condition | Status |
|---|---|---|---|---|
| I-0 | Baseline and traceability | Current reviewed HEAD `d439e6f`; fresh suite 175 tests / 1,595 assertions; v12 source absent; secondary 60-check report found | Every v12 core requirement mapped to evidence/decision | In progress: handoff and secondary-report gaps mapped; exact v12 wording and full gap analysis remain unavailable |
| I-1 | Real account operations | Route inventory had login/logout only; local dev seeder is environment-guarded | College-scoped Admin account create/edit/disable/reset; bootstrap/recovery; forced password change; last-Admin protection; feature tests and isolated browser QA | Done for code: 11 focused tests within fresh full suite; Admin login/menu/create/one-time-secret-hide verified in isolated Chrome. Browser password-rotation flow not performed; feature tests cover it. |
| I-2 | Room, booking, and review gaps | Editable requester display, room snapshots and review contact implemented; secondary report flags Draft and dimensions | Implement clear safe requirements; document owner-dependent retention/units; retain no-auto-classification rule | In progress: implemented booking display/context; Draft is confirmed absent and likely required, but retention semantics need owner input; dimensions need units/area rule |
| I-3 | End-to-end UX and reliability | Role-specific route and test inventory plus I-1/I-2 | Admin, Staff, Lecturer flows verified in isolated browser at 1440/1024/768/390; responsive/accessibility defects fixed | In progress: prior desktop checks exist; this turn's browser tool blocked localhost navigation by policy. Approval and all breakpoints remain unverified. |
| I-4 | Security and operations | Isolated QA, no-Export audit, dependency and migration baseline | Fresh suite/audits; deployment, backup/restore drill when safe, rollback and timezone docs evidence actual results | In progress: tests/build/audits pass; all 19 local migrations are marked Ran; this turn only read migration status. Synthetic SQLite restore passed; production MySQL backup/restore, browser matrix, and timezone decision remain open. |
| I-5 | Senior Review Candidate | Completed application and isolated repeatable demo environment | Script, feedback log, QA report/JSON, pilot gates and final review verdict | In progress: SQLite setup/cleanup rerun passed after cleanup PID-reuse guard fix; Senior readiness remains NOT YET READY pending source traceability, timezone decision and full browser QA. |

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
- Current verified HEAD is `d439e6f`; the worktree contains a pending, tested cleanup-script change. Cleanup now ignores stale/unverified PIDs, stops only verified demo processes, and refuses deletion while port 8766 remains open.
- Current fresh checks: 175 tests / 1,595 assertions; Vite 686 modules; Pint, Composer audit, npm audit, migration-status read, and route Export scan pass. Browser use was blocked by computer-use policy for localhost; it is not a website failure result.
- Current code sets application timezone to UTC while booking fields are local datetime inputs and frontend display follows browser timezone. This is a P0 owner decision because changing the configured timezone can alter interpretation of existing timestamp values.
- `scripts/qa/verify-phase-i-demo-restore.php` passed against synthetic demo data: SQLite integrity passed and table row counts matched across 26 tables after backup and restore-copy. This does not verify production MySQL backup/restore.
