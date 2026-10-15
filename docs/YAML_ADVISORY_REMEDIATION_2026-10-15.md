# YAML development dependency advisory remediation — 15 October 2026

Fresh Composer audit reported PKSA-xz3r-9gp4-4m1z / CVE-2026-109413 for symfony/yaml8.1.2. The official Symfony advisory search result identifies8.1.6 as a fixed version: https://symfony.com/blog/cve-2026-109413-unbounded-recursion-in-the-yaml-inline-lexer-lets-a-single-document-crash-the-process . The direct page fetch was unavailable; package advisory metadata and actual update/audit results are retained separately.

`composer why symfony/yaml` identifies Laravel Sail, a development dependency. No application YAML input/parser call was found in app/routes/config/bootstrap/resources. This is dependency remediation; no remotely exploitable SIM PBRI YAML route is claimed.

Dry-run and actual `composer update symfony/yaml:8.1.6 --minimal-changes --no-scripts --no-interaction` changed one existing package, no installs/removals or root constraints. GitHub source/dist references changed only for that patch; no lifecycle scripts ran. Composer validate passed. Fresh audit after update returns0 advisories/abandoned. npm audit returns0 vulnerabilities.

YamlDependencySafetyTest: ordinary services YAML retains expected structure;512-level inline input raises ParseException. PASS1test/2assertions. Full guarded SQLite suites after patch:191tests/1707assertions each (normal/encrypted sessions). Build remains686modules. Existing actual-controller scenario and simulator concurrency checks pass in disposable schemas, which were removed.

An independent final-delta reviewer found no concrete surviving bypass/regression in the booking lock ordering, Draft atomicity, CSS hierarchy, guarded harness or YAML lock/test changes. Static limits are distinct from executed tests. Original full security scan42a2fa7a completed on immutable6a914e7 with one medium concurrency finding; it is not a zero-finding report or a scan of this updated dependency.
