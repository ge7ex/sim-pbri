# Resume checkpoint — 16 October 2026

## State

I-4 / I-5 agreed technical scope was completed in the 15 October candidate at 39a24b4. This follow-up restores access to the retained demo for owner review. It does not claim production readiness or Senior approval.

## Changes

- Added `phase-i-demo.ps1 -Action Start` to reopen the existing isolated SQLite demo without migration, reseeding or deleting data.
- Fixed restoration of originally unset caller environment variables (unset and empty must not be conflated).
- New demo credentials exclude whitespace. Refreshed only the three existing synthetic demo passwords; no password is committed.
- Added non-destructive startup refusal checks and owner UAT instructions.

## Fresh checks

See docs/qa/DEMO_*_2026-10-16.json: actual Start returned login HTTP 200, preserved SQLite/credential bytes and restored the exact caller DB environment state. Setup/Start refused an existing running demo without changing its files or listener. Browser login and role-page HTTP 200 passed for Admin, Staff and Lecturer after password refresh. PHP syntax and Pint checked the changed seeder. Git whitespace check passed.

The prior full regression result (191 tests / 1707 assertions) is dated 15 October, not rerun for these demo tooling changes. Prior security scan 42a2fa7a-05b1-44cb-9018-4394ab93913f remains sealed on its original snapshot; its booking concurrency finding was addressed and separately verified in the candidate evidence.

## Continue

Demo: http://127.0.0.1:8766. Keep it running for owner presentation. Private credentials are in the current user's TEMP/sim-pbri-phase-i-demo/credentials.txt; do not commit them. If stopped, run Start, not Cleanup. Follow OWNER_UAT_CHECKLIST.md and fix actual reported failures. Owner/Senior acceptance and real Pilot operational gates are still pending. Do not invent institutional data or claim acceptance.

No runtime MySQL changes, main merge, Export or deployment. All changes stay on codex/i4-gpt-classic-handoff-2026-10-14. Stop new work and checkpoint if Codex quota remaining reaches 10%.
