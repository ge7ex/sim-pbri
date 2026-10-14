# SIM PBRI — I-4 continuation for GPT Classic

Date: 14 October 2026. Owner instructed stop at <=10% remaining quota; last observed primary remaining **7%**. New development/security review stopped; only saving/checkpointing continued.

## Repository and limits

- Repository `C:\xampp\htdocs\sim-pbri`; branch `backend/inertia-foundation`.
- Application commit `6a914e7c32401880e59c0d2ce3cb75ebe7100a40` completes I-3 fixes and evidence. The following save commit adds only I-4 results/handoff; verify actual HEAD and clean status first.
- Never reset/seed/clear cache/restore runtime MySQL; no real institutional data invention, no Export, no main merge, no deployment. Isolated schema testing permitted after reviewing guards. Previous user-approved encrypted MySQL backup/restore drill dated12 October need not be rerun merely to refresh date.
- I-0 approved baseline replaces missing v12. I-1/I-2 accepted functional scope complete. I-3 complete for agreed labels/keyboard/responsive/error scope; actual NVDA/Narrator speech is optional additional assurance and NOT VERIFIED. Owner explicitly said skip and proceed I-4. I-4 IN PROGRESS; I-5 not started/closed. Full V1 and Real Pilot NOT YET READY.

## Fresh I-4 results at application commit6a914e7

1. Full `php artisan test`: **190 passed /1,705 assertions**. Second full run with `SESSION_ENCRYPT=true`: same counts, PASS. SQLite `:memory:`, array cache/session/mail and isolated TEMP storage removed; no runtime MySQL in PHPUnit.
2. Production build PASS:686 modules, JS368.69kB/105.18kB gzip. No package installed or lock changed.
3. Composer audit JSON: advisories=[], abandoned=[]; npm audit total vulnerabilities0. Exit0 each. Audit package metadata only; not a security-scan clean verdict.
4. Pint changed PHP check PASS; diff whitespace repaired before I-3 commit.
5. SQLite demo restore PASS: integrity and27 table row counts match, source unchanged.
6. Existing isolated MySQL concurrency checker PASS create/recall under REPEATABLE READ; disposable generated schema removed. It **does not cover scenario_id + outer controller transaction**; do not claim all booking concurrency verified.
7. Read-only normal MySQL `migrate:status`:21 Ran,0 Pending; no migrations executed against normal DB.
8. Guarded demo cleanup PASS:port8766 closed, TEMP demo directory removed. Synthetic credentials deleted with fixture. Normal server8000 untouched.

## Highest priority discovered issue — not fixed

Candidate `candidate-booking-scenario-snapshot`, CWE-362, proposed medium. Independent baseline and focused reviewer agree on source mechanism; parent inspected the same control. No specific runtime reproduction or remediation yet.

`BookingController::store` lines101-103 starts an outer DB transaction. `CreateBookingAction` lines60-78 does plain Scenario/recommendedResources reads before nested transaction/resource locks at81-94. Under MySQL REPEATABLE READ, those reads establish a snapshot before waiting for shared inventory locks. Ordinary availability exists/sum queries (`BookingAvailabilityResolver`:35,42,57) may miss a competing commit after the wait and create overlapping Pending reservations. `SimulatorAssetLocker`:12-17 explicitly documents this hazard.

Counterevidence: ordered resource/asset/type locks, actor College/status/capacity/quantity checks; without scenario_id this early read absent; READ COMMITTED avoids this particular mechanism; approval rechecks but Pending already reserves. Integration worker invokes action directly WITHOUT scenario or controller outer transaction and cannot refute the candidate.

Next: reproduce with guarded disposable MySQL schema, valid own-College scenario, actual outer transaction, competitor commit while worker waits. Verify server isolation in test schema. Make test red, then minimal fix moving nonlocking reads after all booking mutexes inside actual transaction; preserve atomic Draft deletion. Do not remove outer transaction blindly (would break Draft atomicity), weaken locks, change global isolation or patch just UI. Run focused regression then full suite/build if touched source warrants.

## Security scan state

