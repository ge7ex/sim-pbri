# Pre-Phase H — UI Structure and Review Checkpoint

วันที่ตรวจ: 8 ตุลาคม 2569 (Asia/Bangkok)

## 1. Actual starting HEAD

- Repository: `C:/xampp/htdocs/sim-pbri`
- Branch: `backend/inertia-foundation`
- Starting HEAD: `15f722849ba7b5a44ddb1669dd1bd39a6b957cc1`
- Starting working tree: clean.
- Established visual reference: local `sim-pbri-preview` HEAD `2314dd2f711bdccb427a6afcc65764703edd51f3` and existing `.impeccable.md` context.
- Implemented source commit: `b8436771eec04e5eec66fe5932f4f16036e85515`.

## 2. Taste invocation/result

**Unavailable; not invoked.** Not present in the provided skill catalog or the searched installed SKILL.md paths under `.agents/skills`, `.codex/skills`, and the plugin cache. No result is attributed to Taste. The visual criteria in the handoff were reviewed manually under its explicit availability exception.

## 3. UI/UX Promax invocation/result

**Unavailable; not invoked.** The same catalog/filesystem check found no matching installed skill. No result is attributed to UI/UX Promax. The usability checklist supplied in the handoff was applied manually, with source inspection and real Chrome interaction using synthetic accounts.

## 4. Impeccable invocation/result

**Used.** Read `C:/Users/User/.agents/skills/impeccable/SKILL.md`, applied the existing creator-provided `.impeccable.md` context, and reviewed the final screenshots after the usability pass. The current navy/slate direction and Thai font stack were retained. Final composition has distinct page/section/local headings, restrained navigation labels, fewer nested Scenario borders, and less empty stretched form space. No new font, decorative palette, or redesign was introduced. The muted slate token was darkened for readable small text on the existing gray page background.

## 5. Files changed

Source files (16):

- `resources/css/app-shell.css`, `resources/css/components.css`, `resources/css/tokens.css`
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Components/SectionHeader.vue` (new)
- `resources/js/Support/bookingStatus.ts` (new)
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Modules/Booking/Components/BookingStatusBadge.vue`
- `resources/js/Modules/Booking/Pages/Create.vue`, `Index.vue`, `Review.vue`, `Show.vue`, `Calendar.vue`
- `resources/js/Modules/SimResource/Pages/Index.vue`
- `resources/js/Modules/Simulator/Pages/Index.vue`
- `resources/js/Modules/Scenario/Pages/Index.vue`

Evidence files: this report and `docs/qa/PRE_PHASE_H_UI_MATRIX.json`.

## 6. Navigation sections added

| Group | Links, subject to existing permissions |
|---|---|
| ภาพรวม | หน้าหลัก |
| การจอง | ปฏิทินการใช้งาน, ประวัติการจอง, ส่งคำขอจอง, ตรวจสอบคำขอ |
| การจัดการข้อมูล | ทรัพยากร SIM, หุ่นจำลองและทรัพย์สิน, รายวิชาและสถานการณ์ |

Empty groups are removed after permission filtering. Group labels are noninteractive text, subordinate to the actual links. Semantic grouped lists are retained inside the existing nav. Lecturer sees only overview/booking groups; no management or review links. Backend permissions remain authoritative.

## 7. Shared SectionHeader implementation

`SectionHeader.vue` accepts title, optional description/eyebrow/id, and level 2 or 3 (default 2). The actions slot stays alongside its heading, stacking on narrow screens. An id targets the actual heading so existing aria-labelledby references remain useful. Shared `.section`, `.section-header`, `.section-header-copy`, `.section-header-actions`, `.section-eyebrow`, and field grouping classes reuse the existing spacing/color tokens. No value-free PageSection wrapper was added.

## 8. Pages migrated

