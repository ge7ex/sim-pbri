# SIM PBRI Full V1 QA — Working Report

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Product code under QA: `bebd6de4d0368c09438a937091826cf848179aa3` (`fix: confirm staff booking approval in review queue`). QA documentation is maintained on `backend/inertia-foundation`; no changes were merged to `main`.

## Verdict

**FULL V1 READY FOR SENIOR REVIEW: NOT YET READY.**
**REAL PILOT READY: NOT YET READY.**

The feature suite passed on product-code commit `bebd6de`; frontend build passed on 12 Oct; the route list contains no Export/download endpoints. The original `project_sim_flow_v12.md` and full gap analysis remain unavailable, so requirements traceability is incomplete. Synthetic browser coverage includes Admin responsive forms and Staff approval confirmation, rejection, queue empty state, report privacy, a full Lecturer Draft-to-Pending-to-Staff-approval workflow, plus a mobile-menu Escape fix. End-to-end keyboard/screen-reader and zero/error/loading checks across all role journeys remain outstanding.

## Automated evidence refreshed on 12 October 2026

| Check | Result | Scope / limit |
|---|---|---|
| Latest full suite `php artisan test --no-ansi` | PASS: 189 tests / 1,700 assertions | Guarded SQLite `:memory:`; refreshed after Staff approval feedback fix |
| Full PHPUnit suite via direct runner | PASS: 187 tests / 1,672 assertions | SQLite `:memory:`; explicit PHPUnit environment; Composer autoload preloaded because Artisan bootstrap path was not accepted in this sandbox; Laravel storage isolated in a unique temporary workspace directory and removed after run |
| `php artisan test --no-ansi` with `SESSION_ENCRYPT=true` | PASS: 187 tests / 1,672 assertions | Fresh standard Artisan/PHPUnit run on 12 Oct; PHPUnit config forced SQLite `:memory:`; isolated temporary storage removed after run |
| `npm run build` | PASS, 686 modules | Fresh production frontend compilation on 12 Oct 2026 |
| `php vendor/bin/pint --test --dirty` | PASS | Fresh 12 Oct 2026 check |
| `composer audit --no-interaction` | No security advisories | Fresh 12 Oct 2026 check |
| `npm audit --no-fund` | 0 vulnerabilities | Fresh 12 Oct 2026 check |
| `php artisan migrate:status --no-ansi` | 21 Ran, 0 Pending | Fresh read-only status inspection on 12 Oct; no migration executed and no application rows queried |
| `php artisan test --no-ansi` after demo seed timezone correction | PASS: 187 tests / 1,672 assertions | PHPUnit config and DatabaseSafety guard force SQLite `:memory:`; no MySQL test access |
| Current MySQL aggregate row counts | PASS: users=3, colleges=1, sim_resources=1, bookings=0, simulator_assets=0, booking_drafts=0 | Aggregate counts only; no record contents queried |
| Temporary MySQL encrypted backup/restore drill | PASS: AES-256-GCM encrypted dump (41,058 bytes), authenticated decrypt, restore into a newly created isolated schema, exact row counts matched across 27 tables, and source `sim_pbri` row counts matched before/after | User-authorized local one-time drill; encrypted file and temporary schema removed; does not verify institutional production backup policy, scheduled backups, permissions, or disaster recovery |
| Export/download source and route scan | PASS: 0 user-facing source matches and 0 route matches across 51 registered routes | `resources/js`, `app`, and `routes` scanned for download, Thai Export labels, and CSV/XLSX/PDF export terms; registered URI/name scan |
| MySQL report date SQL check | PASS: literal-only SELECT returned `2026-10-16` | Prior 12 Oct check; literal-only `DATE_ADD`; no table queried or changed |
| `git diff --check` | PASS | Source and QA documentation patch |

## Isolated browser evidence

All browser flows used the temporary synthetic SQLite demo created by `scripts/qa/phase-i-demo.ps1`; MySQL was not used. The latest demo was cleaned up and port 8766 was confirmed closed. Some role/browser rows below are prior verified runs; rows added in this refresh identify the current slice.

