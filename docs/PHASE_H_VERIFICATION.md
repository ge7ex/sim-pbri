# Phase H verification — 2026-10-09

Repository: `C:/xampp/htdocs/sim-pbri`. Branch: `backend/inertia-foundation`.
User additionally authorized reversible room closure and reviewing earlier phases, fixing only confirmed failures. No merge into main.

## Required completion evidence

| Item | Implementation / evidence |
| --- | --- |
| 1. Starting HEAD | `e595d7e03da212780b07d790c34cfe49628ca051`, initially clean and up to date |
| 2. Final HEAD | Source HEAD `69be805410f1ca04b612900509c6f17877744368`; evidence commit follows. Resolve final HEAD with `git rev-parse HEAD`; final remote equality is verified after push and reported in chat. |
| 3. Changed files | Listed below; evidence files added separately |
| 4. Permission | Dedicated `report.view`, Staff/Admin only; auth, access-profile and permission middleware |
| 5. Filters | Current month by default; validated dates/status/own-College room, actual simulator asset, course and scenario. No College selector. Client college_id never controls scope. Inclusive dates become [from midnight, day after to midnight), overlap predicates; maximum 366 days. Existing app timezone preserved; Asia/Bangkok boundaries covered in tests. |
| 6. Booking summary | SQL status aggregation: total, pending, approved, rejected, cancelled; selected status applies to workflow and approved subset |
| 7. Rooms | Top 10 approved booking counts, clipped hours, participant sum/average, current capacity, average participant count / capacity percentage, last start. Closed rooms remain in historical statistics. |
| 8. Simulators | Actual selected asset counts/hours, own-College type label, last start/current status; recommendations excluded |
| 9. Equipment | Approved booking count and SUM(pivot.quantity), Top 10 by quantity; custom equipment excluded |
| 10. Course/Scenario | Stored booking selections; own-College labels; course and scenario preferences remain independent |
| 11. Participants/capacity | SUM counts repeated attendance across requests, not unique people; AVG only known counts; missing count explicit. Capacity ratio is not occupancy over time. |
| 12. Admin financial | Current own-College asset snapshot; status counts; date-scoped maintenance count/cost. Purchase value and current book value reuse StraightLineDepreciation with integer-cent sums and chunks of 200. Unknown valuation count explicit. Other booking filters do not change this snapshot. |
| 13. Staff privacy | Feature test asserts no financial-column/maintenance query and no assetStatistics root prop. Browser confirms payload and section absent despite populated Admin financial fixture. |
| 14. College isolation | Feature tests cover foreign/malformed labels, all rankings/options and rejected foreign filters; browser rejects FOREIGN PRIVATE marker. Earlier Booking/Scenario relation leaks fixed and regression tested. |
| 15. Zero state | Zero counts, safe null averages, empty rankings, textual chart empty state; browser date range with no data passes. MySQL real zero-booking query passes. |
| 16. Chart/UI | Existing primitives/tokens, native SVG dashed workflow / solid approved trend, visible legend/context and semantic daily table. Count each booking once on first overlapping day; hours clip to range. Single-day trend uses visible points. |
| 17. Responsive | Admin + Staff Statistics at 1440/1024/768/390; no document overflow. Chart locally scrolls on narrow screens. Existing 9 Admin + 9 Staff + 5 Lecturer pages at 4 widths: 92 checks pass. |
| 18. Accessibility | One h1, section h2, labelled controls, keyboard filter order, focus result panel, native details, table headings/caption, chart title/description + text, non-color-only status and legend, reduced-motion transition 0s. Screenshot review at desktop/mobile passed; not a formal accessibility certification. |
| 19. Ponytail | Actual installed Ponytail + Ponytail Review read and applied. Minimal query/request grouping, no dependencies/migrations, focused regression tests, independent source review. Confirmed privacy bugs fail before fix (2 tests / 16 assertions), pass after (2 / 127). Verdict: Ship within documented limits. |
| 20. Taste | Actual design-taste-frontend v2 read. Its primary workflow excludes dashboards/data tables; contextual spacing/hierarchy/density guidance applied under this handoff, not claimed as full dashboard Taste workflow. Established navy/slate/Thai typography retained. |
| 21. UI/UX Pro Max | Installed skill read; local design-system/chart/UX/Vue searches used. Marketing-oriented outputs were unsuitable and not applied. Date range filters, progressive secondary controls, useful empty states, line chart with textual fallback reviewed. |
| 22. Impeccable | Actual installed skill and project .impeccable.md context read. Desktop/mobile screenshot review; shared tokens, restrained borders, clear metric/ranking structure, legible chart locally scrolls. No unrelated redesign. |
| 23. Tests | Fresh final full suite: 156 passed / 1,408 assertions; guarded SQLite :memory:. Covers prior phases, permission/scope/status/overlap/quantity/financial/null/room closure. |
| 24. Build | Fresh final npm run build: PASS, 680 modules; no new dependency |
| 25. Pint | --test --dirty passed before commits; explicit changed PHP/module/test paths also passed after commits |
| 26. Composer audit | Fresh command: No security vulnerability advisories found |
| 27. npm audit | Fresh command: 0 vulnerabilities |
| 28. Migrations | Runtime migrate:status: all Ran through 000014_add_room_images. No new migration. No destructive runtime command. |
| 29. Git status | Source tree clean after four implementation commits; evidence commit and final clean check follow |
| 30. Pushed commits | 9e17084 room closure; be0a4fe relation privacy + nullable type; aa42ac2 report backend/tests; 69be805 statistics UI. Evidence commit follows; push exclusively to origin/backend/inertia-foundation and verify remote HEAD. |
| 31. Export | Source search in routes/modules/UI finds only TypeScript export function syntax. Registered route URI/name inspection has no export/download/CSV/Excel/PDF. Browser controls absent. No endpoint, hidden/disabled control or user-facing export documentation added. Protected inline room images remain existing functionality. |

