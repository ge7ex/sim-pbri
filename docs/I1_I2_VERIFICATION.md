# I-1 / I-2 verification — 14 October 2026

Source baseline: `e52282d6545f2ae813edafe2ad7dc53d5e006b1b` on `backend/inertia-foundation`; clean before verification. Owner-approved V1 requirements baseline is recorded in `REQUIREMENTS_TRACEABILITY_V1.md`.

**I-1: DONE for the accepted local account-provisioning scope.**
**I-2: DONE for the accepted booking/room/review gap scope.**
**Full V1 Senior Review and Real Pilot: NOT YET READY.** I-3, I-4 and I-5 retain their separate gates; these two package verdicts do not mean the full application or production operations are verified.

## Fresh automated evidence

PASS: **64 tests / 475 assertions**, 2.61 seconds, on forced SQLite `:memory:` with array cache/session, sync queue, a non-existent temporary config-cache path and separate temporary Laravel storage.

Executed files:

- `tests/Feature/Access/UserManagementTest.php` — account role/College scope, CSRF, validation, self/last-Admin controls, active-account/session behavior, temporary/password reset, interactive bootstrap and console recovery.
- `tests/Feature/Booking/BookingDraftTest.php` — incomplete/owned Drafts, foreign IDs, cap, non-reservation, atomic submit/delete and failed-submit retention.
- `tests/Feature/Booking/CreateBookingTest.php` — immutable actor/College, editable requester, room snapshots, capacity, availability/quantity and adjacency.
- `tests/Feature/Booking/SimResourceManagementTest.php` — College-safe room metadata, responsible Staff, dimension pair validation and omission from UI props.
- `tests/Feature/Booking/ScenarioBookingWorkflowTest.php` — recommendations, foreign/inactive IDs, historical relations and reviewer contact/privacy.
- `tests/Feature/Booking/BookingStateTransitionTest.php` — reviewed state changes, reasons/audit, readiness/capacity rechecks and ownership.

The first isolated runner omitted `framework/views` in temporary storage and reported 18 failures with “Please provide a valid cache path”. Creating the required temporary storage directories resolved the runner problem; the same source then passed all 64 tests. No application fix or cache-clear command was used. The temporary test storage was removed after the run.

## Fresh local Chrome evidence

Both browser scripts ran against `http://127.0.0.1:8766`, using only the guarded synthetic SQLite demo. Random synthetic credentials stayed inside the local process; results contain no passwords. Real MySQL accounts or application records were not used.

### I-1: 15 checks passed

1. Admin signs in and creates a synthetic Lecturer through the account page.
2. The one-time credential can be hidden and its input disappears.
3. Temporary-password login requires password change; direct navigation to booking still redirects to that gate.
4. Wrong current password displays validation and preserves the change requirement.
5. Successful password change opens the dashboard.
6. New password signs in through a second independent browser session.
7. Admin reauthenticates and issues a reset credential through the UI.
8. Reset expires both existing user sessions at the next request.
9. Reset credential requires password change again.
10. Admin deactivation expires the active user session.
11. Inactive account cannot sign in.
12. Admin reactivation restores successful sign-in.
13. Admin's own deactivate and role-change controls are disabled.
14. Staff direct navigation to account administration returns HTTP 403.
15. No browser page errors observed.

An initial browser harness used an overly exact wrapped-label selector. Login succeeded, but the role/create-form lookup timed out. Locators were scoped to the inspected create form; the same app source then passed. This was not treated as an application defect.

### I-2: 7 checks passed

1. Incomplete Draft survives reload; owner deletes it and the Draft list becomes empty.
2. Complete Draft resumes the saved Bangkok date, 09:00–11:00 times, editable requester and note; hour control has the blank option plus all 24 hour values.
3. Unsubmitted Draft content is absent from the Staff review page.
4. A second browser session successfully submits a booking in the saved Draft's slot, demonstrating that saving the Draft did not reserve the room.
5. Submitting the stale Draft receives the server conflict error; the Draft and its note remain available.
6. Changing the Draft to 13:00–14:00 and saving/submitting succeeds; the submitted Draft disappears from the owner's list.
7. No browser page errors observed.

The fresh automated tests above supply the additional actor/College, room-dimension/snapshot, capacity, reviewer-contact and transaction evidence. Prior Lecturer→Staff approval evidence is retained in `V1_COMPLETE_SYSTEM_QA.md`; it was not rerun here. This browser conflict exercises application revalidation with sequential competing requests, not a MySQL simultaneous-lock integration test.

## Data and scope boundary

- Demo Setup migrated and seeded only its dedicated temporary SQLite database. Array cache and isolated storage were selected by the guarded script; the project config cache was absent.
- Guarded Cleanup stopped the recorded demo processes, verified port 8766 closed and removed the temporary SQLite/credential directory.
- No MySQL access, runtime migration, real-data import, restore, database cache clearing, application-source edit, package installation, build, push or main merge occurred in this slice.
- Existing agreed variations remain: dimensions stored but hidden everywhere; Draft persists until owner submit/delete; actor College fixed; manual Staff contextual judgment; no automatic classifier; no Export.
- A dedicated contextual classification/checklist, email/identity-provider integration and institutional pilot operations have not been added. The accepted baseline does not require those integrations to close these local functional packages.
- Full screen-reader/responsive/error/loading coverage is I-3; full security scan and concurrency/deployment assurance are I-4; integrated Senior package is I-5.

Machine-readable evidence: `docs/qa/I1_I2_VERIFICATION.json`.