| Role / flow | Result | Limit |
|---|---|---|
| Admin resource list and add/edit room forms | PASS: no dimension inputs, dimensions or derived area visible | Synthetic SQLite fixture |
| Staff review | PASS: same-College contact and booking-time room snapshot visible; no Admin user link | Synthetic fixture only |
| Staff approve/reject | PASS: approval moved fixture to approved history; rejection with reason removed it from queue and set rejected status | Synthetic SQLite fixtures |
| Staff approval confirmation | PASS: accepted native confirmation; POST returned 302; visible `role=status` text “อนุมัติคำขอเรียบร้อยแล้ว”; queue empty state and Lecturer approved history verified | Fresh isolated synthetic SQLite fixture; no real accounts or institutional data |
| Lecturer booking room picker | PASS: dimensions/area absent; 24-hour time controls visible | Synthetic fixture |
| Lecturer Draft to Staff approval | PASS: saved Draft, resumed at `2026-10-16 09:00–11:00`, added course/scenario/simulator, submitted as Pending, Draft disappeared, Staff reviewed and approved it, and Approved history showed the booking | Synthetic single-College SQLite; owner-delete path not exercised in browser |
| Draft privacy | PASS: Staff queue did not show Lecturer Draft | Synthetic fixture |
| Lecturer booking form overflow | PASS at 1440, 1024, 768, and 390 CSS px | Measured document width did not exceed viewport |
| Staff review overflow | PASS at 390 CSS px | Staff 768/1024/1440 not freshly measured |
| Admin resources overflow | PASS at 1440/1024/768/390 | Document/body scroll widths matched viewport at each size; tables scroll within labeled regions |
| Admin resource validation error | PASS: server-side missing-name error renders as alert; field has `aria-invalid=true` and `aria-describedby` resolves to that alert | One Admin field verified in synthetic SQLite browser |
| Keyboard focus spot check | PASS: sampled Tab stop showed a 2.4px solid outline | Spot check only; not a complete keyboard or screen-reader run |
| Lecturer cancel | PASS: confirmation accepted; booking changed to cancelled and status history recorded | Synthetic SQLite fixture |
| Staff queue empty state after approval | PASS: pending count changed from 1 to 0 and empty-state message displayed | Synthetic fixture; same-College scope only |
| Staff report privacy | PASS: approved-activity statistics populated; no finance labels or export/download links in report page | Synthetic Staff account; current route scan also found 0 matches across 51 routes |
| Mobile navigation Escape | PASS at 390px: Escape closes menu, sets `aria-expanded=false`, returns focus to toggle, and leaves visible focus indicator | Synthetic Staff account; tested from menu toggle; screen-reader run not performed |
| Staff rejected-history zero state | PASS: role-filtered synthetic history displayed the empty-state message with zero results | Staff role, isolated SQLite only |
| Staff keyboard and form labels | PASS: Tab focus reached skip link, brand, menu and grouped navigation; review focus indicator visible; status/date filters have associated labels; no console errors on review page | Spot check, not a complete keyboard or screen-reader run |
| Lecturer booking form accessibility and capacity guard | PASS: all visible controls expose accessible names; keyboard focus outline is visible (2.4px solid); selecting 25 people for a 24-person room exposes the capacity status and leaves Submit disabled | Synthetic SQLite only; no request submitted; keyboard walk was a spot check, not an assistive-technology run |
| Admin → Staff rehearsal after timezone fixture correction | PARTIAL: Admin reports showed approved-only usage and Admin-only financial section; time rendered as Bangkok 09:00–11:00 and 13:00–15:00. Staff then saw the same pending request, contact and booking-time room snapshot, without user-management navigation. | Staff approval uses native `window.confirm`; CUA CDP input timed out on this path before a POST. Read-only SQLite counts remained 1 approved / 1 pending; cleanup removed isolated DB. Earlier same-run Lecturer Draft→Staff approval evidence remains |
| Keyboard/screen reader | Not verified end-to-end | Spot checks verified labels/focus; full keyboard/assistive-tech run remains open |

A later fresh Staff browser run accepted the native confirmation dialog, verified the success status and approved history, and measured no overflow at 390px; the earlier Admin→Staff browser-control timeout remains an incomplete run rather than a failed app action.

The first Playwright wrapper was blocked by WSL and `npx --no-install` found no installed executable. A cached Playwright CLI completed the flows above; session access was intermittent earlier in the run and later recovered. No app bug was inferred from browser-control failures.

## Decisions and data boundary

- Owner confirmed timezone `Asia/Bangkok`. Booking input, Draft resume, display, calendar date grouping, booking filters, and report day bounds use Bangkok. Persisted timestamps and global app timezone remain UTC. No timestamp was converted.
- A literal-only `SELECT` through the configured MySQL connection validated the MySQL `DATE_ADD` expression without querying app tables. SQL aggregation against real booking rows was not run in this check.
- Owner confirmed Drafts persist until the owner submits or deletes them; there is no automatic expiry.
- Owner directed dimensions remain hidden from every page, including Admin. Schema and values are preserved; Admin and booking Inertia props omit dimensions. No schema migration/data change was made for this decision.
- MySQL migrations 000017/000018 were previously applied; fresh status lists 21 migrations as Ran. The authorized backup/restore drill created and dropped only a uniquely named temporary schema; no application rows or source schema objects in `sim_pbri` were modified. Exact source row counts matched before and after. No reset, fixtures, or backfill occurred.
- The original v12 requirements file remains unavailable. The secondary 60-check report is an older analytical aid, not an authoritative substitute.

## Remaining gates

1. Obtain `project_sim_flow_v12.md` or an approved full requirements source and reconcile the traceability matrix.
2. Complete end-to-end keyboard/screen-reader and zero/error/loading checks across remaining role journeys. Mobile Escape close/focus return and Lecturer capacity/status guard pass; the remaining matrix is not complete.
3. Verify the institution-approved backup schedule, access controls, retention, off-host copy, and recovery-time objectives. A one-time local MySQL encrypted restore drill passed, but does not establish production operational readiness.
4. Rehearse the complete repeatable Admin/Staff/Lecturer walkthrough before issuing the Senior Review Candidate. Senior feedback is gathered after delivery, per the active handoff; owner acceptance remains a Real Pilot gate, not a prerequisite for Senior review. The Lecturer-to-Staff booking handoff already passes in isolated demo data.