| Page | Functional sections |
|---|---|
| Dashboard | ภาพรวมคำขอ, รายการคำขอล่าสุด; existing primary booking shortcut remains in PageHeader |
| Booking Create | 1 ห้องและช่วงเวลา, 2 ข้อมูลการใช้งาน, 3 รายวิชาและสถานการณ์ (ถ้ามี), 4 อุปกรณ์, 5 หมายเหตุและตรวจสอบก่อนส่ง |
| Booking History | ค้นหาคำขอ, รายการคำขอจอง |
| Booking Detail | ข้อมูลคำขอและช่วงเวลา, conditional legacy count/amendment sections, เครื่องจำลอง, ห้องและอุปกรณ์, conditional custom equipment, ประวัติสถานะ, permitted actions |
| Review | คำขอที่รอตรวจสอบ, รายละเอียดคำขอ, ทรัพยากรที่ขอใช้, การพิจารณา |
| Calendar | ตารางการใช้ห้องและทรัพยากร, local date headings |
| Resources | เพิ่มทรัพยากร, ทรัพยากรของหน่วยงาน, selected room edit panel with native ข้อมูลห้อง fieldset |
| Simulator | type/asset forms, ประเภทเครื่องจำลอง, ทะเบียนทรัพย์สิน, Admin-only financial detail heading, maintenance form/history |
| Scenario | course/scenario creation, รายวิชาและสถานการณ์, local course/scenario names, recommended simulator/equipment groups |

Existing pages now share PageHeader for page identity. Booking Create retains the preview room card, protected photos, 24-hour controls, recommendations, equipment/custom equipment, availability feedback, and one ordinary form; it is not a wizard.

## 9. Usability findings (manual; Promax unavailable)

| Finding | Observed issue |
|---|---|
| U1 | Flat navigation mixes booking work with management work |
| U2 | Booking form repeats stage numbers and separates participant count from phone/contact |
| U3 | Disabled final submit lacks a nearby explanation of the next required action |
| U4 | Room edit controls live inside a wide horizontally scrolling table on mobile |
| U5 | Asset edit and maintenance controls can open above/below the currently visible list without focus movement |
| U6 | Review shows equipment names without the quantities already provided by the backend |
| U7 | Status history shows internal English state values to Thai users |

## 10. UX-driven fixes

- Group navigation while retaining the exact permission predicates and active-route logic.
- Use five clearly numbered booking sections; group usage count and contact details, preserve optional recommendation semantics.
- Add guidance derived from the existing submit conditions; canSubmit, watchers, payload and server checks are unchanged.
- Move the selected room editor outside the wide table, bring it into view/focus, and restore trigger focus after save/cancel. Keep one selected room state and the existing save action.
- Bring initiated asset edits/maintenance forms into view/focus after the relevant render.
- Show Review equipment quantities using existing pivot.quantity; no query/API change.
- Share the existing Thai status translations between badges and the audit timeline; raw stored status/history is unchanged.
- Clarify the Scenario creation course label while retaining its existing parent-course requirement. Booking preferences remain optional.

## 11. Visual findings (manual; Taste unavailable)

| Finding | Observed issue |
|---|---|
| V1 | Inconsistent page and section heading treatments obscure hierarchy |
| V2 | Scenario panels nest multiple equal-weight borders/cards |
| V3 | Repeated English eyebrow headings and duplicate management context add visual noise |
| V4 | Mobile shell stretches the sidebar/header on short pages, leaving a large navy blank area |
| V5 | Muted text on the gray page background has calculated contrast 4.34:1 |
| V6 | Side-by-side admin forms with different content lengths stretch short panels into empty white space |

## 12. Visual fixes

- Reuse PageHeader/SectionHeader and a restrained h1 → h2 → h3 scale.
- Use spacing and quiet dividers for the Course/Scenario hierarchy instead of nested bordered cards.
- Keep Thai page titles primary; remove redundant management context lines.
- Define mobile shell rows as auto/1fr and keep the sidebar at its content height.
- Change the existing --sim-muted slate value from #64748b to #5d6b7e: 4.95:1 on #f1f5f9 and 5.43:1 on white. Navy sidebar group labels #bfdbfe have calculated contrast 10.64:1 on #0f2742.
- Align management form panels to their content height, without hiding controls or changing the established page composition.

## 13. Accessibility findings/checks

