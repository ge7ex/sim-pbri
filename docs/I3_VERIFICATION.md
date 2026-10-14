# I-3 — cross-role UI and reliability verification

Date: 14 October 2026. Starting HEAD: `238bf178e171e4151ce64e1b72744be1bc9a0efa`, branch `backend/inertia-foundation`, clean before work. This slice uses the owner-approved I-0 requirements baseline.

**Browser/keyboard/reliability slice: verified for the cases below.**
**I-3: COMPLETE for the agreed handoff scope.** On 14 October the owner confirmed proceeding to I-4 without the additional native speech check. The original handoff asks for screen-reader labels; Chrome accessibility-tree verification covers that requirement. Native speech remains NOT VERIFIED, not a fabricated PASS.
Full V1 Senior Review and Real Pilot remain **NOT YET READY**. I-4 and I-5 have not been closed by this UI work.

## Reproduced problems and fixes

| Problem | Before evidence | Change and after evidence |
|---|---|---|
| Staff review could be activated again while a request was held | Approve remained enabled; no busy state or loading status region | Shared action state guards both methods; buttons/reason input disabled; busy state plus live status outside the busy region; intercepted delayed approve/reject sends only one request and controls recover on validation |
| Review transport failure supplied no visible feedback | Aborted synthetic action restored the button but exposed zero alert messages | Inertia 3.7 callbacks show safe network/server warnings asking users to verify request status before retrying; no automatic retry; network and HTTP 503 cases pass at 390px |
| Successful keyboard approval lost focus | Active element became BODY after the selected card disappeared | Success/validation/request-error feedback receives focus after render; actual SQLite approval now focuses the status paragraph; error focus is asserted in the browser regression |
| Forbidden page had no usable Thai explanation/return | HTTP 403 showed only `403 / FORBIDDEN`, with no main landmark | A Thai standalone 403 view exposes one main/h1 and a relative safe home link; authorization still returns 403; Staff/Lecturer keyboard return checks pass at all four widths |
| Many controls/links were smaller than the accepted 44px touch target | Shared controls were 42px; filter actions 40px; resource-create summary 32px; compact table links and checkbox labels were smaller | Shared minimum hit areas and the three overriding scoped rules were corrected; post-fix mobile samples have no rounded visible target below 44px; all affected responsive samples were rerun |

The review fix uses the installed Inertia callbacks (`onFinish`, `onSuccess`, `onError`, `onNetworkError`, `onHttpException`), verified from the local 3.7.0 type declarations. Controller authorization, transactions, approval rules and history remain enforced server-side. The 403 view renders a generic explanation, not exception details.

## Fresh evidence

- **19 PHP tests / 132 assertions PASS**: authorization/profile/permissions plus booking transitions, including the new Thai 403 regression. Forced SQLite `:memory:`, array cache/session and isolated temporary storage; temporary storage removed. This is a targeted run, not a fresh full suite.
- **Frontend build PASS, 686 modules**. Final application JS is 368.69 kB / 105.18 kB gzip. No package added. Earlier patch quoting failure was corrected before the passing build.
- **Pint check-only PASS** after one test-spacing correction. `git diff --check` passes.
- **220 role/data/viewport samples**: Admin 92, Staff 84, Lecturer 44; populated and actual synthetic zero-Master-Data Colleges; widths 1440/1024/768/390. Responses were 200 with one h1/main. No document overflow, missing programmatic control names, broken visible aria-describedby references or page errors were observed in these samples.
- **110 full Tab walks over enabled visible controls** at 1440/390: all inventoried controls reached, with no missing computed focus indicator for the scored controls. Native date subfields were not judged by host-outline metrics; a captured report date-field focus was visually inspected and showed the focus ring and selected segment.
- **8 public-page samples** (`/`, `/login`) and **8 forbidden-page samples** (Staff/Lecturer) pass the four-width overflow checks; forbidden return link is keyboard-reachable.
- Mobile navigation opens with Space and closes with Escape, returning focus to the toggle; the skip link enters main via keyboard.
- Staff review delayed-action, duplicate-event guard, validation recovery, actual synthetic approval/empty queue and report loading/date validation/zero-state/reload/back checks pass.
- Admin account delayed 422 field error and network failure restore retry controls and expose accessible errors; Lecturer availability failure exposes an alert and keeps Submit disabled; invalid login exposes its field error.
- Eight sampled design-token foreground/background pairs have calculated contrast ratios from **4.95 to 15.12**. This sample does not establish contrast compliance for every rendered/pseudo/disabled element.

