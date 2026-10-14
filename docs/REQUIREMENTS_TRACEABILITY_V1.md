# SIM PBRI V1 Requirements Traceability — working baseline

Date: 14 October 2026 (I-0 source baseline refresh)
Application branch/source baseline: `backend/inertia-foundation` / `e3983e8450cbeab2123eebf54e3579a1cb922d1e`. Original v12 source remains unavailable. Execution results in existing rows are inherited; the 14 October slice verifies source mappings, not fresh runtime behavior.

## Source note

The active source for this first pass is `SIM_PBRI_FULL_PRODUCT_COMPLETION_PHASE_I_HANDOFF_2026-10-12.md`, especially §§1–9. The original `project_sim_flow_v12.md` is still unavailable in the repository or known Downloads path. A user-provided secondary report, `SIM_PBRI_Original_Requirements_Explained_For_Everyone_2026-10-12.html`, was found in Downloads; it contains 60 analytical checkpoints and cites v12, but its evidence snapshot is the older `d2e84e7` commit. It is useful to identify likely gaps, not a replacement for validating exact source wording against v12. Rows below combine handoff-derived scope, that secondary report, and current code/tests; exact v12 traceability remains unverified; the owner-approved handoff/decision baseline is fully mapped below.

Status: **Match** = verified implemented; **Partial** = important part missing or evidence incomplete; **Missing** = no current workflow; **Decision** = material behavior cannot be established from available source.