## Reversible room closure

`disabled` / ปิดการใช้งาน is distinct from `maintenance` / ปิดปรับปรุง. Admin only can close and reopen; equipment retains previous statuses. Booking creation/approval/recall/availability block closed rooms using existing readiness rules. No history, room image, audit or metadata is deleted.

Focused feature suite: 5 tests / 92 assertions. Normal isolated-browser Admin status cycle confirms disabled label, disabled select-room button, readable booking history, reopening to ready and preserved metadata (updated_at appropriately changes). No runtime room status changed.

## Review/fix loop across previous phases

Ran the complete existing suite and responsive route matrix. Kept passing behavior unchanged. Independently reviewed prior Booking, Calendar, Review, Scenario and Simulator relation projections.

- Confirmed foreign resource/user names leaked through hydrated relations on history/calendar/detail/review, and Scenario recommendations admitted foreign resources or rooms. Two focused tests failed before source changes, then passed (127 assertions); own-College names/quantities and stored snapshots/history remain intact.
- Confirmed Simulator UI crashed on a nullable type returned by defensive backend projection: browser before TypeError; after safe fallback with zero page errors. Fixture changed browser response only; no DB constraints disabled in runtime.
- Report invalid range errors were lost after GET navigation. preserveState retains validation errors; Admin/Staff filter cycles now pass.
- Single-day SVG polyline had only one point; visible circles added and browser confirms both series points plus text table.
- QA selector and stale initial Inertia-script comparisons caused test-script failures; fixed QA scripts only. updated_at changes are expected after status edits. No product changes for those harness failures.

## Runtime safety and performance limits

Read-only MySQL results: users 3, resources 1, bookings 0 before and after; Staff no financial section, Admin allowed section; all actual MySQL aggregate branches execute; derived overlap-hour calculation returns 2 hours. Existing indexes selected by EXPLAIN (see JSON); no new index without evidence.

SQL does booking aggregates, bounded daily trend <=366 and Top 10 rankings; no booking collection loaded for PHP counting. Filter catalogs load own-College master records, and Admin valuation scans own assets in 200-row chunks. Empty real DB EXPLAIN is not a production-volume load test. No new Codex Security server scan/report was run; source review, tests and dependency audits are not ISO certification or an exhaustive security assurance.

Browser QA used isolated SQLite synthetic accounts on 8765, not runtime MySQL/8000. Session closed, identified QA process stopped, isolated DB/storage/settings removed. Sanitized results retained in docs/qa JSON. No credentials or app keys included.

## Next phase

User authorized continuing future phases automatically and fixing only failures. No Phase I-or-later requirements were found in supplied Phase H handoff, README or docs. Await next handoff for dependent implementation; do not invent role/workflow/Export requirements. Phase H and the defined earlier-phase regression review are complete subject to final verified push.

## Changed source files

- `app/Core/Enums/AppPermission.php`
- `app/Core/Enums/UserRole.php`
- `app/Modules/Booking/Http/Controllers/BookingController.php`
- `app/Modules/Booking/Queries/BookingCalendarQuery.php`
- `app/Modules/Booking/Queries/BookingIndexQuery.php`
- `app/Modules/Booking/Queries/BookingReviewQuery.php`
- `app/Modules/Report/Http/Controllers/ReportController.php`
- `app/Modules/Report/Http/Requests/ReportFilterRequest.php`
- `app/Modules/Report/Queries/AdminAssetStatisticsQuery.php`
- `app/Modules/Report/Queries/ReportStatisticsQuery.php`
- `app/Modules/Report/Routes/web.php`
- `app/Modules/Scenario/Http/Controllers/ScenarioManagementController.php`
- `app/Modules/SimResource/Enums/SimResourceStatus.php`
- `app/Modules/SimResource/Http/Requests/StoreSimResourceRequest.php`
- `app/Modules/SimResource/Http/Requests/UpdateSimResourceRequest.php`
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Modules/Booking/Pages/Create.vue`
- `resources/js/Modules/Report/Components/BookingTrend.vue`
- `resources/js/Modules/Report/Components/UsageRanking.vue`
- `resources/js/Modules/Report/Pages/Index.vue`
- `resources/js/Modules/SimResource/Pages/Index.vue`
- `resources/js/Modules/Simulator/Pages/Index.vue`
- `routes/web.php`
- `tests/Feature/Booking/BookingRelationPrivacyTest.php`
- `tests/Feature/Booking/RoomDisabledTest.php`
- `tests/Feature/Report/ReportStatisticsTest.php`
- `tests/Feature/Scenario/ScenarioRelationPrivacyTest.php`

Machine-readable evidence: [PHASE_H_VERIFICATION.json](qa/PHASE_H_VERIFICATION.json).
