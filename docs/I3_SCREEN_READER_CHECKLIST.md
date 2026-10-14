# I-3 — optional native assistive-technology check

Status: **NOT RUN; additional assurance, not a blocker for I-3**. The original handoff requires screen-reader labels. Chrome accessibility-tree checks passed 114 samples. On 14 October the owner instructed skipping the additional native speech check and continuing I-4. Native speech, announcement timing and native dialogs remain unverified. The earlier mandatory-gate statement was an interpretation error.

## Safe test environment

Use only the isolated synthetic demo. From PowerShell in `C:\xampp\htdocs\sim-pbri`:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Setup
```

Open `http://127.0.0.1:8766`. Read the random synthetic credentials privately from the file path printed by Setup; never paste passwords into chat or reports. If Setup refuses because config is cached or the port/directory is occupied, stop and report that condition; do not run `optimize:clear`, `cache:clear`, or a MySQL reset/restore to make it work.

## Five short journeys

| Journey | What to check | Expected result |
|---|---|---|
| Guest login | Read and Tab through email/password/submit; try an invalid synthetic login | Fields and validation are named and discoverable; no keyboard trap |
| Lecturer booking | Read room picker, date and 24-hour time groups, participant/requester/contact fields and Draft controls; try an over-capacity count | Controls have understandable names; availability/capacity feedback is discoverable; invalid submission stays blocked |
| Staff review | Read selected request/contact; activate Approve with keyboard, first cancel the native confirmation, then approve a synthetic request | Confirmation is understandable; cancel sends no action; loading/result is discoverable; success focus moves to the status message; queue empty state is readable |
| Admin account | Create only a synthetic account; read the temporary-credential notice privately; hide it; check a validation error | User understands one-time credential handling; fields/errors and action states are discoverable; no password copied into results |
| Error/zero navigation | As Staff visit `/app/users`; follow the return link; open reports with an empty future date range | Thai 403 explanation and safe return link are read; zero-results information and filters are discoverable |

Record only screen reader/version, browser/version, journey, PASS/FAIL, and a short description of any problem. Do not include passwords or personal data. A failed journey becomes a reproduced I-3 defect to fix; absence of a run remains NOT VERIFIED.

When done:

```powershell
.\scripts\qa\phase-i-demo.ps1 -Action Cleanup
```

Cleanup must confirm port 8766 is closed and remove only the recorded temporary demo directory. Full V1 and Real Pilot remain separate later gates.