| REQ ID | Intended behavior (handoff-derived) | Current verified behavior | Status | User impact / V1 behavior | Priority | Source and evidence | Implementation / test / decision |
|---|---|---|---|---|---|---|---|
| ACC-01 | Authorized personnel sign in/out; roles restricted to Lecturer/Staff/Admin | Login/logout, rate limiting, access-profile middleware; these three roles only | Match | Preserve; no public registration | P0 | `routes/auth.php`; `UserRole`; AuthenticationTest; AccessProfileTest | Rechecked baseline |
| ACC-02 | College Admin can provision and maintain real users without local-only seeder | Admin-only list/search/create/edit/deactivate/reset; signed-in College fixes scope; account mutations audited | Match in code/tests/browser for create and one-time hide | Deliver credentials privately; no email send/public registration | P0 | Access routes/controller/requests; `UserManagementTest`; isolated Chrome Admin session | I-1 implemented; browser created only synthetic local SQLite account |
| ACC-03 | Bootstrap first Admin; prevent last Admin loss; recover safely | `sim:bootstrap-admin` and `sim:recover-admin-password`; self and last active Admin protections | Match in code/tests; console commands tested | Keep console commands restricted to trusted operators | P0 | `app/Console/Commands`; `UserManagementTest` | Browser console workflow not applicable; commands do not print password |
| ACC-04 | Accounts can be deactivated/restricted and recover/change password | `is_active`, forced password change, own change-password page; auth-session hash invalidation on password rotation | Match in code/tests and fresh synthetic browser password-change/reset/deactivation journeys on 14 October | Use generated one-time passwords; email recovery remains unavailable | P0 | Migration 000015; active/access middleware; password routes; `UserManagementTest` | Browser credential change remains unverified; automated test source covers it; targeted browser check belongs to I-1 |
| COL-01 | Actor College is server-owned; no cross-College account/data switching | Access profile requires College; each feature scopes actors; no College selector | Match | Keep College immutable in account management; add foreign-id regression cases | P0 | `EnsureUserHasAccessProfile`; module policies/requests; existing privacy tests | Recheck in I-1/system audit |
| ROOM-01 | Admin manages rooms; staff operates them read-only; room can be ready/disabled/maintenance | Admin create/update/delete guarded; reversible disabled/maintenance; Staff read-only; Lecturer blocked | Match | Preserve histories and protected images | P0 | SimResource routes/policy; RoomDisabledTest; ResourceDeletionTest; RoomImageTest | Recheck in I-3 |
| ROOM-02 | Capacity, building/floor, responsible Staff, protected photos | Existing fields/forms and college-scoped responsible Staff; image controller protects role/College | Match | Keep; verify again by role and browser | P1 | sim-resource migration/forms; RoomImageTest; phase H browser evidence | Recheck in I-3 |
| ROOM-03 | Physical room dimensions | Nullable paired fields remain stored in metres for future use; dimension values and calculated area are not rendered on any page and are omitted from Admin and booking Inertia props. Existing values remain untouched when ordinary room fields are edited | Intentional variation: stored dimensions and hidden UI match the explicit owner decision | Preserve columns and values; no dimension UI or payload on any role; do not migrate/drop or backfill | P1 | Migration `2026_10_12_000018`; SimResource requests/actions/model; SimResourceController; BookingController; `SimResourceManagementTest` | Privacy regression test asserts dimensions are absent from Admin and booking props; full source v12 remains unavailable |
| ROOM-04 | Booking history retains room facts as they were at request time | New bookings persist nullable name/location/building/floor/capacity snapshots on booking_resource; history/detail/review/dashboard/calendar use snapshot; legacy nulls fall back with explicit notice on detail/review | Match for new bookings; legacy facts intentionally unknown | Do not backfill historical facts from current Master Data | P1 | Migration `2026_10_12_000016`; CreateBookingAction; booking queries and Vue pages; snapshot regression test | Implemented; fresh suite passes |
| EQ-01 | Manage equipment quantities/readiness and prevent unsafe deletion | College-scoped resource forms; deletion guards booking/history; quantities reserved/rechecked | Match | Preserve | P0 | SimResource tests; resource delete tests | Recheck in I-3 |
| SIM-01 | Manage Simulator Types/Assets, status, availability and maintenance | Admin create/update/status/maintenance; no physical type/asset deletion endpoint; Staff read-only; booking availability and exclusivity checks | Match | Preserve Admin-only finance fields | P0 | Simulator routes/actions/queries/tests | Recheck in I-3 |
| SIM-02 | Financial/depreciation data is Admin-only | Staff projections/query omit financial statistics; Admin uses existing depreciation service | Match | Keep field/query redaction | P0 | SimulatorManagementTest; ReportStatisticsTest; Phase H report | Recheck in I-4 |
| COURSE-01 | Maintain Course and Scenario metadata/recommendations | Admin add/edit Course/Scenario; update preserves relationships/recommendations | Match | Preserve current flexible recommendation behavior | P1 | ScenarioManagementTest; ScenarioPreferenceTest | Prior baseline pass recorded; not rerun for this I-0 slice |
| BOOK-01 | Create booking with room/resources/course/scenario and validate capacity/availability | Authenticated create, validation, transactional availability checks and resource pivots | Match | Preserve all current checks | P0 | CreateBookingTest; ScenarioBookingWorkflowTest; SimulatorBookingTest | Recheck full workflow in I-3 |
| BOOK-02 | Actor identity remains separate from editable display name/on-behalf use | Editable requester display name is stored separately; `requested_by_user_id` is actor-derived and ignored if forged | Match | Default name is actor; a supplied name is the person using/coordinating the room | P1 | `CreateBookingAction`; `StoreBookingRequest`; Create.vue; `CreateBookingTest` | Implemented and regression-tested |
| BOOK-03 | Draft before submission; Draft must not reserve or be reviewable | Separate `booking_drafts` store; create/update/delete/resume limited to owning authenticated user and College; 10-draft cap; submit reuses full booking validation/availability checks and deletes the draft only in the successful transaction | Match in code/tests and recorded happy-path browser evidence; retention resolved by owner | Drafts remain until submitted or owner deletes them; no automatic expiry, as explicitly confirmed by owner | P1 | Migration `2026_10_12_000017`; `BookingDraftController`; `StoreBookingDraftRequest`; Create booking page; `BookingDraftTest` (7 tests) | Implemented and owner-confirmed retention; isolated browser saved/resumed a Draft, submitted the completed request, confirmed Draft removal, and Staff approved the resulting Pending booking; preceding full-suite pass recorded; not rerun for this I-0 slice |
| BOOK-04 | Review/approve/reject/recall/cancel with audit trail, ownership and collision rechecks | State transition actions/policies audit actor/reason and revalidate availability | Match | Preserve and repeat concurrent/transition tests | P0 | BookingStateTransitionTest; policy/actions; SimulatorBookingTest | Recheck in I-3/I-4 |
| REV-01 | Staff review can see contact details needed to coordinate request | Permission-protected review projection displays requester display name, immutable actor name, and requester phone; query scopes pending bookings and related users to actor College | Match | Review phone is limited to authorized same-College Staff/Admin; detail uses the existing same-College BookingView policy, not an owner-only policy | P1 | BookingReviewQuery; Review.vue; ScenarioBookingWorkflowTest; browser Staff review | Implemented and tested; foreign College sees no booking |
| REV-02 | Staff manually evaluates contextual/time cases; no automatic class inference | Review page exposes booking details and separate approve/reject actions; no automatic class classifier found. A required recorded checklist/note is not explicit in the available source | Match for manual decision / Decision for extra recorded field | Preserve human review; do not add automatic classification. Add a structured note/checklist only if v12 or owner requires it | P1 | Review controller/page; booking transition tests; secondary report `RULE-01/02` | Current behavior observed; dedicated recorded classification/checklist is OWNER DECISION NEEDED if required; preserve manual review |
| CAL-01 | Availability/calendar/history accurately show Pending and Approved reservations | Pending and Approved block; rejected/cancelled do not; filters College-scoped | Match | Preserve overlap and adjacent-time rules | P0 | BookingAvailabilityResolver; BookingCalendarQuery; state tests | Recheck in I-3 |
| STAT-01 | Dashboard/statistics derive from approved activity, useful filters and zero states | SQL aggregates scoped by College and approved subset; zero values/nulls handled | Match | Preserve query-based aggregates; no cross-College selector | P1 | ReportStatisticsTest; PHASE_H_VERIFICATION | Recheck in I-4 |
| STAT-02 | Admin finance visible only to Admin; no export | Role-scoped finance payload; source and registered-route scans find no user-facing export/download feature | Match | No user-facing Export anywhere | P0 | route inventory; ReportStatisticsTest; Phase H evidence | Repeat full source/route/browser scan in I-4 |
| UI-01 | Thai, accessible, responsive Admin/Staff/Lecturer workflows | Synthetic browser verified Admin room create/edit forms hide dimensions, Staff review/contact/report privacy and approved-history flows, Lecturer Draft and capacity validation. Lecturer booking form controls all have accessible names, focus outline is visible, over-capacity help is announced as status, and submit remains disabled. Responsive measurements cover Lecturer form at 1440/1024/768/390, Staff review at 390, Admin resources at all four widths; Staff rejected-history empty state and labeled filters verified. | Partial | Finish full keyboard and screen-reader paths plus remaining role-specific error/loading checks before Senior-ready verdict | P1 | `V1_COMPLETE_SYSTEM_QA.md`; `SimResourceManagementTest`; isolated SQLite browser UAT | No real accounts/data used. Spot checks only: no complete assistive-technology run; Admin-led walkthrough/owner acceptance remain open |
| OPS-01 | Repeatable deployment, backup/restore, rollback and timezone plan | Asia/Bangkok booking/report paths have focused evidence; SQLite demo restore passed; one temporary local MySQL AES-256-GCM backup/restore drill passed with exact row counts matching across 27 tables and source counts unchanged; institutional schedule, off-host retention/recovery objectives and real master data remain unverified | Partial | Verify institution-approved backup operations and pilot data/recovery requirements; do not assert production readiness | P1 | `docs/DEPLOYMENT_RUNBOOK_V1.md`; `V1_COMPLETE_SYSTEM_QA.md`; `docs/REAL_PILOT_READINESS_GATES.md` | Local encrypted restore test completed and temporary artifacts removed; no production disaster-recovery run
| TIME-01 | Institution-local booking time and reports | Owner confirmed Asia/Bangkok. Booking datetime-local input, Draft resume, display, calendar grouping, booking date filters and report date boundaries use Bangkok; instants remain stored/query-bounded in UTC and global app timezone is unchanged | Match for implemented booking/report paths; broader operations timezone remains part of pilot runbook | Preserve UTC persistence and existing timestamp interpretation; use Asia/Bangkok for user-facing booking/report day semantics | P0 | `resources/js/Support/dateTime.ts`; `BookingController`; booking queries; `ReportFilterRequest`; `ReportStatisticsQuery`; timezone tests; read-only MySQL DATE_ADD literal check | Focused timezone tests/full suite pass; MySQL date expression returns expected local date; no DB timestamp conversion performed |
| SEN-01 | Isolated realistic Admin/Staff/Lecturer demo and senior feedback process | SQLite TEMP-only setup, synthetic fixture seeder, cleanup script, walkthrough and feedback register exist; prior/current runs verified Staff approval and report privacy, and the latest integrated run verified Lecturer Draft submission through Staff approval before cleanup closed port 8766 and removed TEMP demo data | Match for repeatable demo assets; Partial for full walkthrough | Keep demo isolated; complete browser role and responsive checks before marking Senior candidate ready | P1 | `scripts/qa/phase-i-demo.ps1`; `seed-phase-i-demo.php`; Senior demo docs | I-5 assets ready; Lecturer-to-Staff Draft/submit/approval and Staff report slices passed; Admin-led Senior walkthrough and owner feedback remain incomplete |
| BOOK-05 | Booking note and custom equipment requests outside the catalog | Validated note and custom equipment name/quantity/note persisted; immutable actor and College still server-derived | Source/test references present; browser edge coverage incomplete | Keep bounded optional notes and custom requests without inventing catalog records | P1 | `app/Modules/Booking/Http/Requests/StoreBookingRequest.php`; `app/Modules/Booking/Actions/CreateBookingAction.php`; `tests/Feature/Scenario/ScenarioPreferenceTest.php` | I-2 source mapped; I-3 targeted validation recovery |
| EQ-02 | Scenario template quantities and recommendation flags are optional, not trusted client authority | Scoped template inputs; booking action derives recommendation flags; users may omit recommended items | Source/test references present | Preserve flexibility and College limits | P1 | `app/Modules/Scenario/Actions/SyncScenarioEquipmentTemplateAction.php`; `tests/Feature/Booking/ScenarioBookingWorkflowTest.php` | I-3 completeness mapped |
| SIM-03 | Retain working normalized model and depreciation calculation unless actual business problem | Calendar-year straight-line estimate uses integer cents, caps depreciation and returns null for incomplete metadata | Retained implementation; institutional accounting policy not verified | Estimate only; do not claim accounting entries | P1 | `app/Modules/Simulator/Services/StraightLineDepreciation.php`; `tests/Feature/Simulator/SimulatorFoundationTest.php` | No formula rewrite assumed |
| FILE-01 | Protected inline room images; no unsafe upload/public file path or historical deletion | Bounded raster input, GD JPEG normalization, private disk, policy-controlled retrieval and post-commit cleanup | Source/test references present; scan incomplete | Inline authenticated images allowed by no-Export rule | P0 | `app/Modules/SimResource/Services/RoomImageStorage.php`; `app/Modules/SimResource/Http/Controllers/SimResourceController.php`; `tests/Feature/Booking/RoomImageTest.php` | I-4 full scan and evidence review |
| SEC-01 | Independent full security review, privacy/authorization and transaction safety | Existing full scan is partial; auth/privacy/rollback test sources and separate integration checker exist | Partial verification; no final full-scan verdict | No zero-vulnerability conclusion from empty draft findings | P0 | `tests/Feature/Booking/BookingRelationPrivacyTest.php`; `tests/Integration/SimulatorConcurrencyCheck.php`; scan ID recorded in I-0 baseline JSON | I-4 owns discovery/validation; default PHPUnit does not run MySQL integration checker |
| QA-01 | Exact-version, guarded automated and browser evidence; zero/one/many, foreign College, malformed relations, auth/rate-limit, collision/rollback, SQL and no Export | SQLite safety guard and domain tests exist; prior suites recorded as passing; remaining browser/concurrency evidence not complete | Partial verification | Report source inspection separately from executed checks | P0 | `phpunit.xml`; `tests/DatabaseSafety.php`; `tests/Unit/DatabaseSafetyTest.php`; `docs/V1_COMPLETE_SYSTEM_QA.md` | I-4 reconciliation and focused required checks |
| UI-02 | Public/login/unauthorized pages, role navigation, Thai validation/flash, back/reload and loading behavior | Page components and role middleware exist; prior fixes include CSRF and Staff success status; complete journey coverage remains open | Partial verification | Verify named journeys at 1440/1024/768/390 and with keyboard/assistive technology | P1 | `resources/js/Layouts/AppLayout.vue`; `resources/js/Pages/Auth/Login.vue`; `resources/js/Modules/Access/Pages/Users/Index.vue`; `resources/js/Modules/Booking/Pages/Review.vue` | I-3 browser matrix; no redesign assumed |
| OPS-02 | Preserve MySQL; isolated QA, secrets, permissions and controlled changes; investigate incident | Demo guards and operations guides exist; documented MySQL database-cache clear occurred with unknown pre-command baseline | Partial: incident disposition and deployment operations open | No reset/seed/restore/cache clear against real DB as routine QA | P0 | `scripts/qa/phase-i-demo.ps1`; `docs/incidents/2026-10-12-cache-clear-during-ui-qa.md`; `docs/DEPLOYMENT_RUNBOOK_V1.md` | I-4 incident and operator-safety review; Real Pilot operations remain separate |
| ARCH-01 | Preserve modular Laravel/Vue/Inertia architecture, established layers, supported dependencies and Git scope | Existing FormRequest/Policy/Action/Query modules and lockfiles; branch verified; no main merge | Source baseline mapped; release dependency checks inherited | No speculative roles, table renaming, unrelated rewrite, force push or discarded user work | P0 | `composer.lock`; `package-lock.json`; `app/Providers/AppServiceProvider.php`; module route inventory | Apply across I-1–I-5; later coherent commits verified before push |

