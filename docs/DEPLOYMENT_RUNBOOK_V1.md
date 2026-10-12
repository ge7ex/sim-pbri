# SIM PBRI V1 — Deployment and Operations Runbook

Status: **Preparation only — not an authorization to deploy.** No production host, DNS, identity provider, migration window, or target backup procedure has been verified.

## 1. Gates before deployment

Do not deploy to a real pilot until the College owner has approved:

- the exact V1 requirements and remaining operating-hours / manual special-case review policy;
- the institution's operating hours and any approved local-time exceptions;
- authorized pilot users, first-Admin bootstrap, and private credential delivery;
- the target hostname, HTTPS termination, reverse-proxy behavior, and cookie policy;
- database backup, restore, migration window, monitoring, retention, and rollback/forward-fix owner.

The application keeps persisted timestamps and the global `config('app.timezone')` in UTC. Booking input/display, calendar day grouping, booking filters, and report date boundaries use the owner-confirmed `Asia/Bangkok` timezone. Existing timestamp values were not converted. This behavior has focused automated and isolated QA evidence; other timestamp surfaces, institution operating hours, and manual in-class/out-of-class/special-case review policy still need owner/pilot validation before real data is entered.

## 2. Build and release inputs

Use the committed lockfiles and a reviewed commit on the approved branch. From a clean release checkout:

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
```

Do not include `.env`, generated credentials, local databases, temporary QA SQLite files, or `node_modules` in a release artifact. Preserve the existing application data directory only through the approved backup and deployment procedure.

## 3. Environment configuration

Set secrets in the deployment secret manager or protected server environment; do not put secret values in Git, command-line arguments, tickets, screenshots, or this runbook.

Required production settings include:

| Setting | Requirement |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Approved HTTPS origin |
| `APP_KEY` | Generate once through a protected console/secret workflow; preserve it across releases |
| `DB_CONNECTION` | `mysql`; set host, port, database, username, and password from the secret manager |
| `SESSION_ENCRYPT` | `true` for production session payloads |
| `SESSION_SECURE_COOKIE` | `true` on HTTPS |
| `SESSION_HTTP_ONLY` | Keep enabled |
| `SESSION_SAME_SITE` | Keep `lax` unless the approved sign-in architecture requires another tested value |
| `SESSION_DOMAIN` | Leave host-only (`null`) unless an approved domain design requires a broader scope |
| `LOG_CHANNEL` / `LOG_LEVEL` | Use the approved protected log sink, retention, alerting, and redaction policy |
| `FILESYSTEM_DISK` | Configure writable private storage for protected room images and include it in backup/retention planning |

`.env.example` is a local-development template: it defaults to debug mode, SQLite, and database-backed session/cache/queue. It now enables session payload encryption by default, but this does not replace the production HTTPS/cookie settings above. Do not copy the template unchanged into production. No outbound mail or identity provider is configured; email-based invitations and password recovery are unavailable.

The current bootstrap has no explicit trusted-proxy configuration. Before deployment behind a proxy/load balancer, verify the actual topology and configure the trusted proxy addresses/headers narrowly; do not trust arbitrary forwarded headers. Confirm the app recognizes HTTPS before enabling secure-cookie checks.

## 4. Database and migration procedure

### Accounts and privileges

- Use a dedicated MySQL database and a runtime account scoped to that database. Do not run the application as MySQL `root`.
- Runtime access should be limited to the table operations required by the application. Schema-changing privileges should be granted only to the migration operator for the approved change window.
- This codebase currently uses one default database connection for both runtime and Artisan migrations; separate runtime/migration credentials are an operational process requirement, not a feature implemented in the application.
- The database connection uses `utf8mb4`, strict mode, and no table prefix by default. Keep these compatible with the deployed schema.

### Every schema release

1. Confirm the target host, database name, branch, and commit without printing credentials.
2. Take a backup using the institution-approved MySQL backup mechanism and verify its integrity and restore access in an isolated environment. The SQLite demo restore check is not evidence for MySQL backup readiness.
3. Review pending migration SQL and the migration diff. `--pretend` is an inspection aid; it is not a backup or rollback plan.
4. With explicit change-window approval, run:

   ```powershell
   php artisan migrate --force --no-interaction
   php artisan migrate:status
   ```

5. Verify the application health endpoint, login, role/College scoping, booking, review, and protected image paths using approved synthetic or authorized data.
6. Record the commit, migration batch/status, backup identifier, operator, time, and outcome in the institution's change record without including secrets or personal data.

Never use `migrate:fresh`, `db:wipe`, `DROP`, `TRUNCATE`, or an automatic restore against the pilot database. Do not run `migrate:rollback` as an automatic recovery: migrations `000015` and `000016` have destructive `down()` paths that remove account fields/access-event data and booking snapshots. Prefer a reviewed forward-fix. Any database restore requires explicit incident/change approval and a verified target backup.

## 5. Runtime storage, session, cache, queue, and logs

- The database migrations create `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, and `failed_jobs`; the example environment uses database-backed session/cache/queue drivers.
- If `QUEUE_CONNECTION=database` is retained, run and monitor a queue worker and review failed jobs. No application-specific queued job dispatch was found in the current code review. If the deployment chooses `sync`, document that choice and recheck before introducing queued work.
- The `room-images` disk points to `storage/app/private/room-images` with direct serving disabled. Keep this storage non-public, restrict filesystem permissions to the application service account, and include it in approved backup/retention. Do not expose it with a public symlink.
- The application needs write access only to required Laravel runtime directories such as `storage` and `bootstrap/cache`; source, `.env`, and release artifacts should not be writable by the web process unless the deployment model requires it.
- Protect application/server logs, set a defined retention period, alert on service/database failures, and restrict log access. Existing room-image failure logs record the College ID and exception class, not file contents. Verify the production logging sink and redaction rules before pilot.
- No scheduled application jobs were identified in the current route/console review. Recheck this if scheduled work is added.

