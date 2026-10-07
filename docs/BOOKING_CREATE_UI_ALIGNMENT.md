# Booking Create preview alignment correction — 2026-10-07

## User correction

The previous UI checkpoint primarily shared styling; the booking form's structure still differed from preview. This follow-up follows `sim-pbri-preview/src/modules/booking/pages/BookingCreatePage.vue` at main `2314dd2f711bdccb427a6afcc65764703edd51f3`, verified against the remote HEAD.

## Changes

- Room-first layout: large room card, visual placeholder, actual description/building/floor/location/capacity, previous/next controls, selected-room summary and change-room action. Ready rooms are presented first; unavailable rooms remain visible with selection disabled.
- The card uses an explicitly labeled placeholder because the actual Room domain has no image field. No preview room photos, fixed room simulators, suitability data or fictional operational values were imported.
- Date and time are separate fields in a four-column desktop row. Time24Field uses hour 00–23 and minute 00–59 selects, including midnight and arbitrary minute values. No half-hour or office-hour restriction was introduced.
- Choosing a room before entering dates expresses a preference only. Real room availability still gates submission; changing dates runs the original abortable availability checks. Capacity, status, phone, actor-derived college and server-side rechecks remain authoritative.
- Optional Course/Scenario/Simulator fields remain together. Existing recommended-equipment semantics and simulator checks stay intact.
- Catalog equipment uses select/quantity/add controls and editable/removable rows closer to preview. Custom equipment remains supported separately inside the equipment section. Quantities retain the existing exclusive/count clamps.
- Account/college are readonly; unlike preview, requester impersonation is not introduced. Notes are a separate panel.
- Shared formatBookingDateTime explicitly uses h23 for Dashboard, history, detail, review and calendar, including audit timestamps. Browser-local timezone behavior and UTC submission conversion remain unchanged.
- Mobile room navigation moves below the full-width card; fields stack and focus states remain.

## Verification

- `php artisan test --compact`: 117 passed / 874 assertions; SQLite memory guard remains active.
- `npm run build`: passed (665 modules).
- Browser QA on the real current app with isolated SQLite and synthetic Lecturer: 1440, 1024, 768 and 390px all without document overflow; first section is Room; no datetime-local fields remain; zero page errors.
- 24 hour options / 60 minute options, maintenance selection disabled, overlapping interval blocked, invalid end-before-start blocked, over-capacity blocked.
- Scenario recommendations produce expected quantity 2; additional catalog quantity 2 can be added and removed; custom quantity 2 submits correctly.
- Successful 21:45–23:05 request displays h23 in history. Persisted QA payload is independently checked: 14:45–16:05 UTC (Asia/Bangkok browser input), participant count 10, room quantity 1, recommended equipment quantity 2, custom quantity 2.
- Screenshots: user workspace `output/playwright/booking-preview-fix-1440.png`, `booking-preview-fix-390.png`, and `booking-preview-filled-1440.png`.

No booking backend action, policy, route, migration or dependency was changed. Browser checks use synthetic QA data; runtime database records were not changed. Test server, QA database, synthetic credentials and sessions are removed after checks. This follow-up is not a formal accessibility or security certification.
