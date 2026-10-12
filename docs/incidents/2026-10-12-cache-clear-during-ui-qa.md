# Incident: MySQL cache clear during UI QA

Date: 12 October 2026
Repository: `C:\xampp\htdocs\sim-pbri`
Branch: `backend/inertia-foundation`
Status: Contained; pre-change cache baseline unavailable

## Summary

During isolated UI verification, `php artisan optimize:clear` was run from the project shell to refresh cached frontend/view state. The command did not inherit the synthetic demo environment. The repository `.env` configured `DB_CONNECTION=mysql` and `CACHE_STORE=database`, so Laravel cleared its database-backed cache store.

## Evidence

- Command output reported the Laravel cache clear step completed.
- A subsequent read-only aggregate query found `cache=0` and `cache_locks=0`.
- No pre-command row counts or contents were captured. The count and content of any removed ephemeral cache/lock entries therefore cannot be established.
- No application table was queried or intentionally modified in this follow-up; no migration, restore, or data import was performed.
- Synthetic demo data lived in a separate temporary SQLite database and was removed by the guarded cleanup script.

## Impact and response

The database-backed cache and lock entries were cleared. Application records are not stored in these tables, but removal of active cache/lock entries could affect transient application state. No restoration was attempted because no pre-change state was captured and reconstructing ephemeral entries from an ordinary row count is not possible.

The synthetic demo has been shut down and its temporary directory removed. No further command that accesses MySQL was run after the read-only count. The feature branch was not pushed or merged.

## Follow-up

- At the next maintenance window, the owner can inspect application/job logs and compare available MySQL binlog or backup evidence if they need to determine whether transient locks were active at the time.
- Keep future local demo operations explicitly scoped to the temporary SQLite environment. Do not run framework cache-clear commands from a shell that loads the project `.env` unless their target store is deliberately isolated.
- Preserve the existing MySQL data; do not restore or recreate cache entries without evidence and an explicit recovery plan.