## I-0 baseline refresh — 14 October 2026

### Authority and completion boundary

Owner instructions take precedence over the 12 October Full Product Completion handoff, which takes precedence over analytical references. The owner confirmed Bangkok booking time, indefinite owner-controlled Draft retention, and room dimensions hidden from every page including Admin. These are resolved requirements, not unanswered product gaps.

The original `project_sim_flow_v12.md` remains unavailable. The owner already confirmed it could not be found; the 14 October filename search in the project and Downloads found no copy. The secondary HTML is an older analysis of 60 checkpoints, not 60 approved requirements and not a substitute for original wording. Its IDs are mapped below solely to avoid dropping analytical topics.

The handoff-named standalone standards files (`agent.md`, `workflow.md`, architecture/coding/frontend/database/security/testing/Git/definition-of-done/design Markdown files) were not found in the repository inventory. The owner-provided AGENTS instructions govern this slice; README and existing project reports were inspected. `.impeccable.md` exists for later UI review.

**I-0: DONE against the owner-approved V1 baseline.** On 14 October 2026 the owner explicitly confirmed the 12 October handoff, confirmed decisions and this matrix as the V1 baseline. This approved source decision closes I-0; it does not claim exact v12 comparison. A mapped requirement can still be Partial: I-0 identifies the work; I-1 through I-5 implement and verify it.

