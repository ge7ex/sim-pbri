# Owner UAT checklist — 16 October 2026

Use http://127.0.0.1:8766 with the privately supplied synthetic accounts. This is the real application using an isolated SQLite demo, separate from MySQL and port 8000. Do not enter real personal or institutional data.

| Role | Action | Expected result |
|---|---|---|
| Lecturer | Sign in, inspect dashboard and navigation on desktop/mobile | Readable layout, role-appropriate links |
| Lecturer | Create incomplete Draft, leave and resume | Saved fields return; Draft is not a submitted request |
| Lecturer | Complete a fictional request with an available room/time and submit | Validation explains missing fields; valid request becomes Pending |
| Staff | Open review queue and submitted details | Pending request visible; unsubmitted Draft absent |
| Staff | Approve one request; reject another with a reason | Status and history reflect the action |
| Lecturer | Reopen own history/details | Current status and rejection reason visible where applicable |
| Admin | Inspect master data and user management | Admin controls available; room dimensions hidden |
| Admin | Disable a fictional room, inspect booking choices, then re-enable it | Disabled room cannot be selected for a new request; can be restored |
| All | Inspect reports and role navigation | Staff has no Admin financial/account controls; no Export |
| All | Navigate with Tab/Shift+Tab and inspect narrow viewport | Visible focus, accessible labels, usable navigation |

Record each result as PASS / FAIL / NOT CHECKED. For failures provide role, page, steps, expected result, actual result and a screenshot without passwords. Do not mark acceptance complete until the owner has performed and accepted these checks. Native screen-reader speech is optional and owner-skipped; do not report it as tested.

Actual institutional master data, named users, target server operations and owner/Senior acceptance remain gates in REAL_PILOT_READINESS_GATES.md.
