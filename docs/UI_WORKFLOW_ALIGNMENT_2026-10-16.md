# UI workflow alignment — 16 October 2026

## Delivered

1. Shared in-app confirmation dialog replaces browser confirm for booking approval, rejection, cancellation and recall. Recall explains the change to Pending and shows the reason; the existing server-side confirmed/reason/permission checks remain authoritative. Safe initial focus, Tab/Shift+Tab containment, Escape to return, focus restoration, busy and repeated-submit guards are included.
2. Calendar opens in calendar view. With no date bounds (including cleared fields), the server supplies the current month in Asia/Bangkok. Toggle calendar/list without losing filters; navigate months across years; select a day for the full agenda. Multi-day events appear on each occupied day; an exact midnight end does not occupy the next day. Dates/times remain Bangkok with 24-hour formatting. Cross-College events retain only the existing redacted fields. For a multi-month custom range, calendar shows its starting month and list shows the returned range; this is explained on screen. Existing 500-event query cap is unchanged.
3. Simulator registry starts with dropdown selectors and read-only details. Explicit Add/Edit opens one labelled form. Editing requires an in-app confirmation before PUT; returning from the dialog keeps entered fields. Cancel exits editing. Selection is locked during an open editor to avoid editing another selected record. Pagination (20 assets/page), maintenance history and permission-scoped financial fields are retained.

## Evidence

- Full PHPUnit suite on guarded SQLite :memory:: **192 passed / 1750 assertions**. Added current-Bangkok-month regression at the UTC month boundary, cleared bounds and explicit date range. Privacy fixtures now request their explicit dates so they do not depend on the actual month.
- Production Vite build: PASS, 689 modules. Pint and Git whitespace checks: PASS.
- Real Chrome, three separate role contexts: calendar default/toggle/range/year boundary, multi-day/midnight, private event and mobile checks; modal initial focus/Tab/Shift+Tab/Escape/return/confirm; registry selection/edit/create/validation and Staff financial/control privacy; no page errors. Registry viewports 390/768/1366; mobile calendar/cancellation 390. Results in docs/qa/UI_WORKFLOW_2026-10-16.json.
- Existing ReviewLoadingCheck updated to confirm the modal, then passed approval/rejection loading, duplicate-submit prevention, validation, network and HTTP 503 recovery and feedback focus.
- First browser run found Shift+Tab focus escape; corrected shared dialog focus containment and reran. Screenshot review also found the CSS reset removed native dialog centering; explicit auto margins restored it and mobile center-coordinate assertions passed. Other harness failures were missing approved fixture (now intercepted UI status) and overly broad/exact selectors; no business data changed to satisfy tests.
- Ponytail and Ponytail Review used for connected code review; Impeccable used with existing .impeccable.md institutional design context; UI/UX Pro Max targeted modal confirmation/focus guidance. No package added. Native dialog supplies modal isolation; explicit focus wrapping covers the observed Chrome behavior.

## Limits / safety

Browser mutation cases used intercepted responses, not real database writes. Existing backend tests verify actual isolated transitions/saves. Native screen-reader speech and owner acceptance have not been performed in this change. This is not a fresh repository-wide security scan or deployment approval. No runtime MySQL/schema change, data reset, Export, deployment or main merge. Demo remains available on port 8766 with unchanged credentials.

Continue with owner UI acceptance on the three updated pages and report actual failures. Previous real-Pilot operational gates remain open in REAL_PILOT_READINESS_GATES.md.