### Fresh baseline versus inherited execution evidence

On 14 October, elevated read-only Git inspection confirmed HEAD `e3983e8450cbeab2123eebf54e3579a1cb922d1e`, branch `backend/inertia-foundation`, clean working tree, ahead 9 compared with the locally stored origin ref. A subsequent `git fetch origin` succeeded: origin/backend/inertia-foundation is `883b7440d6140eeed5885a8590844d03c6f1f79d`, with 9 local-only and 0 remote-only commits before this documentation commit. No pull, push or merge occurred. Initial sandbox Git errors were permission limitations; the elevated read-only check succeeded.

This refresh inspected source/test inventory and selected connected implementations. `docs/qa/I0_BASELINE_VERIFICATION.json` records source fingerprints, evidence-file checks and secondary checkpoint mapping. File existence and named test methods are source evidence, not test execution.

The preceding work recorded 189 passing tests / 1,700 assertions and a build of 686 modules. These commands were not rerun for this documentation slice. Older QA paragraphs describe the earlier 429-blocked attempt; the later successful result is inherited from the previous execution record. Full execution-evidence reconciliation belongs to I-4, not a fabricated fresh pass in I-0. The MySQL migration/row-count/restore descriptions above are historical snapshots, not a 14 October database inspection.

### Handoff coverage register

