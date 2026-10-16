# Senior Review Walkthrough — SIM PBRI

## Readiness

The isolated demo setup is repeatable and uses the real Laravel + Vue + Inertia application with fictional data. The current 15 October2026 candidate has passed I-4 and I-5 technical checks; see `SENIOR_REVIEW_CANDIDATE_2026-10-15.md`. Senior approval and Real Pilot readiness are separate, not yet obtained.

## Start and clean up

From the repository root in PowerShell:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Setup
```

The script creates a dedicated SQLite database under `%TEMP%\sim-pbri-phase-i-demo`, generates a random APP_KEY and three random sign-in credentials, migrates the isolated database, and starts Laravel on `http://127.0.0.1:8766`. The credential file is protected by a Windows ACL for the current user. Keep credentials out of reports, tickets and Git. Deliver these synthetic local credentials privately to the owner when explicitly requested; they are not production accounts.

Setup restores the caller's prior APP/DB environment variables after launching the isolated server. Cleanup verifies the recorded process name, command line, and creation ticks and refuses to delete TEMP data while port 8766 is still listening.

To reopen a stopped existing demo without deleting or reseeding its data:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Start
```

Start preserves the database and credentials. Browser sessions may require a fresh login after restart. Do not use Cleanup to restart a demo.

Only when the owner is finished and wants to delete the isolated demo:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Cleanup
```

## Isolated SQLite restore check

With the demo environment set up, run:

```powershell
php scripts/qa/verify-phase-i-demo-restore.php
```

The check creates a SQLite backup with `VACUUM INTO`, restores it to a second file under the same TEMP directory, verifies integrity and row counts for every table, then removes its temporary backup files. It does not verify a production MySQL backup or restore process.

Cleanup verifies the recorded PHP server command lines, stops only the demo server, and removes only its fixed TEMP directory. If another process occupies port 8766, setup stops before creating the demo. The seeder itself refuses to run unless the selected connection is SQLite and the database path is the dedicated demo file.

## 17-minute walkthrough

1. **Lecturer — 5 minutes:** Sign in, open the booking form and save an incomplete Draft. Select its resume link, complete the fictional room/time/course/scenario/equipment/contact, confirm availability, submit and open the Pending detail.
2. **Staff — 4 minutes:** Sign out and sign in as Staff. Confirm the unsubmitted Draft was never in the review queue. Verify the submitted request's contact, requester actor, room snapshot, simulator, scenario and equipment; approve it or reject with a reason. The database begins with one synthetic Approved record and a separate Pending record.
3. **Lecturer — 2 minutes:** Reopen history and verify status/detail. Compare the booking user/coordinator label with the immutable submitted-by actor.
4. **Admin — 4 minutes:** Review fictional master data, account management, dashboard and statistics. Room dimensions are intentionally hidden from all pages, including Admin; do not expect dimension fields or calculated area in the UI. If creating a user, the random temporary credential appears once; do not retain it after the demo.
5. **Cross-role — 2 minutes:** Verify Staff has no Admin-only financial data or account-management link; verify a Lecturer cannot access Staff review or Admin account routes. No Export control or route is available.

All phone numbers and names in this environment are fictional. No image upload, live email, external integration, real MySQL data, or production account is used. Synthetic browser QA completed Staff approval and rejection plus Lecturer cancellation, and verified status/history updates. Recent checks also confirmed the Staff rejected-history zero state, keyboard focus through skip link and grouped navigation, labels on status/date filters, and no console errors on the Staff review page; an Admin server validation error is associated with an alert. Current role/responsive/keyboard/error/loading results are recorded in the 15OctoberQA evidence. Actual native screen-reader speech remains owner-skipped optional assurance, NOT VERIFIED.

## Executed15October2026

I-5 real-app walkthrough13checks and crossCollegebooking/image403sixcases pass. Adminroom/equipment/course/scenario/simulatorcreation, disabled/nonselectable roomandreenable, LecturerDraftresumesubmit, Staffapprove/rejectreason, historycancel, populated/zero reports, financeroleprivacy andmobilekeyboard verified. Initial harness assumptions about hiding disabledrooms/carousel/select element were corrected against actualUI; no applicationpatch made for those harnessfailures. Account15checks and Reviewnetwork/loading cases are separatefreshI4evidence. Currentdemo intentionallyretained forownerpresentation; readprivatecredentials locally; cleanupafterpresentation.
