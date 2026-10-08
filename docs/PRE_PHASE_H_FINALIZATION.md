# Pre-Phase H Finalization — 2026-10-08

## Scope and immutable baseline

Repository: `C:\xampp\htdocs\sim-pbri`. Branch: `backend/inertia-foundation`.
Starting HEAD after fetch and fast-forward-only pull: `b8618e8efd22cae5c57f996495c2f60acf6ef91e`.
The existing UI Structure checkpoint was preserved. Phase H was not started; main was not merged.

Checked source HEAD: `1a19e7373854cd311a31444febe96f011767e4a5`.
The documentation commit follows this source HEAD. Its final SHA, clean status and remote equality are reported after commit/push, rather than embedding a self-referential commit hash here.

## Skills and code-quality review

**Ponytail skill was not available and was not silently substituted.**

Installed skill locations were searched for Ponytail, Taste and UI/UX Promax; none was available. No Ponytail invocation or findings are claimed. The code-work skill supported the implementation workflow. A manual code-quality pass checked the requested items: existing architecture, thin controller, policy ownership, transaction boundaries, naming, duplication, scope and error safety.

Material review outcomes:

- The controller delegates deletion; permission middleware and policy remain authoritative. The action reauthorizes the locked row before dependency checks.
- One small action uses existing relationships, restrictive FKs and `RoomImageStorage`; no schema change, generic framework, duplicated role model, or new cleanup abstraction.
- Private image cleanup is registered inside the transaction for execution after commit. A rollback retains the resource and file. Invalid or foreign-college paths block deletion.
- A late-dependency test verifies restrictive-FK failure becomes a safe validation error and the transaction rolls back.
- Seeder environment, password and College guards run before user writes. Tests cover missing/ambiguous/nonexistent College selection, hashing and idempotence.
- Initial new test failures were fixture errors (enum instead of the model's string role, and framework production console confirmation). The fixtures were corrected; the final complete suite passes.

Impeccable was used for the final visual review against the existing `.impeccable.md`: navy/slate institutional direction, restrained secondary red Delete, explicit confirmation, visible error/success, pending controls, keyboard focus return and contained table scrolling at 390px. No broader redesign was introduced. Taste and UI/UX Promax were not invoked.

## Resource deletion

`DELETE /app/resources/{simResource}` requires authenticated access profile, `sim-resource.delete`, and own-college policy authorization. Admin permission definitions were already present; `UserRole.php` is unchanged.

Within a transaction the action locks and reauthorizes the resource, then blocks:

- Any booking reference, including pending, approved, rejected and cancelled history.
- Any room image audit entry, including an entry recording a removed photo.
- Any scenario resource recommendation/template reference.

Restrictive FKs remain unchanged as the final concurrent-write guard. No booking, pivot, image audit or scenario history is deleted. No cascade, SoftDeletes or migration was added.

An eligible resource is hard-deleted. An eligible valid private image is cleaned through existing storage only after commit. Historical imaged rooms remain protected even after removal; normal uploaded rooms therefore cannot be hard-deleted. Storage cleanup failure follows the existing private-file warning behavior and can leave a private orphan; it does not falsely report a committed database operation as failed.

The UI renders Delete only with the existing delete permission, requests confirmation naming the resource, disables repeated deletion while pending, surfaces safe server errors and returns focus to the resource list after success. Eligibility rules are exclusively server-side.

## Actual local database and dev users

Effective environment: `local`; driver: `mysql`; database: `sim_pbri`.
Read-only environment inspection, `migrate:status` and Eloquent counts succeeded.
`php artisan db:show --counts` failed because local XAMPP lacks `performance_schema.session_status`. This diagnostic compatibility limitation was not treated as a connection failure; read-only Eloquent inspection confirmed the actual database. No vendor, performance schema or application schema changes were made to bypass it.

Only one College existed: ID **9**, วิทยาลัยพยาบาลบรมราชชนนี. It was explicitly selected for the dev accounts.

Before seeding: 1 user, 1 resource, 0 bookings. After two successful seeder runs: 3 users, 1 resource, 0 bookings; the account IDs were identical on both runs.

| Email | Name | Role | ID | Result |
| --- | --- | --- | --- | --- |
| admin@sim-pbri.local | SIM PBRI Admin | admin | 18 | Updated existing dev account |
| staff@sim-pbri.local | SIM PBRI Staff | staff | 20 | Created |
| lecturer@sim-pbri.local | SIM PBRI Lecturer | lecturer | 19 | Created |

`LocalDevUserSeeder` refuses outside local/testing. It uses existing Colleges, explicit selection when ambiguous, `updateOrCreate`, and the User model's hashed password cast. It is **not** invoked by `DatabaseSeeder`. The supplied dev password was consumed only at runtime through a process environment variable; no value or hash is included in repository files or this report. Production must never use these shared development accounts.

For later local use: set `SIM_PBRI_DEV_PASSWORD` securely in the process environment, optionally set an explicitly chosen existing `SIM_PBRI_DEV_COLLEGE_ID`, and run `php artisan db:seed --class=LocalDevUserSeeder`. Clear the process variables afterwards. Do not save the password in source, committed scripts or reports.

No destructive runtime migration/reset, backup restoration or fabricated booking history was performed. All automated database tests use the repository's guarded SQLite `:memory:` configuration. Browser QA created clearly named temporary rooms through Admin UI and deleted them through the same UI; none remains and no booking was submitted.

## Role checks and browser QA

Normal login forms on the actual local server were used for all three supplied dev accounts. Auth, CSRF, policies and college constraints were not bypassed.

| Role | Actual runtime evidence at 1440px and 390px |
| --- | --- |
| Admin | Resource page; temporary room edit/save; explicit confirmation and cancel; original image-audited room deletion blocked; unused temporary room deletion succeeds; repeated Delete disabled; success status; focus returns to list; no document overflow |
| Staff | Resources read-only; no create/edit/delete; direct CSRF-authenticated DELETE returns 403; review accessible; operational Simulator page accessible |
| Lecturer | Exact booking-only permissions; Resource/Review/Simulator/Scenario GET returns 403; booking room selection and change-room control work; no booking submitted; no document overflow |

Staff's actual Simulator dataset is empty. The runtime check confirmed no financial fields in that payload; nonempty financial redaction and maintenance mutation guards are covered by the existing automated tests, not claimed as populated-runtime proof.

Machine-readable evidence: [PRE_PHASE_H_FINALIZATION.json](qa/PRE_PHASE_H_FINALIZATION.json).
Screenshots for the three roles at 1440px and 390px were inspected locally under the task workspace's `output/playwright/`; they are not uploaded to GitHub.

### Separate runtime state change during QA

Original resource ID 10 initially had an image and ready status. An image audit records `removed` by Admin ID 18 at **2026-10-08 08:55:32 UTC**; the row's status became maintenance with `updated_at` **08:55:40 UTC**. The explicit QA scripts update only named temporary rooms; their operations on resource 10 are blocked DELETE checks. Provenance of the separate edit has not been established. The user was asked whether they were editing the room. No restoration or status reversal was attempted.

The original resource and both image-audit records remain. The protected-delete check passed after image removal as well. Lecturer selection was verified using a temporary ready room because the original room is now maintenance. This is an operational observation, not evidence of an authorization failure or a claim of restored historical data.

### Verification limits

Booking history cases, cross-college deletion, FK race handling and eligible image cleanup/rollback were verified in isolated tests. Runtime had no bookings and no simulator assets; no synthetic historical bookings or financial assets were inserted into MySQL. The FK race test injects a dependency before deletion on SQLite; it is not a parallel-process MySQL load test. This checkpoint is not an ISO certification or a new exhaustive Codex Security scan.

## Final checks

| Check | Result |
| --- | --- |
| New focused tests | 17 passed / 108 assertions: 10 Resource deletion tests + 7 Seeder tests |
| Complete suite | **142 passed / 1,061 assertions** |
| Production build | Passed / **671 modules** |
| Pint | Passed |
| git diff --check | Passed |
| composer audit | No security vulnerability advisories found |
| npm audit, all dependencies | 0 vulnerabilities |
| migrate:status | All existing migrations through `2026_10_07_000014_add_room_images` Ran; no new migration |
| Runtime final counts | 3 users / 1 resource / 0 bookings; 0 QA resources remain |
| Roles | Existing enum/permissions unchanged; Staff and Lecturer master mutations remain denied |
| Scope | Phase H untouched; no merge into main |

## Changed files and commit discipline

1. `app/Modules/SimResource/Actions/DeleteSimResourceAction.php`
2. `app/Modules/SimResource/Http/Controllers/SimResourceController.php`
3. `app/Modules/SimResource/Policies/SimResourcePolicy.php`
4. `app/Modules/SimResource/Routes/web.php`
5. `resources/js/Modules/SimResource/Pages/Index.vue`
6. `tests/Feature/Booking/ResourceDeletionTest.php`
7. `database/seeders/LocalDevUserSeeder.php`
8. `tests/Feature/LocalDevUserSeederTest.php`
9. This report and `docs/qa/PRE_PHASE_H_FINALIZATION.json`.

Coherent source commits:

- `dfe63084479db983c73d4862b5c32d8ecc79a844` — `feat(resources): add safe resource deletion`
- `1a19e7373854cd311a31444febe96f011767e4a5` — `chore(dev): add guarded local development users`

The report is committed separately as `docs: record pre-phase H final verification`. Push is limited to `origin backend/inertia-foundation`; final working-tree cleanliness and remote SHA equality are checked after that push and included in the final response. Stop at this checkpoint before Phase H.