Each explicit product domain, gap candidate, invariant, verification gate and delivery artifact has a canonical destination. The canonical table describes the chosen V1 behavior; this register traces the source scope to it.

| Handoff scope | Canonical IDs / artifact | Remaining work package |
|---|---|---|
| §1.1; §4 I.1 authenticated personnel and real account operations | ACC-01–04, COL-01; `docs/ACCESS_MANAGEMENT_OPERATIONS.md` | I-1 verification |
| §1.2 room/equipment Master Data, readiness, capacity, quantities, responsible Staff, images/history | ROOM-01–04, EQ-01, FILE-01 | I-2 / I-3 |
| §1.3 simulator type/assets, status, valuation and maintenance | SIM-01–03 | I-3 / I-4 |
| §1.4 course/scenario edit, recommendations and quantities | COURSE-01, EQ-02 | I-3 |
| §1.5 booking creation, transitions, availability, calendar/history and audit | BOOK-01–05, CAL-01, REV-01–02, QA-01 | I-2 / I-3 / I-4 |
| §1.6 dashboard, statistics, restricted financial analytics | STAT-01–02, SIM-02 | I-3 / I-4 |
| §1.7 usable Thai desktop/mobile interfaces, empty/error/loading states | UI-01–02 | I-3 |
| §1.8 College isolation, financial redaction, real QA and isolated walkthrough | COL-01, SIM-02, QA-01, SEC-01, SEN-01 | I-3 / I-4 / I-5 |
| §1 no user-facing Export, including hidden controls/routes; protected inline images allowed | STAT-02, FILE-01 | I-4 |
| §2 actor-derived College; only three roles; no public/student booking | ACC-01, COL-01 | Preserve in all packages |
| §2 pending+approved block time; audit/ownership; Admin Master mutations; protected history | BOOK-04, CAL-01, ROOM-01, EQ-01 | Preserve in all packages |
| §2 preserve modular monolith; no speculative renaming, roles, cross-College analytics or automatic overlap rejection | ARCH-01, COL-01, CAL-01, STAT-02 | Preserve in all packages |
| §3 startup baseline and guarded QA; preserve changes and MySQL | QA-01, OPS-02, ARCH-01; baseline JSON | I-0 / I-4 |
| §4 I.2 editable display name with immutable actor | BOOK-02 | I-2 / I-3 |
| §4 I.2 safe Draft persistence, non-reserving/non-reviewable Draft, atomic revalidated submission | BOOK-03 | I-2 / I-3 |
| §4 I.2 optional room dimensions and immutable historical context | ROOM-03–04 | I-2 |
| §4 I.2 reviewer contact visibility and manual contextual review | REV-01–02 | I-2 / I-3 |
| §4 I.2 Scenario/Room flexibility; College lock; own-College reporting; retain model/calculation semantics | COURSE-01, COL-01, STAT-02, SIM-03, ARCH-01 | Preserve / decisions below |
| §4 I.3 all module workflows; reload/back/redirect reliability | ACC-01–04, ROOM-01–04, EQ-01–02, SIM-01–03, COURSE-01, BOOK-01–05, UI-02 | I-1 / I-2 / I-3 |
| §4 I.4 skills and actual responsive/accessibility fixes | UI-01–02; installed Ponytail, Impeccable and UI/UX Pro Max | I-3; Taste only where applicable |
| §4 I.5 zero/one/many, foreign College, malformed relations, finance, auth/rate limit, collisions, rollback, images, SQL | QA-01, FILE-01, SEC-01 | I-4 |
| §4 I.5 deployment config, permissions/storage/queue/session/logging, isolated restore, timezone and rollback | OPS-01–02, TIME-01; deployment runbook | I-4 / Real Pilot |
| §5 I-0… I-5 exit conditions; §9 all P0/P1 defects fixed and independently verified | this matrix, prioritized open-work register below, completion plan | Each package's own gate |
| §6 real app + separate synthetic demo; no public hosting or real-data fixtures | SEN-01, OPS-02 | I-5 |
| §6 plan, traceability, QA Markdown/JSON, demo script, feedback intake and real-pilot gates | all seven files in artifact register below | I-0 / I-4 / I-5 |
| §7 minimal verified changes, FormRequest/Policy/Action/Query, transactions, no secret leakage, additive changes | ARCH-01, COL-01, QA-01, OPS-02 | Preserve in all packages |
| §7 branch-only changes; verified coherent commits; no merge main/force push; preserve user work | ARCH-01 | Preserve in all packages |
| §8 accurate Thai delivery: HEAD/status, increments, gaps/variations, account flows, browser/privacy, fresh checks, cleanup, skills/limits and separate verdicts | `docs/V1_COMPLETE_SYSTEM_QA.md`, `docs/PHASE_I_PRODUCT_COMPLETION_PLAN.md`, Senior package | I-4 / I-5 |

