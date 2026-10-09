# Pilot Readiness / UAT — 9 ตุลาคม 2569

## สรุปสถานะ

**Technical UAT ในฐานข้อมูลแยก: ผ่าน หลังแก้ปัญหาที่ทำซ้ำได้ 2 กลุ่ม**

**Pilot ที่ใช้ข้อมูลจริง: ยังไม่พร้อมเริ่ม** ผู้ใช้ยืนยันว่ายังไม่ได้สำรวจพื้นที่และยังไม่มี Master Data จริง ต้องการโครงสร้างพร้อมเพิ่มผ่านหน้าเว็บก่อน ระบบมีแบบฟอร์มดังกล่าวแล้วและตรวจเพิ่มข้อมูลผ่านเว็บครบตามรายการด้านล่าง ไม่สร้างข้อมูลวิทยาลัย/ทรัพยากร/การจองจริงขึ้นเอง และยังไม่เริ่ม Phase I

Repository `C:/xampp/htdocs/sim-pbri`, branch `backend/inertia-foundation`. No main merge, deployment, new roles, schema changes, dependencies or Export features.

## HEAD และผลตรวจใหม่

- `git fetch origin` และ `git pull --ff-only`: Already up to date; starting HEAD `ff5b59bc2e4476cdb09fbd499d46fd206574ff37`, working tree clean.
- อ่าน Phase H Markdown/JSON, routes, policies, requests, actions, database safety guard และ project design context.
- Fresh baseline รอบนี้: **156 tests / 1,408 assertions**, build **680 modules**, Pint PASS, Composer audit CLEAN, npm audit **0 vulnerabilities**, runtime migrations all Ran through 000014.
- Fresh final หลังแก้: **157 tests / 1,414 assertions**, build **680 modules**, Pint PASS, Composer audit CLEAN, npm audit **0 vulnerabilities**. `git diff --check` PASS.
- Fix commits: `a1dc098` nullable relations; `9c3e3f1` last-used timezone. Source HEAD `9c3e3f17c4335d98591bcad2a96648e590657db7`. Evidence commit follows; exact final HEAD/remote equality and clean tree are checked after push and reported in chat.

## MySQL จริง — ตรวจ read-only

Inventory script guards local/mysql/sim_pbri and uses a read-only transaction, rolled back after SELECTs. ไม่แสดง .env, password, key, hash, phone or user names.

| รายการ | จำนวนก่อน/หลัง UAT |
| --- | ---: |
| วิทยาลัย | 1 (ID 9) |
| ผู้ใช้ | 3: Admin/Staff/Lecturer อย่างละ 1 |
| ห้อง | 1 |
| อุปกรณ์ | 0 |
| Booking | 0 |
| รายวิชา / Scenario | 0 / 0 |
| ประเภทหุ่น / ทรัพย์สินหุ่น | 0 / 0 |
| ชุดแนะนำอุปกรณ์ / ประเภทหุ่น | 0 / 0 |
| ประวัติเปลี่ยนรูปห้อง | 2 |

ห้อง ID 10: `maintenance`, capacity 80, quantity 1, exclusive true; มีอาคาร/ชั้น ไม่มีผู้รับผิดชอบหรือรูปปัจจุบัน จำนวน/สถานะ/ตัวชี้วัดความพร้อมที่อ่านก่อนและหลังตรงกันทั้งหมด ไม่คืนรูปหรือเปลี่ยนสถานะ เพราะ provenance ยังไม่ได้รับการยืนยัน ไม่มีห้อง ready ให้เริ่มจองจริงในรอบนี้

Effective app timezone is UTC; datetime labels use browser local timezone. This round fixes the ranking instant display only. Existing date-filter boundaries use app timezone and were preserved; owner should confirm the operational reporting-day timezone before a real pilot. No timezone setting changed.

## Role-based UAT coverage

All synthetic bookings/master data were in isolated SQLite on `127.0.0.1:8765`. Normal login, form, CSRF, route middleware, policies and action checks used. No runtime MySQL insert/update/delete or runtime login required.

