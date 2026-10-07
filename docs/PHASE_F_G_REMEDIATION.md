# Phase F/G review remediation — 2026-10-07

## Changes

- PHPUnit forces SQLite `:memory:`. `Tests\TestCase::createApplication` checks the effective database configuration before RefreshDatabase traits run. MySQL, file SQLite, missing configuration, and URL overrides are rejected. Cached unsafe configuration fails closed. The separate disposable-schema MySQL concurrency harness remains independent.
- Missing legacy participant counts can be filled once by own-college Staff/Admin with booking approval permission, for Pending/Approved/Rejected bookings. Cancelled records and non-null counts cannot be changed. The detail page exposes the form using a server-side policy flag. No guessed values or automatic backfill.
- The action locks the booking, reauthorizes its current state, locks resources by ID, and checks the current room readiness, college, exclusivity and capacity. Count and an append-only audit entry commit together. Audit records actor, previous null count, confirmed count, reason and timestamp. Status, reviewer, time interval, equipment and simulator selection stay unchanged; approval and recall still perform their existing checks.
- Equipment approval capacity regression now uses distinct eligible rooms, asserts the equipment-specific error, and includes a sufficient-capacity positive control.
- Resource index displays room capacity in people and equipment total quantity in items.

## Deployment and rollback

New additive migration: `2026_10_07_000013_create_booking_participant_amendments`. Apply it before serving the revised booking detail page. It has not been applied to runtime in this remediation because of the incident below. No runtime reset, seed or automatic restore is part of this patch. Roll back application code before any schema rollback; preserve audit records before dropping the table.

## Verification and limitations

Regression coverage includes permission/college boundaries, invalid inputs, room readiness/capacity, legacy readability, once-only writes, stale caller reauthorization, atomic audit persistence, approval/recall after correction, and preservation of approved status. Fresh integrated verification: `php artisan test --compact`: 112 passed / 791 assertions; `npm run build`: passed (659 modules); Composer audit: no advisories; npm production dependency audit: 0 vulnerabilities; Pint and git diff whitespace checks passed. A deliberate parent-environment MySQL override caused all 8 targeted feature tests to stop at DatabaseSafety before RefreshDatabase (expected exit 1, zero assertions). PHPUnit XML defaults alone do not reliably override all inherited process environment values; the effective-configuration guard is the authoritative fail-closed layer. Browser checks with real role accounts and fresh MySQL concurrency checks were not repeated in this remediation.

## Execution incident

During remediation, the file-edit script failed at Python parsing and a subsequent test command still ran before database isolation was installed. The old RefreshDatabase configuration used runtime MySQL and ran migrate:fresh. Afterwards, users, colleges, bookings, resources and simulator assets each had zero rows. No pre-command counts were collected, so the original records and extent of loss cannot be enumerated. The user confirmed no backup is available. MySQL log_bin is OFF. No restore path has been verified; no replacement records have been created. Further runtime writes were stopped. This patch prevents the same default-test mechanism but does not recover prior data.