### Decisions and retained variations

| ID | Chosen V1 behavior | Authority / consequence |
|---|---|---|
| DEC-01 | Booking/report UI uses Asia/Bangkok; persisted instants/global timezone remain UTC | Explicit owner confirmation; no historical timestamp conversion |
| DEC-02 | Draft remains until its owner submits or deletes it | Explicit owner confirmation; no expiry job |
| DEC-03 | Preserve optional room dimensions in storage; hide dimensions/area on every UI and Admin/booking props | Explicit owner confirmation; this is an intentional variation, not an unfinished dimensions UI |
| DEC-04 | College belongs to authenticated actor; only Lecturer/Staff/Admin; own-College reports | Handoff §§1–2; no central role/College switch |
| DEC-05 | Scenario recommendations remain flexible; no forced Scenario-to-Room constraint | Handoff I.2; new hard constraint needs owner decision if later requested |
| DEC-06 | Staff manually judges context; no automatic in/out-of-class inference | Handoff I.2; whether a dedicated recorded classification/checklist is required remains OWNER DECISION NEEDED; current reason/history is not proof of such a field |
| DEC-07 | Editable requester display name never changes authenticated audit actor | Handoff I.2; forged user/College IDs are not accepted |
| DEC-08 | Future room snapshots are recorded; legacy values remain unknown and visibly fall back | Handoff I.2; no invented/backfilled historical snapshot |
| DEC-09 | Console bootstrap/recovery and private one-time credential delivery; no email invitation/recovery without infrastructure | Handoff I.1 permits available secure workflow; outbound identity/mail integration remains unconfigured |
| DEC-10 | Type/assets use supported create/update/status flows; maintenance/history cannot be physically edited/deleted | Handoff I.3 says CRUD the product already supports; no physical asset/type delete feature is assumed |
| DEC-11 | Keep calendar-year straight-line estimate, zero residual/no purchase-year charge; not booked accounting entries | Existing tested service retained per handoff; institution accounting policy not asserted |
| DEC-12 | Use the 12 October handoff, confirmed owner decisions and this matrix as the accepted V1 requirements baseline | APPROVED by owner on 14 October 2026: “ยืนยัน baseline นี้เพื่อปิด I-0”; exact v12 comparison remains unavailable and is not claimed |

### Baseline decision and prioritized open work

| ID | Priority / impact | Next action | Exit evidence / owner |
|---|---|---|---|
| CLOSED-00 | Baseline decision resolved | Owner approved handoff+confirmed decisions+matrix on 14 October | I-0 DONE; recompare if original v12 is recovered later |
| CLOSED-01 | I-1 verification passed | Forced password change, reset/session invalidation, disable/reactivate and Staff 403 tested in synthetic Chrome | 15 checks passed; I1_I2_VERIFICATION.md |
| CLOSED-02 | I-2 verification passed | Owner delete, reload/resume, non-reservation, stale conflict and corrected submit tested in synthetic Chrome | 7 checks passed; broader UI matrix remains I-3 |
| OPEN-03 | P1 accessibility/reliability | Complete keyboard, screen-reader and role-specific zero/error/loading/back/reload matrix at stated viewports | Per-path evidence and actual fixes if defects reproduced; I-3 |
| OPEN-04 | P1 security assurance | Finish existing full scan; validate findings and fix confirmed defects | Final report/coverage for exact revision; I-4 |
| OPEN-05 | P1 operational incident | Review recorded cache-clear impact/available evidence; improve unsafe QA/operator instructions if reproduced | Incident disposition and any verified remediation; I-4; preserve MySQL |
| OPEN-06 | P1 evidence consistency | Reconcile stale heads/test totals and distinguish historical checks from fresh runs | Consistent QA Markdown/JSON; I-4 |
| OPEN-07 | P1 concurrency assurance | Inspect prior integration receipts; guarded SQLite success alone does not prove MySQL row-lock races | Exact integration evidence or explicit unverified limit; I-4; no MySQL run authorized by I-0 |
| OPEN-08 | P1 review readiness | Rehearse integrated Admin/Staff/Lecturer walkthrough and package evidence | Complete repeatable Senior Candidate; I-5 |
| PILOT-01 | Real Pilot gate | Real Master Data/users, approved operations hours/context policy, TLS/proxy, DB permissions, recurring backups/retention/RTO, monitoring and owner acceptance | Institution-owned approvals and target checks; not invented software results |