| UAT | ผลและหลักฐาน |
| --- | --- |
| Lecturer ส่งคำขอ | สร้าง 3 คำขอผ่านเว็บ วัน 20/21/22 ต.ค. 14:00–16:00 จำนวน 12 คน เลือกห้อง/รายวิชา/Scenario/หุ่นจริงใน QA และชุดอุปกรณ์แนะนำ 2 ชิ้น; detail แสดงเวลา 24h |
| Staff approve | อนุมัติคำขอวันที่ 20 ผ่าน confirm ปกติ |
| Staff reject | ปุ่มปฏิเสธ disabled หากไม่มีเหตุผล; ใส่เหตุผลแล้วบันทึกสำเร็จ |
| Recall | เรียกกลับทั้ง approved/rejected เป็น pending; อนุมัติ/ปฏิเสธใหม่สำเร็จ ประวัติ transition ถูกเก็บ |
| History/calendar | Own-College labels/history/time visible, no FOREIGN PRIVATE identity/note; foreign event remains anonymous busy by existing rule |
| Pending + approved conflicts | คำขอ pending และ approved ทำให้ช่วง 14–16 ไม่ว่าง; adjacent 16–18 ว่าง ปุ่มส่งตรงกับ eligibility |
| Cancel | Lecturer ยกเลิกคำขอ pending ของตนผ่าน confirm; status cancelled และประวัติ 2 transitions ยังคงอยู่ |
| Approved-only statistics | ในวันที่ 20–22: 3 คำขอ, 1 approved, room 2h, equipment quantity 2, participants 12. Recall approved → pending ทำให้ approved และ approved-usage เป็นศูนย์; reapprove คืนยอดเดิม |
| Report filters | ใช้ room/asset/course/scenario พร้อมกัน, status pending ไม่รวม usage, empty range และ invalid date errors ผ่าน |
| Financial privacy | Staff payload ไม่มี assetStatistics; simulator purchase/depreciation/maintenance cost/note absent แม้ Admin มีข้อมูลซื้อและบำรุงรักษาใน QA. Staff ไม่มีปุ่มแก้ห้อง/บำรุงรักษา |
| Lecturer authorization | ไม่มีเมนู Reports; /app/reports 403 ทุก 4 widths; Resource/Simulator/Scenario/Review 403 |
| Admin rooms/equipment | เพิ่มห้อง กรอกความจุ แก้ไข และเพิ่ม equipment ผ่านเว็บ; cancel delete คง record; delete unused สำเร็จ; ห้องมี booking/image history ถูกปฏิเสธการลบ |
| Room image/status | Upload synthetic PNG ผ่านเว็บได้ protected inline image; disabled และ maintenance แยกป้าย/กันเลือก; ready เปิดกลับเลือกได้ รูปและประวัติยังอยู่ |
| Admin simulator | เพิ่ม type/asset, แก้สถานที่, บันทึก maintenance ผ่านเว็บ; ซื้อ 15,000 และ cost 125 ใน QA. Financial snapshot ยอดซื้อรวม 115,000 ค่า maintenance 125 ตรง fixture |
| Admin Course/Scenario/templates | เพิ่ม course/scenario ผ่านเว็บ บันทึก equipment quantity 2 และ simulator type recommendation; reload ยืนยัน persisted relation |
| Responsive | 9 Admin + 9 Staff + 5 Lecturer routes × 1440/1024/768/390 = **92 checks**. Reports เพิ่ม 8 render cases และ Lecturer report denial 4 cases. No document overflow/page errors in final normal-flow matrix |
| Accessibility | One h1, heading sequence, labelled inputs, active nav, native details/keyboard filters, chart title/description + text table; reduced-motion 0s; screenshot review desktop/mobile |
| No Export | rg in all app/routes/resources finds only TypeScript export syntax; registered route URI/name has no export/download/CSV/Excel/PDF. All roles have no user-facing controls/hints; Reports browser checks confirm. Existing protected inline room photo is preserved. |

## Reproduced issues and minimal fixes

### UAT-01 — null relations crashed booking views

Defensive College scoping can return a null transition/amendment actor or simulator type. Show/Review accessed `.name` directly. Four browser response-fixture cases reproduced TypeError and h1=0 before changes. Corrected nullable TypeScript types, optional access and Thai fallback; kept all backend College filters intact.

After fix all 4 cases have zero page errors, h1=1 and visible fallback. Existing BookingRelationPrivacyTest passes (78 assertions), proving foreign names remain absent. New `tests/Browser/BookingNullableRelations.js` is the browser regression script, separately executed via Playwright CLI; it is not included in the PHP test count. It requires isolated QA port 8765, Staff login, pending booking #1 with asset and audit fixture; see tests/Browser/README.md. Simulator-type null case is a response fixture consistent with defensive projection, not a claim that normal composite-FK writes permit this corruption. No runtime constraints disabled.

