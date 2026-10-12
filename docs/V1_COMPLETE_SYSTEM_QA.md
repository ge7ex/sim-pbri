# SIM PBRI Full V1 QA — Working Report

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Code under QA: `2332b11` (`fix: close mobile navigation on escape`), pushed to `origin/backend/inertia-foundation`.

## Verdict

**FULL V1 READY FOR SENIOR REVIEW: NOT YET READY.**
**REAL PILOT READY: NOT YET READY.**

The feature suite and frontend build pass on `2332b11`; the route list contains no Export/download endpoints. The original `project_sim_flow_v12.md` and full gap analysis remain unavailable, so requirements traceability is incomplete. Synthetic browser coverage includes Admin responsive forms and Staff approval, rejection, queue empty state, report privacy, a full Lecturer Draft-to-Pending-to-Staff-approval workflow, plus a mobile-menu Escape fix. End-to-end keyboard/screen-reader and zero/error/loading checks across all role journeys remain outstanding.

## Automated evidence refreshed on 12 October 2026

| Check | Result | Scope / limit |
|---|---|---|
| Full PHPUnit suite via direct runner | PASS: 187 tests / 1,672 assertions | SQLite `:memory:`; explicit PHPUnit environment; Composer autoload preloaded because Artisan bootstrap path was not accepted in this sandbox; Laravel storage isolated in a unique temporary workspace directory and removed after run |
| Same suite with `SESSION_ENCRYPT=true` | 187 passed / 1,672 assertions | Prior 12 Oct QA snapshot; guarded SQLite `:memory:`; not rerun at `2332b11` |
| `npm run build` | PASS, 686 modules | Fresh production frontend compilation on 12 Oct 2026 |
| `php vendor/bin/pint --test --dirty` | PASS | Fresh 12 Oct 2026 check |
| `composer audit --no-interaction` | No security advisories | Fresh 12 Oct 2026 check |
| `npm audit --no-fund` | 0 vulnerabilities | Fresh 12 Oct 2026 check |
| `php artisan migrate:status --no-ansi` | 21 Ran, 0 Pending | Fresh read-only status inspection on 12 Oct; no migration executed and no application rows queried |
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
| Keyboard/screen reader | Not verified end-to-end | Source labels and test evidence exist; no full assistive-tech run |

The first Playwright wrapper was blocked by WSL and `npx --no-install` found no installed executable. A cached Playwright CLI completed the flows above; session access was intermittent earlier in the run and later recovered. No app bug was inferred from browser-control failures.

## Decisions and data boundary

- Owner confirmed timezone `Asia/Bangkok`. Booking input, Draft resume, display, calendar date grouping, booking filters, and report day bounds use Bangkok. Persisted timestamps and global app timezone remain UTC. No timestamp was converted.
- A literal-only `SELECT` through the configured MySQL connection validated the MySQL `DATE_ADD` expression without querying app tables. SQL aggregation against real booking rows was not run in this check.
- Owner confirmed Drafts persist until the owner submits or deletes them; there is no automatic expiry.
- Owner directed dimensions remain hidden from every page, including Admin. Schema and values are preserved; Admin and booking Inertia props omit dimensions. No schema migration/data change was made for this decision.
- MySQL migrations 000017/000018 were previously applied; current read-only status lists 21 migrations as Ran. No MySQL reset, fixture, backfill, or write occurred in this QA slice.
- The original v12 requirements file remains unavailable. The secondary 60-check report is an older analytical aid, not an authoritative substitute.

## Remaining gates

1. Obtain `project_sim_flow_v12.md` or an approved full requirements source and reconcile the traceability matrix.
2. Complete end-to-end keyboard/screen-reader and zero/error/loading checks across remaining role journeys. Mobile Escape close/focus return now passes; the remaining matrix is not complete.
3. Verify the approved production MySQL backup/restore process; the existing isolated restore drill covers SQLite only.
4. Complete the final Admin-led Senior walkthrough and owner acceptance before marking a Senior Review Candidate or real Pilot ready. Lecturer-to-Staff booking handoff now passes in the isolated demo.

No Export feature was enabled, `main` was not merged, no production deployment occurred, and no real Master Data was invented or seeded.