No Export feature was enabled, `main` was not merged, no production deployment occurred, and no real Master Data was invented or seeded.
## Earlier follow-up verification at origin branch baseline 883b744

The earlier QA snapshot was based on `47552b4`. This follow-up found and fixed the account-management CSRF defect; the current product-code commit is `25d8a20`.

| Check | Result | Scope / limit |
|---|---|---|
| `php artisan test --no-ansi` | PASS: 187 tests / 1,672 assertions | Current branch; DatabaseSafety tests confirm test DB overrides that point to MySQL, a SQLite file, or a database URL are rejected. |
| `npm run build` | PASS: 686 modules | Production Vite build on current branch. |
| `php vendor/bin/pint --test --dirty` | PASS | Check-only formatter run. |
| `composer audit --no-interaction` | No security advisories | Current Composer lockfile. |
| `npm audit --no-fund` | 0 vulnerabilities | Current npm lockfile. |
| Laravel route inventory | 46 registered routes; 0 route URI/name matches for Export or download | User-facing Export source/route scan remains separately bounded; JS module `export` declarations are not Export features. |

### Additional isolated browser checks

- Admin report date range beyond 366 days displayed the field error “กรุณาเลือกช่วงวันที่ไม่เกิน 366 วัน”; submitting a valid November range with no fixture bookings displayed all zero totals and the report’s no-results messages.
- Staff could view room/equipment data in read-only mode; the page stated Staff cannot edit. Room dimensions remained absent.
- Staff navigation exposed no user-management link. Direct GET to `/app/users` returned HTTP 403.
- Staff reports contained usage statistics but no finance or Export/download text.
- Keyboard-only tabbing on the Staff review page reached the skip link, navigation, request selector, detail link, approval button and rejection-reason field; focus cycled back to the skip link with no keyboard trap. This was a keyboard spot check, not a full assistive-technology audit.
- The approval confirmation dialog was reached, but the browser-control bridge timed out while handling it. A read-only query of the temporary SQLite demo still showed one Approved and one Pending fixture; this run did not record a new approval and the approval click was not retried.
- Demo Cleanup confirmed port 8766 was closed, then removed the isolated TEMP SQLite directory.

That earlier follow-up changed no source code or MySQL data. In the subsequent approval-feedback slice, the Staff review page and its feature test were changed and committed as `bebd6de`; browser and feature-suite verification passed (189 tests / 1,700 assertions). Full screen-reader coverage, keyboard/error/loading coverage across every role journey, and the source-complete requirements matrix remain open. The Senior Review and Real Pilot verdicts remain **NOT YET READY**.

### Follow-up QA — 12 October 2026

Code baseline: `25d8a20` (`fix: refresh account management csrf token`), committed locally on `backend/inertia-foundation`.

- Reproduced Admin account creation returning HTTP 419 immediately after login. Login regenerates the session CSRF token, while the account page's manual `fetch` read the old `<meta>` value that Inertia does not refresh. The account-management GET now includes the current token in its page props and the form uses that value.
- Added a regression test that confirms the token rotates after login and the next account-management page receives the new token. The duplicate-email validation message now renders in Thai.
- Fresh verification: `php artisan test --no-ansi` — 189 passed / 1,690 assertions; `npm run build` — 686 modules; `php vendor/bin/pint --test --dirty` — pass; `git diff --check` — pass.
- Synthetic SQLite browser verification: Admin logged in and submitted a duplicate email immediately without a hard reload. The request returned the expected Thai inline error, with `aria-invalid=true` and `aria-describedby` linked to that error; the synthetic account count remained 3. The same session displayed approved-only usage statistics and Admin-only financial statistics.
- The Staff login attempt in this run did not authenticate from the generated TEMP credential file, so Staff role checks were not repeated in this slice. No credential reset was performed. Cleanup confirmed port 8766 was closed and removed the isolated TEMP demo.
- No MySQL connection or data change occurred. Composer audit was rejected before execution by automatic review rate limiting (429); npm audit was not rerun. Prior dependency-audit results apply to unchanged lockfiles, but are not fresh results for this follow-up.

Full screen-reader coverage, complete cross-role keyboard/error/loading checks, source-complete v12 traceability, a full-repository Codex Security scan, and the integrated Admin/Staff/Lecturer Senior walkthrough remain open. The diff-only Codex Security scan `3bb54aee-bdf1-41bd-a7ec-d3d2b90d587a` reviewed the three changed source files from `883b744` through `048828f` and completed with 0 findings; it does not replace a full-repository scan. Verdicts remain **FULL V1 READY FOR SENIOR REVIEW: NOT YET READY** and **REAL PILOT READY: NOT YET READY**. At this report's baseline the branch was three commits ahead of origin; no merge to `main` was made.
