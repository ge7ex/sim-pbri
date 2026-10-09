# Booking nullable-relations browser regression

This is a Playwright CLI `run-code` function. It is separate from `php artisan test`.

Run only with an isolated SQLite QA app at `http://127.0.0.1:8765`, never runtime MySQL/port 8000. Sign in normally as a synthetic own-College Staff. Fixture requirements: pending booking ID 1, an actual selected simulator asset/type, and at least one status transition with a permitted actor. It must appear in the history list and the first Review result. No real data/credentials are needed.

```powershell
playwright-cli --session <isolated-qa-session> run-code --filename tests/Browser/BookingNullableRelations.js
```

The script changes only intercepted Inertia responses, one case at a time: null transition actor, null amendment actor, null detail simulator type, null review simulator type. It asserts one h1, visible fallback and zero page errors for each case, then removes its interception. It never submits forms or edits the database. Real foreign-name redaction is separately covered by BookingRelationPrivacyTest.

Fresh executed results and red-before/green-after evidence: `docs/qa/PILOT_UAT_VERIFICATION.json`. QA database, synthetic credentials and browser session were cleaned up after the recorded UAT; recreate an isolated fixture before running again.
