# I-4 final technical verification — 15 October 2026

**I-4 COMPLETE for agreed software/security/operational-preparation scope. I-5 remains to be executed. Real Pilot NOT READY.**

Application source:8ec55d2 on codex/i4-gpt-classic-handoff-2026-10-14. Historical reports remain dated; current machine-verifiable results are in docs/qa/I4_FINAL_VERIFICATION_2026-10-15.json.

## Security

Existing Standard scan42a2fa7a-05b1-44cb-9018-4394ab93913f was resumed, remaining18paths reviewed and coverage reconciled; accepted final semantics were sealed successfully. Target is original6a914e7. Report contains **one medium finding**, not zero: Scenario plain reads before inventory locks under REPEATABLE READ. Originalcode stays vulnerable in that immutable report. Actual-controller red/green regression verifies laterfixc4502ce; sixcurrentcases and twoexisting simulator cases pass in generated localMySQL schemas that were dropped.

Generated report remains at `C:\Users\User\.codex\state\plugins\codex-security\scans\sim-pbri\6a914e7c32401880e59c0d2ce3cb75ebe7100a40_20261014T032704Z_y252lcov\report.md`. Its SHA256 and exactscanstatus are in the JSON. The workbench's old-revision triage is still open; it cannot offer remediation on a different checkout. This does not erase the demonstrated currentcodefix or imply the old report scannednewcode.

Independent final-source delta review covered6a914e7→c5aff10 plus YAMLpatch/unitregression, connected transaction/request/model/service callers and added evidence for secrets. No concrete surviving bypass/regression found. Runtime evidence independently checks the reported race, Draft atomicity, roles and workflow. This is explicit delta assessment, not a second registered repository scan.

Fresh dependency audit found a new symfony/yaml advisory. Only devtransitive8.1.2→8.1.6 was patched, no rootconstraint/newdependency/script changes. Benignparse and bounded rejection regression pass; final Composer/npm audits0. See YAML_ADVISORY_REMEDIATION_2026-10-15.md. Dependency implementation/archiveauthenticity was not exhaustively audited.

## Fresh checks

- FullguardedSQLite tests **191passed/1707assertions**, twice (normal and encrypted sessions); isolatedstorage cleaned and caller envrestored. PHP8.5.7.
- BuildPASS686modules; JS105.18kB gzip. ChangedPHPPint and Composer validatePASS.
- Chrome **116pages**:3roles×theirallowedroutes×1440/1024/768/390; oneh1/main, no missingcontrolnames/documentoverflow/Exportcontrols/pageerrors.
- **7roleforbidden routes** returnThai403. Accountbrowser15checks; Draftbrowser7checks; reportfinancialpayload8samples (Adminpresent/Staffabsent); Review4delayed/error/duplicate/network503cases pass. The initial generic matrix's reportprop assertion used a wrong optionalpath; corrected dedicated8sample check asserts top-level assetStatistics and filters. No apppatch needed for that harnessissue.
- Registered51routes/noExport scan and unchangedpermission routes are supported by currentcode/review and14Octrouteinventory. NativeNVDA/Narrator speech remains owner-skipped NOTVERIFIED; ChromeAX/keyboard evidence is scoped, not certification.

## Operational boundary

No productiontarget exists. TLS/proxy/cookies/leastprivilege/logretention/recurringoffhostbackups/realdata/accounts/owneracceptance remain RealPilot gates. Existing deploymentrunbook, historical21Ran/0Pending status and12OctauthorizedencryptedMySQLrestore remain accurately dated; no migration/restore was rerun merely to refreshdate. NormalMySQLrows/cache untouched, no mainmerge/Export/deploy.

SyntheticSQLitedemo currently active for the immediately following I-5 walkthrough and ownerrequestedpresentation; secrets remain in privateTEMPfile, never reports/Git. Record its final retained/cleanup state after I-5.
