# SIM PBRI

SIM PBRI is an internal Simulation Center booking and resource-management application for authorized Lecturer, Staff, and College Admin accounts. It uses Laravel 12, PHP, Vue 3, Inertia, TypeScript, and MySQL in its intended runtime configuration. College scope and permissions are enforced by the server.

## Product workflows

- Lecturers create, save, resume, submit, and track room/resource booking requests.
- Staff review, approve, or reject requests and view the operational details needed to coordinate them.
- College Admins manage rooms, equipment, simulator assets, courses, scenarios, and personnel access.
- Availability, history, room snapshots, and usage statistics support the request and review workflows.
- User-facing Export and report-download features are intentionally not provided.

## Run an isolated Senior demo

The recommended local walkthrough uses fictional accounts and a dedicated SQLite database under the current user's temporary directory. It does not connect to the configured MySQL database or add demo records to an existing database.

From PowerShell at the repository root:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Setup
```

Open `http://127.0.0.1:8766` and read the random credentials from the local path printed by the setup script. Do not copy those credentials into chat, reports, tickets, or Git. After the walkthrough, clean up the server, database, and credentials:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Cleanup
```

The cleanup script stops only the recorded demo server and refuses to remove the temporary data while port 8766 remains in use. The synthetic SQLite backup check is:

```powershell
php scripts/qa/verify-phase-i-demo-restore.php
```

This verifies the demo SQLite file only. It is not a production MySQL backup or disaster-recovery procedure.

## Local development

Prerequisites: PHP 8.2+, Composer, Node.js/npm, and MySQL for a normal shared runtime. PHP GD is required for room image uploads. The isolated Senior demo uses SQLite and does not need real application data.

Install dependencies and prepare a new local-only SQLite environment from PowerShell. These commands create `.env` only when absent, keep the database file local, and generate a local app key:

```powershell
composer install
npm ci
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
php artisan key:generate
php artisan migrate --no-interaction
npm run dev
```

Run `php artisan serve` in a second terminal. Apply migrations only after confirming `.env` targets a disposable local development database. Never point the demo seeder or automated tests at the existing `sim_pbri` database.

Run the verification checks separately:

```powershell
php artisan test --no-ansi
npm run build
php vendor/bin/pint --test --dirty
```

The PHPUnit configuration forces SQLite `:memory:` and `DatabaseSafety` rejects non-isolated connections. Keep that guard in place.

## Operations and account setup

- Read [`docs/DEPLOYMENT_RUNBOOK_V1.md`](docs/DEPLOYMENT_RUNBOOK_V1.md) before preparing a deployment. It is preparation guidance, not approval to deploy.
- Read [`docs/ACCESS_MANAGEMENT_OPERATIONS.md`](docs/ACCESS_MANAGEMENT_OPERATIONS.md) for first-Admin bootstrap, College-scoped account management, and recovery. There is no public sign-up or configured email-recovery service.
- Read [`docs/SENIOR_DEMO_SCRIPT.md`](docs/SENIOR_DEMO_SCRIPT.md) for the repeatable role walkthrough and cleanup process.
- Current verification and open gates are recorded in [`docs/V1_COMPLETE_SYSTEM_QA.md`](docs/V1_COMPLETE_SYSTEM_QA.md) and [`docs/REAL_PILOT_READINESS_GATES.md`](docs/REAL_PILOT_READINESS_GATES.md).

The Senior Review and Real Pilot readiness decisions are separate. Check the current QA report for their status; do not treat synthetic demo data, local restore checks, or passing automated tests as real-user acceptance or production readiness.