- Matrix checks: exactly one visible main h1, no skipped heading levels, no unlabeled visible input/select/textarea, one active navigation link, no empty navigation group, and no document overflow.
- Keyboard: Enter opens mobile navigation; Escape from a nav link closes it and restores toggle focus. Existing aria-expanded, aria-current, skip link, and focus-visible styles remain.
- Room editor fits the 390px viewport (left 14, right 376), receives focus after render, and restores its trigger after save/cancel.
- Participant/phone errors are associated with their fields. New action areas stack on narrow screens; section actions use at least 44px targets.
- Tables retain focusable internal horizontal-scroll regions. No financial content or unavailable module links were exposed to make navigation easier.
- Contrast calculations concern the stated token combinations; these checks are not a complete formal WCAG certification or a screen-reader audit.

## 14. Responsive and workflow QA

Actual built Vue/Inertia app in local Chrome, synthetic accounts, isolated SQLite/file storage on port 8765. No real-account credentials or runtime MySQL writes were used for browser checks.

| Role | Pages | Widths | Passed cases |
|---|---:|---|---:|
| Admin | all 9 requested pages | 1440, 1024, 768, 390 | 36 |
| Staff | all 9 permitted pages | 1440, 1024, 768, 390 | 36 |
| Lecturer | Dashboard, Create, History, Detail, Calendar | 1440, 1024, 768, 390 | 20 |
| Total | | | **92** |

Final matrix: zero document overflow, zero page errors, heading/label checks passed, exactly one active route. Closed mobile header stays within the QA height bound. See [machine-readable matrix](qa/PRE_PHASE_H_UI_MATRIX.json).

Additional browser results:

- Admin grouped navigation, keyboard menu, room metadata edit/save/cancel focus, photo preservation, asset edit/maintenance focus, recommendations and Review sections/rejection reason gate: passed.
- Staff financial headings hidden and financial fields/maintenance cost/notes absent from initial Inertia payload. No resource edit or maintenance mutation controls: passed.
- Lecturer management/review GETs return 403, management group is absent, own-college protected room photo loads, room change recovery and submit guidance work, Thai audit status copy is visible: passed.
- Booking regression: 24 hour options, 60 minute options, maintenance room disabled, overlap/end-before-start/overcapacity blocked, recommended quantity 2, extra equipment add 2/remove, custom request, successful 21:45–23:05 submission: passed with zero page errors.
- Empty filtered History and Calendar retain section context and usable filters on 390px: passed.

Screenshots and sanitized CLI evidence remain under `C:/Users/User/Documents/ChatGPT/sim pbri/output/playwright/pre-h-*`. Temporary QA database, sessions, credentials, server and credential-bearing snapshots were removed after verification.

## 15. Tests/assertions

`php artisan test --compact`: **125 passed, 953 assertions**, fresh after the final source polish. TestCase checks the effective SQLite :memory: connection before database-refresh traits run. No runtime database reset/seed was performed.

## 16. Build

`npm run build`: passed, **671 modules**. Final bundled JS approximately 309.35 kB (91.71 kB gzip). Assets were generated in the actual XAMPP public/build directory, not in a separate application checkout.

## 17. Composer audit

`composer audit --no-interaction`: no security vulnerability advisories found, exit 0.

## 18. npm audit

`npm audit`: 0 vulnerabilities, exit 0. No dependencies or lockfiles changed.

## 19. Migration and formatting status

`php artisan migrate:status`: migrations through `000014_add_room_images` Ran. No new migration or schema change for this checkpoint.

`php vendor/bin/pint --test --dirty`: passed. `git diff --check`: passed.

## 20. Final Git/scope status

The source commit modifies only authenticated presentation resources. `git diff --name-only -- app database config tests composer.json composer.lock package.json package-lock.json` was empty. Backend authorization, college isolation, privacy/redaction, capacity/availability/locking, preferences, participant/contact rules, approve/reject/recall/cancel and audit persistence were not changed.

Source working tree was clean after `b843677`; this report and matrix are committed separately on the same branch. Final working-tree cleanliness and remote SHA are verified in the final handoff after pushing the evidence commit. No merge into main and no Phase H work.

## 21. Pushed commits / checkpoint disposition

Source: `b8436771eec04e5eec66fe5932f4f16036e85515` — refactor(ui): group navigation and authenticated content sections.

The report/matrix evidence commit follows it on `backend/inertia-foundation`; its final SHA is included in the chat handoff. This checkpoint is complete under the handoff's explicit unavailable-skill exception. Taste and UI/UX Promax remain unavailable and are not represented as invoked. Phase H has not been started.