The matrix was rechecked after shared touch CSS changed. The final three-page overrides reran only their 64 affected samples, retaining 156 unaffected samples. Final feedback/failure/focus changes were checked through the dedicated review regression and an actual synthetic approval; no unrelated full-suite repetition was used.

## Chrome platform accessibility-tree verification

PASS: 114 samples using Chrome DevTools `Accessibility.getFullAXTree`, across populated/empty data and 1440/390px, including four public samples. Exposed interactive controls have names; sampled pages have one h1 and main landmark. Roles, names and headings were recorded, not input values. Evidence: `docs/qa/I3_CHROME_AX_2026-10-14.json`. This validates browser accessibility semantics, not NVDA/Narrator speech.

## Regression and artifacts

- `tests/Browser/ReviewLoadingCheck.js`: Playwright CLI function for a fresh, authenticated synthetic Staff session on port 8766; it intercepts both action responses and network/503 failures, never sends those intercepted actions to the database.
- `tests/Feature/Authorization/PermissionTest.php`: server-side Thai 403, landmark and relative-return-link regression.
- `docs/qa/I3_VERIFICATION.json`: summary and source hashes.
- `docs/qa/I3_BROWSER_MATRIX_2026-10-14.json`: individual page/viewport receipts.
- `docs/qa/screenshots/I3_403_MOBILE_2026-10-14.png` and `I3_NATIVE_DATE_FOCUS_2026-10-14.png`: synthetic/non-personal visual evidence.

Impeccable/audit and UI/UX Pro Max guided semantics, touch areas, focus and state checks against the existing calm institutional navy/slate design context. Ponytail Review was applied to the connected frontend/action/error paths. No independent subagent review or formal accessibility certification is claimed. Performance instrumentation and a new dark-mode design were not part of this slice; build size and the measured UI paths are the available evidence.

## Tooling limits and remaining gate

Initial harness runs counted closed-details controls and native date subfield Tabs incorrectly, used a wrong mobile selector, and then used an outdated Inertia page-data location/versionless request. The harness was corrected against the actual DOM (`script[data-page]`) and visibility behavior. Those failures were not recorded as app defects; completed checkpoints were reused when continuing.

Actual NVDA/Narrator speech, reading order, live announcements and native-dialog behavior have **not** been verified. Native computer control is unavailable in this session. The earlier report incorrectly made native speech a mandatory gate. The owner confirmed skipping it and proceeding to I-4. The optional checklist remains available for additional assurance; a future reproduced defect must still be fixed.

The 220-page matrix does not mean every conceivable expanded edit/pagination/error combination was exercised. It combines with the I-1/I-2 and prior Master Data workflow evidence; native assistive-technology speech remains an explicit limitation. No user-facing Export was introduced.

## Data boundary

Only the guarded temporary SQLite demo was migrated/seeded. An additional fictional College with three roles and no Master Data was created within that verified demo database for zero-state checks. Test/browser secrets stayed local and are absent from reports and Git. Network/validation response fixtures are identified as simulated; actual approval and forbidden checks are separately identified above.

No MySQL connection, cache clear, real-data import/restore, main merge, deployment or push occurred. Cleanup outcome is recorded in the summary JSON. Full security review and integration/deployment assurance belong to I-4.