- Current Standard scan `42a2fa7a-05b1-44cb-9018-4394ab93913f`, target immutable6a914e7, whole repository. Running/partial, **not completed**.
- Initial candidate checkpoint accepted via `record_codex_security_scan_draft` complete:false, coverage.partial. Findings empty means no finalized finding, not no vulnerabilities.
- Preflight ready:delegated_workers pass, six-slot capability unknown; actual session cap4. Security skill explicitly authorized independent preflight/baseline/focused workers. All returned; no ongoing work needed.
- Later threat-model/coverage draft rejected by schema (first threatModel structure, then surfaces require `disposition`, valid values reported/no_issue_found/rejected/not_applicable/needs_follow_up). **Those model/coverage updates were NOT saved.** Do not assume canonical threat model exists. Retrieve authoritative context before continuing; don't duplicate scan or declare completion.
- Scan directory: `C:\Users\User\.codex\state\plugins\codex-security\scans\sim-pbri\6a914e7c32401880e59c0d2ce3cb75ebe7100a40_20261014T032704Z_y252lcov`.
- Old scan `c93862fe-47e6-405f-ace8-a4033830f715` still partial at olde3983e8, discovery8/20. Do not count as current coverage or cancel without owner request.
- Worker final responses in this Codex chat: `i4_baseline_security`, `i4_booking_security`, `i4_security_preflight`; exact fully_reviewed_files there. Baseline84 paths and focused33 paths overlap; union only, don't add counts or count architecture excerpts as audited files. If GPT Classic cannot retrieve them, reread corresponding source rather than pretend coverage. Remaining report/front-end/config/models/migrations/tests/docs/root/operational source not fully audited.

## Threat-model facts to preserve when resuming

Session auth and college/role/object policies protect bookings/master data/reports; private normalized JPEG endpoint, secret CLI account bootstrap/recovery, explicit Inertia identity, protected temporary SQLite QA. Assets:credentials/sessions/app key, Draft JSON/history, master/financial metadata/images, DB/logs, temporary credentials. Browser inputs, authenticated altered IDs, malformed permitted image uploads and concurrent reservations are attack surfaces; local operator authority is distinct.

Current machine is development runtime, not a verified production deployment; TLS/proxy/cookie/DB privileges/backups/logging unverified. Demo script uses **file sessions**, arraycache/syncqueue/synthetickey/privateTEMP storage; PHPUnit uses array sessions. Do not conflate. UTC persistence/Bangkok booking/report semantics retained. No sensitive values copied. Operations runbook exists but target deployment not rehearsed. Previous cache-clear incident remains documented; do not repair unknown transient state by restoring/resetting data.

## Files and next steps

- `docs/I3_VERIFICATION.md`, `docs/qa/I3_VERIFICATION.json`,220-row browser matrix,114 Chrome AX samples,110 Tab walks and screenshots are committed.
- Fresh I-4 raw logs/audit JSON in `docs/qa/I4_*_2026-10-14.*`; result summary `docs/qa/I4_VERIFICATION.json`.
- Reconcile requirements/UI-01/UI-02/QA/SEC/OPS rows, main QA JSON and operational runbook after candidate resolution. Current main QA historical entries should remain dated; do not overwrite prior results as current.
- Finish full exact-source security coverage/finding validation and scan sealing correctly; verify no Export routes using guarded route list; conclude operations readiness separately from Real Pilot owner/data/deployment gates.
- Prepare I-5 integrated Senior walkthrough only after I-4 actual closure. No push happened in this slice; do not assume GitHub current. Preserve branch and review before established authorized push.

## Harness commands

PHP `C:\xampp\php\php.exe`; Node `C:\Program Files\nodejs\node.exe`; npm CLI `C:\Program Files\nodejs\node_modules\npm\bin\npm-cli.js`; Composer PHAR `C:\Users\User\AppData\Local\ComposerSetup\bin\composer.phar`. Normal npm wrapper PATH broken, direct installed CLI works. No need install packages.

Scratch wrappers (not production feature files): `C:\Users\User\Documents\ChatGPT\sim pbri\output\i4-tests.ps1`, `i4-integration.ps1`. Read before running; tests refuse cached config/force memory DB and private temp storage; integration script itself enforces generated disposable schema before migrate. First integration execution was rejected by auto review; source inspection proved guards and retry approved. No rejected execution altered DB.
