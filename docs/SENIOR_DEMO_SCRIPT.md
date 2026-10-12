# Senior Review Walkthrough — SIM PBRI

## Readiness

The isolated demo setup is repeatable and uses the real Laravel + Vue + Inertia application with fictional data. It does **not** make this build Senior-review-ready: see `V1_COMPLETE_SYSTEM_QA.md` for the remaining QA, source, and business-decision gates.

## Start and clean up

From the repository root in PowerShell:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Setup
```

The script creates a dedicated SQLite database under `%TEMP%\sim-pbri-phase-i-demo`, generates a random APP_KEY and three random sign-in credentials, migrates the isolated database, and starts Laravel on `http://127.0.0.1:8766`. The credential file is protected by a Windows ACL for the current user. Read the credentials only on the local machine; do not paste them into reports, chat, tickets, or Git.

After the walkthrough:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Cleanup
```

Cleanup verifies the recorded PHP server command lines, stops only the demo server, and removes only its fixed TEMP directory. If another process occupies port 8766, setup stops before creating the demo. The seeder itself refuses to run unless the selected connection is SQLite and the database path is the dedicated demo file.

## 15-minute walkthrough

1. **Lecturer — 4 minutes:** Sign in, open the booking form, select the fictional room and a 24-hour period, review capacity, enter the coordinator/contact, select `DEMO-101` and its scenario, confirm the recommended equipment, submit, and open the Pending booking detail.
2. **Staff — 4 minutes:** Sign out and sign in as Staff. Open the review queue; verify contact, requester actor, room snapshot, simulator, scenario and equipment. Approve a new Pending booking or reject it with a reason. The database begins with one synthetic Approved record and one Pending record on separate future dates.
3. **Lecturer — 2 minutes:** Reopen history and verify status and detail. Compare the booking user/coordinator label with the immutable submitted-by actor.
4. **Admin — 3 minutes:** Sign in as Admin. Review fictional room/equipment/course/scenario/simulator master data, open the account-management list, and inspect dashboard/statistics. If creating a user, the random temporary credential appears once; do not retain it after the demo.
5. **Cross-role — 2 minutes:** Verify Staff has no Admin-only financial data or account-management link; verify a Lecturer cannot access Staff review or Admin account routes. No Export control or route is available.

All phone numbers and names in this environment are fictional. No image upload, live email, external integration, real MySQL data, or production account is used. Browser approval still needs a successful manual/automated confirmation run before this walkthrough is considered full-system QA evidence.