## 6. First administrator and service checks

After the approved schema migration, a trusted operator can create the first Admin through:

```powershell
php artisan sim:bootstrap-admin
```

Use an interactive protected console. The command prompts for user data and a hidden password; do not pass passwords as shell arguments. Deliver credentials only through an institution-approved private channel. For recovery, use `php artisan sim:recover-admin-password {admin-email}` only from a trusted console and follow the same handling rules. Account procedures are detailed in `ACCESS_MANAGEMENT_OPERATIONS.md`.

Laravel exposes `/up` as a liveness endpoint. It does not prove that MySQL, storage, authentication, role isolation, or booking workflows are operational. Run the approved smoke checks separately and keep Export routes/features disabled.

## 7. Recovery and rollback

- Keep the previous release artifact available for application-code rollback, but do not assume this reverses schema changes safely.
- For a failed deployment, stop traffic or enter the approved maintenance procedure, capture logs and migration status, and contact the database/change owner.
- Prefer a code forward-fix when the schema is additive and compatible. Restore database data only when an approved incident decision identifies the exact backup and target; first restore into an isolated database and verify it.
- Do not restore the synthetic SQLite demo into MySQL. Do not restore over the user's current database as a routine deployment rollback.
- The isolated SQLite backup/restore script verifies demo-file integrity and row counts only. A production MySQL backup/restore drill is still **NOT VERIFIED**.

## 8. Verified versus open

Verified locally in the 12 October 2026 QA snapshot: the application builds; automated feature tests use guarded SQLite `:memory:`; read-only `migrate:status` reported 21 migrations as `Ran`; demo setup/cleanup and a synthetic SQLite restore check passed. The migration count is an environment snapshot, not a deployment target or instruction to migrate.

Not verified: target deployment, proxy/TLS configuration, production account privileges, production session encryption/cookie behavior, timezone fit outside the tested booking/report paths, operating-hours/manual review policy, real MySQL backup/restore, log monitoring/retention, storage backup, owner acceptance, real master data, pilot accounts, full browser matrix, and Senior sign-off.