### Required artifact register

| Artifact | I-0 observation / later obligation |
|---|---|
| `docs/PHASE_I_PRODUCT_COMPLETION_PLAN.md` | Exists; I-0 status refreshed; later increments remain independently gated |
| `docs/REQUIREMENTS_TRACEABILITY_V1.md` | This available-source matrix plus complete secondary checkpoint mapping |
| `docs/V1_COMPLETE_SYSTEM_QA.md` | Exists; inherited results and stale follow-up paragraphs need I-4 reconciliation |
| `docs/qa/V1_COMPLETE_SYSTEM_QA.json` | Exists; do not turn file/reference checks into runtime test passes |
| `docs/SENIOR_DEMO_SCRIPT.md` | Exists; integrated rehearsal incomplete |
| `docs/SENIOR_FEEDBACK_LOG.md` | Exists; feedback follows delivery, not a substitute for missing implementation |
| `docs/REAL_PILOT_READINESS_GATES.md` | Exists; pilot operational approvals remain unverified |

The 14 October I-0 slice changes documentation only. No application code, MySQL access, migration/cache clear, test/build run, deployment, Export or main merge is part of this slice.

## Secondary analytical checkpoint crosswalk (60/60 mapped)

IDs below are namespaced as ANALYSIS: to avoid confusing old analytical IDs with canonical requirement IDs. Mapping is not a claim that 60 accepted requirements are complete.

