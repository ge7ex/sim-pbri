# UI Alignment Checkpoint — 2026-10-07

## Reference and scope

Product baseline: `d02a4c8`, `backend/inertia-foundation`.
Visual source: `ge7ex/sim-pbri-preview`, main `2314dd2f711bdccb427a6afcc65764703edd51f3`, inspected both in source and in a local browser.

The navy 280px sidebar, palette, content hierarchy, summary metrics and recent-booking table follow the reference. Preview repositories, mock stores, fake users, booking records and workflow logic were not merged into product. Preview dependency installation reported five high advisories; no preview dependency or lockfile was adopted. Product dependency audits below are independently clean.

## Implementation

- UI.1–2: tokens.css, app-shell.css and components.css establish a shared authenticated layer. AppLayout uses navy desktop navigation, role-filtered links, identity/college context, logout, and a compact mobile disclosure. Exact route matching prevents Booking Create and Booking History from both appearing active. Keyboard focus, aria-current, aria-expanded, Escape focus restoration and a skip link are provided.
- UI.3: thin DashboardController delegates to DashboardQuery. Lecturer aggregates and recent rows are own-user plus own-college; Staff/Admin aggregates are own-college. Booking-view permission is required. Recent rows are limited to eight and explicitly projected; notes, phone, equipment details and financial fields are not sent. Associated room and scenario names are independently college-scoped. Totals include cancelled records.
- UI.4: Review uses server-provided paginated Pending records, a request selector, selected details, visible rejection reason label, unchanged approve/reject endpoints, and links to filtered history. Audit/recall/participant amendment remain in the real booking detail page. Only selection ID and existing form input state are held locally.
- UI.5: resource creation is a native details disclosure; the table shows own-college room/equipment context, capacity/quantity, responsibility, status and exclusivity. All existing creation and room editing controls remain.
- UI.6: Booking Create gains section links and shared controls while retaining its full existing submission/availability code. Calendar groups the actual server events by displayed day; cross-college redaction stays server-driven.
- UI.7: Scenario, Simulator, booking history and details share tokens/primitives. Admin forms, templates, operational records, depreciation and maintenance retain their original data/permission conditions.
- UI.8–9: keyboard, mobile disclosure, focus rings, native table scroll regions, column semantics, form labels, text contrast and restrained spacing reviewed. Status colors retain text labels. Existing Thai font fallback is retained through the authenticated font stack. No new gradient or ornamental hero was introduced.

`Workspace.vue` and WorkspaceController have no registered route references; they remain untouched per the handoff boundary. No authenticated routed page contains obsolete prototype language. No Phase H feature, role, permission, migration, booking action or authorization policy was added/changed.

## Fresh verification

| Check | Result |
|---|---|
| php artisan test --compact | 117 passed, 874 assertions; isolated SQLite memory guarded before database refresh |
| npm run build | Passed, 661 modules |
| composer audit --no-interaction | No vulnerability advisories |
| npm audit (including dev dependencies) | 0 vulnerabilities |
| php vendor/bin/pint --test --dirty | Passed |
| php artisan migrate:status | All existing migrations Ran; no new migration |
| Responsive browser matrix | 8 pages × 1440/1024/768/390px = 32/32 HTTP 200; zero page errors; no document horizontal overflow; one active sidebar item |
| Expanded resource forms | Add and room edit at 390px: no document overflow; table scrolls within its region |
| Keyboard menu | Tab/Escape closes disclosure, aria-expanded=false, focus returns to toggle with visible solid outline |
| Browser booking flow | Create with real room availability, phone/count and scenario equipment recommendation → approve → recall; status history preserved |
| Browser role checks | Lecturer: four permitted links, own-only dashboard, direct Review/Resources 403. Staff: resources readonly, quantity visible, simulator financial fields absent. Admin financial detail remains visible |
| Browser calendar privacy | Foreign booking shows redacted entry; no foreign identity/note shown |

Dashboard regression tests additionally cover empty results, unauthenticated/unassigned users, counts for all roles, cross-college exclusion, recent-row limit, and malformed foreign room/scenario associations. Existing booking, simulator, capacity, permission and privacy tests remain green.

### Visual quality review

Compared desktop shell and dashboard screenshots directly against the preview. Sidebar width and navy match the reference; content padding, heading hierarchy, summary cards, white panels, table borders and status labels are consistent. Module controls use shared CSS with scoped rules retained for actual layout. Mobile forms stack, the sidebar becomes a compact disclosure, and wide data remains available through horizontal table regions. Dark sidebar has no prototype notice or inaccessible module links. Long financial/scenario pages preserve existing detail controls.

Measured token contrast ratios: navy_on_white=15.12:1, muted_on_white=4.76:1, muted_on_soft=4.55:1, pending=5.99:1, approved=6.51:1, rejected=6.61:1. This is scoped UI QA, not an exhaustive accessibility certification.

## QA isolation and limits

Browser tests used the real current Laravel/Inertia code and built assets on loopback, with a disposable SQLite file and three synthetic role accounts. QA database, session storage, application key and login credentials were separate from runtime. No runtime records were seeded, edited or reset in this checkpoint. Test servers and synthetic credential/database files are removed after QA; screenshots and sanitized results remain in the user's workspace under `output/playwright`.

Screenshots: `ui-dashboard-1440.png`, `ui-dashboard-390.png`, `ui-review-1440.png`, `ui-resources-1440.png`, and one screenshot per page/viewport plus expanded controls. Evidence is local, outside the product Git repository.

Real user accounts/data were not used for browser checks. MySQL concurrency testing and a new Codex Security scan were not repeated because the changes preserve existing booking actions/locking and add only scoped dashboard reads. The previous database incident is not a recovery claim; this checkpoint does not reconstruct prior lost records.
