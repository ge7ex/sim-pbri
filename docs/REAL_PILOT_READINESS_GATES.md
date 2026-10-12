# Real Pilot Readiness Gates

Software completion and real operational readiness are separate decisions. Current verdict: **REAL PILOT READY — NOT YET READY**.

| Gate | Required evidence | Current state |
|---|---|---|
| Real master data | Approved College/room capacities, responsible Staff, equipment quantities, simulator assets, courses and scenarios | Not available; no real institutional data was invented or seeded |
| Authorized pilot users | Named Lecturer/Staff/Admin accounts confirmed by the College owner | Not verified; account-management feature exists; private credential delivery channel must be approved |
| Identity and recovery | Approved email/identity provider or documented private out-of-band password operations | No mail/identity integration configured; local Admin/console process documented |
| Timezone and operating hours | Owner-confirmed timezone, business hours, holidays and exception handling | Not verified with owner |
| Database deployment | Approved change window, database backup, migration runbook, rollback/forward-fix decision and monitoring | Local MySQL additive migrations 000015/000016 applied; production deployment not performed |
| Backup and restore | Isolated backup/restore drill with integrity and access verification | Not performed |
| Storage and images | Writable private storage, backup/retention, image size/type validation and authorized access | Existing protected image behavior has tests; real deployment storage/backup not verified |
| Operations | Error monitoring, log retention/redaction, incident owner, least-privilege server/database accounts | Not verified for production environment |
| Security and privacy | Approved user list, College data isolation, staff finance privacy and no Export controls | Automated checks pass; full browser matrix and Senior review pending |
| Owner acceptance | College owner sign-off on requirements, migration, workflow, data retention and training | Not obtained |

Do not present synthetic demo credentials/data as pilot users or master data. Do not run the isolated demo seeder against MySQL.