### UAT-02 — last-used ranking displayed UTC as local text

The same QA booking displayed 14:00 in Show but 07:00 in both usage rankings. Raw aggregate SQL timestamps were sliced into text without timezone. Query now serializes these <=20 ranking instants to ISO with app timezone; ranking reuses the existing booking datetime formatter.

New feature test failed before fix (raw SQL timestamp vs expected ISO). After fix 8 report tests / 134 assertions pass, including UTC and Asia/Bangkok cases and unchanged 2h totals. Browser same-booking comparison now shows 14:00 in Show, rooms and simulator, not 07:00. No storage/filter/availability/date semantics changed.

Harness-only failures (option labels, redirect target, status text and full-page response interception) were corrected in the QA scripts, not the product. CLI confirm dialogs can yield early; completion was verified from saved results, fresh UI/props and history before counting a pass. Inventory helper initially queried an absent is_active field; corrected helper after schema read, transaction remained read-only. None is reported as an application bug.

## Ponytail review

**What this change does:** Keeps booking pages readable when scoped relations are missing, and displays report usage instants consistently with booking pages. No workflow/permission changes.

Two confirmed findings above were fixed. Re-read callers, nullable server contracts, shared formatter, query, role/financial boundaries and regression evidence; final scoped diff is 6 source/test files. No new helpers, architecture, dependencies or schema needed.

**Verdict: Ship the scoped fixes.** Real pilot remains conditional on surveyed data/owner acceptance.

**Not checked:** institutional acceptance with real users/data, deployment, production-volume load, restore drill or an exhaustive Codex Security scan. Automated UAT is technical evidence, not a formal certification or owner sign-off.

## UI/UX skills

- Actual installed Ponytail + Ponytail Review read/applied.
- Playwright skill read/applied for fresh local-browser UAT; selector failures did not justify product changes.
- UI/UX Pro Max read; targeted local `error recovery missing data --domain ux` search supports understandable recovery/fallback text. Unrelated bulk-action recommendation was not applied.
- Impeccable read with existing .impeccable.md context; retained institutional navy/slate/Thai direction and reviewed final desktop/mobile screenshots. Passing layout retained.
- Taste remains installed; primary workflow excludes data dashboards/tables. No Taste redesign was invoked or claimed in this UAT, which required only correctness fixes.

## Master Data readiness and next priorities

1. **Survey data first.** [MASTER_DATA_INTAKE_CHECKLIST.md](MASTER_DATA_INTAKE_CHECKLIST.md) maps real required/optional fields to existing Admin pages. Blank intake format has no invented records. Web entry structure tested and ready for owner-supplied data.
2. Confirm existing room ID 10 readiness/provenance, capacity and responsible staff. Status change/image restoration require explicit owner decision; no automatic reopening.
3. Plan named pilot user identities and College assignments. Current three accounts are documented local development accounts, not evidence of approved institutional pilot identities. No new provisioning feature added.
4. Owner confirms reporting-day timezone and performs acceptance with real rooms/resources, alongside backup/restore and deployment planning appropriate to the chosen pilot environment. App UTC/day boundaries remain unchanged.
5. UI evidence gap for later agreed scope: Course/Scenario rename/edit routes exist, but current management UI exposes creation and Scenario activation/template changes rather than full metadata edit controls. This does not block adding data through the web; it is a remaining product-work candidate, not implemented Phase I.

No Phase I feature scope agreed; stop after this report. Do not import until real records and owner authorization are provided.

## Cleanup and git

QA browser closed; guarded PID/port8765 process stopped; isolated SQLite, fixture image, private image storage, sessions/logs/settings/app key/password removed. Password-containing auto-snapshots checked locally without printing values. Runtime server8000 untouched. Sanitized synthetic evidence/screenshots retained locally; structured evidence is committed in docs/qa.

Only scoped fixes and reports/checklist are committed/pushed to `origin/backend/inertia-foundation` after diff review. No main merge. Exact final clean-tree/remote SHA verification follows the documentation commit and is in the final chat response.

Evidence: [PILOT_UAT_VERIFICATION.json](qa/PILOT_UAT_VERIFICATION.json).
