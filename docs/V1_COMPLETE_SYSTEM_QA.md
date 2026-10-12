# SIM PBRI Full V1 QA — Working Report

Date: 12 October 2026
Branch: `backend/inertia-foundation`
Code under QA: `cafca43` (`fix: announce validation errors accessibly`); QA documentation follows in this branch.

## Verdict

**FULL V1 READY FOR SENIOR REVIEW: NOT YET READY.**
**REAL PILOT READY: NOT YET READY.**

The feature suite, frontend build, scoped style check, dependency audits, migration-status inspection, and synthetic role workflows pass. The original `project_sim_flow_v12.md` and full gap analysis remain unavailable, so requirements traceability is incomplete. Browser coverage includes Admin responsive breakpoints and synthetic approval, rejection and cancellation flows; keyboard/screen-reader and zero/error/loading browser-state checks remain outstanding. Production MySQL backup/restore and real Master Data are not verified.

## Fresh automated evidence

| Check | Result | Scope / limit |
|---|---|---|
| `php artisan test --no-ansi` | 187 passed / 1,672 assertions | Guarded SQLite `:memory:` only |
| Same suite with `SESSION_ENCRYPT=true` | 187 passed / 1,672 assertions | Guarded SQLite `:memory:` only |
| `npm run build` | PASS, 686 modules | Production frontend compilation |
| Scoped `php vendor/bin/pint --test --dirty` | PASS | Changed PHP files |
| `composer audit --no-interaction` | No advisories | Current lockfile |
| `npm audit --no-fund` | 0 vulnerabilities | Current lockfile |
| `php artisan migrate:status` | 21 migrations Ran | Read-only status inspection; no migration executed |
| Export/download route scan | 0 URI/name matches | Registered route list; source scan finds only TypeScript `export` syntax |
| MySQL report date SQL check | PASS: literal-only SELECT returned `2026-10-16` | Validated `DATE(DATE_ADD(..., INTERVAL 7 HOUR))`; no table queried or changed |
| `git diff --check` | PASS | Source and QA documentation patch |

## Isolated browser evidence

All flows used the temporary synthetic SQLite demo created by `scripts/qa/phase-i-demo.ps1`; the MySQL runtime was not used by browser tests. The demo was cleaned up and port 8766 was confirmed closed.

| Role / flow | Result | Limit |
|---|---|---|
| Admin resource list and add/edit room forms | PASS: no dimension inputs, dimensions or derived area visible | Synthetic SQLite fixture |
| Staff review | PASS: same-College contact and booking-time room snapshot visible; no Admin user link | Synthetic fixture only |
| Staff approve/reject | PASS: approval moved fixture to approved history; rejection with reason removed it from queue and set rejected status | Synthetic SQLite fixtures |
| Lecturer booking room picker | PASS: dimensions/area absent; 24-hour time controls visible | Synthetic fixture |
| Lecturer Draft | PASS: saved and resumed `2026-10-16 09:00–11:00`; values remained in Bangkok local time | Submit/delete were not exercised in this browser run; feature tests cover lifecycle |
| Draft privacy | PASS: Staff queue did not show Lecturer Draft | Synthetic fixture |
| Lecturer booking form overflow | PASS at 1440, 1024, 768, and 390 CSS px | Measured document width did not exceed viewport |
| Staff review overflow | PASS at 390 CSS px | Staff 768/1024/1440 not freshly measured |
| Admin resources overflow | PASS at 1440/1024/768/390 | Document/body scroll widths matched viewport at each size; tables scroll within labeled regions |
| Admin resource validation error | PASS: server-side missing-name error renders as alert; field has `aria-invalid=true` and `aria-describedby` resolves to that alert | One Admin field verified in synthetic SQLite browser |
| Keyboard focus spot check | PASS: sampled Tab stop showed a 2.4px solid outline | Spot check only; not a complete keyboard or screen-reader run |
| Lecturer cancel | PASS: confirmation accepted; booking changed to cancelled and status history recorded | Synthetic SQLite fixture |
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
2. Complete keyboard, accessibility and zero/error/loading checks across remaining role journeys.
3. Verify the approved production MySQL backup/restore process; the existing isolated restore drill covers SQLite only.
4. Complete final Senior walkthrough and owner acceptance before marking a Senior Review Candidate or real Pilot ready.

No Export feature was enabled, `main` was not merged, no production deployment occurred, and no real Master Data was invented or seeded.