| Analytical ID | Analytical topic | Canonical destination |
|---|---|---|
| ANALYSIS:FLOW-01 | เข้าสู่ระบบก่อนใช้งาน | ACC-01 |
| ANALYSIS:FLOW-02 | เลือกวิทยาลัย / หน่วยงาน | COL-01 |
| ANALYSIS:FLOW-03 | กำหนดวันที่และช่วงเวลา | TIME-01, BOOK-01 |
| ANALYSIS:FLOW-04 | ตรวจสอบเวลาซ้ำ | CAL-01, QA-01 |
| ANALYSIS:FLOW-05 | ห้องที่พร้อมจอง | ROOM-01, BOOK-01 |
| ANALYSIS:FLOW-06 | ประเภทและรายการหุ่น | SIM-01, BOOK-01 |
| ANALYSIS:FLOW-07 | รายวิชาและ Scenario | COURSE-01, BOOK-01 |
| ANALYSIS:FLOW-08 | อุปกรณ์ตาม Scenario | COURSE-01, EQ-02 |
| ANALYSIS:FLOW-09 | ปรับจำนวนอุปกรณ์ | EQ-01, EQ-02, BOOK-01 |
| ANALYSIS:FLOW-10 | ส่งคำขอเป็น Pending | BOOK-01, CAL-01 |
| ANALYSIS:FLOW-11 | Staff/Admin พิจารณา | BOOK-04, REV-01, REV-02 |
| ANALYSIS:FLOW-12 | ผลใน Dashboard / Report | STAT-01 |
| ANALYSIS:ROOM-01 | Room Master: เพิ่มห้อง | ROOM-01 |
| ANALYSIS:ROOM-02 | อาคาร / ชั้น | ROOM-02 |
| ANALYSIS:ROOM-03 | ขนาดทางกายภาพของห้อง | ROOM-03 |
| ANALYSIS:ROOM-04 | ความจุผู้เข้าใช้งาน | ROOM-02, BOOK-01 |
| ANALYSIS:ROOM-05 | ผู้รับผิดชอบห้อง | ROOM-02 |
| ANALYSIS:ROOM-06 | Active / Disabled | ROOM-01 |
| ANALYSIS:ROOM-07 | ความถูกต้องของข้อมูลย้อนหลัง | ROOM-04 |
| ANALYSIS:SIM-01 | Parent → Asset | SIM-01 |
| ANALYSIS:SIM-02 | ปีซื้อ ราคา อายุใช้งาน | SIM-01, SIM-03 |
| ANALYSIS:SIM-03 | ค่าเสื่อมราคา | SIM-03 |
| ANALYSIS:SIM-04 | ค่าบำรุงรักษา / ประวัติซ่อม | SIM-01, SIM-02 |
| ANALYSIS:SIM-05 | หุ่นไม่พร้อมใช้งาน | SIM-01, BOOK-01 |
| ANALYSIS:SIM-06 | ซ่อนราคา/การเงินจาก User | SIM-02 |
| ANALYSIS:SCEN-01 | Scenario สังกัดรายวิชา | COURSE-01 |
| ANALYSIS:SCEN-02 | ประเภทหุ่นที่แนะนำ | COURSE-01, SIM-01 |
| ANALYSIS:SCEN-03 | Equipment Template | COURSE-01, EQ-02 |
| ANALYSIS:SCEN-04 | Scenario ↔ ห้อง | COURSE-01, REV-02 |
| ANALYSIS:SCEN-05 | จัดการข้อมูลผ่านหน้า Admin | COURSE-01 |
| ANALYSIS:BOOK-01 | ชื่อผู้จองในฟอร์ม | BOOK-02 |
| ANALYSIS:BOOK-02 | เบอร์ติดต่อ | BOOK-01, REV-01 |
| ANALYSIS:BOOK-03 | จำนวนผู้เข้าใช้งาน | BOOK-01 |
| ANALYSIS:BOOK-04 | ไม่เกินความจุห้อง | BOOK-01, ROOM-02 |
| ANALYSIS:BOOK-05 | หมายเหตุ | BOOK-05 |
| ANALYSIS:BOOK-06 | บันทึกอุปกรณ์ที่แนะนำ | EQ-02 |
| ANALYSIS:STAT-01 | Draft | BOOK-03 |
| ANALYSIS:STAT-02 | Pending / Approved / Rejected / Cancelled | BOOK-04 |
| ANALYSIS:STAT-03 | Rejected ต้องมีเหตุผล | BOOK-04 |
| ANALYSIS:RULE-01 | ไม่จำแนกเวลาเรียนอัตโนมัติ | REV-02 |
| ANALYSIS:RULE-02 | บันทึกผลการตรวจเชิงบริบท | REV-02 |
| ANALYSIS:RULE-03 | ไม่อนุมัติทันทีหลังส่ง | BOOK-01, BOOK-04 |
| ANALYSIS:RULE-04 | Pending กันเวลา | CAL-01 |
| ANALYSIS:VIEW-01 | หน้าส่งคำขอ | BOOK-01, UI-01, UI-02 |
| ANALYSIS:VIEW-02 | หน้าตรวจคำขอ | REV-01, REV-02, UI-01, UI-02 |
| ANALYSIS:VIEW-03 | เบอร์ติดต่อในหน้า Review | REV-01 |
| ANALYSIS:VIEW-04 | Dashboard จำนวนสถานะ | STAT-01 |
| ANALYSIS:VIEW-05 | Room/Simulator/Equipment usage | STAT-01 |
| ANALYSIS:VIEW-06 | รายงานตามหน่วยงาน | COL-01, STAT-02 |
| ANALYSIS:MODEL-01 | Booking Request | BOOK-01, ARCH-01 |
| ANALYSIS:MODEL-02 | Booking Equipment | EQ-01, EQ-02, BOOK-05, ARCH-01 |
| ANALYSIS:MODEL-03 | Room | ROOM-01, ROOM-02, ROOM-03, ARCH-01 |
| ANALYSIS:MODEL-04 | Simulator depreciation/maintenance | SIM-01, SIM-03, ARCH-01 |
| ANALYSIS:MODEL-05 | Scenario type link | COURSE-01, ARCH-01 |
| ANALYSIS:SEC-01 | Authentication | ACC-01 |
| ANALYSIS:SEC-02 | ผู้อนุมัติมีสิทธิ์เท่านั้น | BOOK-04, COL-01 |
| ANALYSIS:SEC-03 | ขอบเขต College | COL-01 |
| ANALYSIS:SEC-04 | Audit Trail การอนุมัติ | BOOK-04 |
| ANALYSIS:SEC-05 | รักษาประวัติคำขอ | ROOM-01, ROOM-04, EQ-01, BOOK-04 |
| ANALYSIS:SEC-06 | ไม่แก้คำขอหลัง Approved โดยพลการ | BOOK-04, QA-01 |

## I-1 / I-2 verification update — 14 October 2026

I-1 and I-2 are DONE for their accepted functional scope. Fresh evidence: 64 targeted tests / 475 assertions, 15 account Chrome checks and 7 Draft Chrome checks; see docs/I1_I2_VERIFICATION.md and docs/qa/I1_I2_VERIFICATION.json. Evidence uses only isolated SQLite. These results do not replace a full-repository scan, simultaneous MySQL concurrency proof, full screen-reader/browser-state matrix or deployment validation. I-3 through I-5 remain open.
