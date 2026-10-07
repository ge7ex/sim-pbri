<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface EquipmentItem {
    id: number;
    name: string;
    status: 'ready' | 'pending' | 'maintenance';
    quantity_total: number;
    location: string | null;
}

interface RecommendedEquipment extends EquipmentItem {
    pivot: {
        quantity: number;
    };
}

interface ScenarioItem {
    id: number;
    course_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    recommended_resources: RecommendedEquipment[];
    recommended_simulator_types: Array<{ id: number; name: string; is_active: boolean }>;
}

interface CourseItem {
    id: number;
    code: string | null;
    name: string;
    scenarios: ScenarioItem[];
}

interface SharedProps { auth: { user: any; permissions: string[] }; errors: Record<string, string>; flash?: { success?: string } }

const props = defineProps<{
    courses: CourseItem[];
    equipment: EquipmentItem[];
    simulatorTypes: Array<{ id: number; name: string; is_active: boolean }>;
}>();

const page = usePage<SharedProps>();
const canUpdate = computed(() => page.props.auth.permissions.includes('scenario.update'));
const canManage = computed(() =>
    page.props.auth.permissions.includes('scenario.create')
    && page.props.auth.permissions.includes('scenario.update'),
);

const courseForm = useForm({ code: '', name: '' });
const scenarioForm = useForm({
    course_id: null as number | null,
    name: '',
    description: '',
    is_active: true,
});

const templateQuantities = reactive<Record<number, Record<number, number>>>({});
const simulatorSuggestions = reactive<Record<number, number[]>>({});
const savingSuggestions = reactive<Record<number, boolean>>({});

watch(() => props.courses, (courses) => {
    for (const course of courses) {
        for (const scenario of course.scenarios) {
            if (!(scenario.id in simulatorSuggestions)) {
                simulatorSuggestions[scenario.id] = scenario.recommended_simulator_types.map((item) => item.id);
            }
            if (!(scenario.id in templateQuantities)) {
                templateQuantities[scenario.id] = {};
                for (const item of scenario.recommended_resources) {
                    templateQuantities[scenario.id][item.id] = item.pivot.quantity;
                }
            }
        }
    }
}, { immediate: true });

function saveSimulatorSuggestions(scenario: ScenarioItem): void {
    if (savingSuggestions[scenario.id]) return;
    router.put(`/app/scenarios/${scenario.id}/simulator-types`, {
        simulator_type_ids: simulatorSuggestions[scenario.id] ?? [],
    }, {
        preserveScroll: true,
        onStart: () => { savingSuggestions[scenario.id] = true; },
        onFinish: () => { savingSuggestions[scenario.id] = false; },
    });
}

function submitCourse(): void {
    courseForm.post('/app/scenarios/courses', {
        preserveScroll: true,
        onSuccess: () => courseForm.reset(),
    });
}

function submitScenario(): void {
    if (scenarioForm.course_id === null) return;

    scenarioForm.post('/app/scenarios', {
        preserveScroll: true,
        onSuccess: () => scenarioForm.reset(),
    });
}

function toggleScenario(scenario: ScenarioItem): void {
    router.put(`/app/scenarios/${scenario.id}`, {
        course_id: scenario.course_id,
        name: scenario.name,
        description: scenario.description,
        is_active: !scenario.is_active,
    }, { preserveScroll: true });
}

function quantityFor(scenarioId: number, equipmentId: number): number {
    return templateQuantities[scenarioId]?.[equipmentId] ?? 0;
}

function updateQuantity(scenarioId: number, equipmentId: number, event: Event): void {
    const target = event.target as HTMLInputElement;
    const value = Number(target.value);

    if (!templateQuantities[scenarioId]) {
        templateQuantities[scenarioId] = {};
    }

    templateQuantities[scenarioId][equipmentId] =
        Number.isInteger(value) && value > 0 ? value : 0;
}

function saveTemplate(scenario: ScenarioItem): void {
    const equipment = Object.entries(templateQuantities[scenario.id] ?? {})
        .filter(([, quantity]) => quantity > 0)
        .map(([id, quantity]) => ({
            id: Number(id),
            quantity,
        }));

    router.put(`/app/scenarios/${scenario.id}/equipment-template`, {
        equipment,
    }, { preserveScroll: true });
}

function statusLabel(status: EquipmentItem['status']): string {
    if (status === 'ready') return 'พร้อมใช้งาน';
    if (status === 'maintenance') return 'ปิดปรับปรุง';
    return 'รอตรวจสอบ';
}
</script>

<template>
    <Head title="รายวิชาและสถานการณ์จำลอง" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>Scenario Library</p>
            <h1>รายวิชาและสถานการณ์จำลอง</h1>
            <span>รายวิชาและชุดอุปกรณ์เป็นข้อมูลสำหรับจัดกลุ่มและแนะนำเท่านั้น ผู้ใช้ยังปรับรายการจริงได้ตอนส่งคำขอจอง</span>
        </header>
        <p v-if="page.props.flash?.success" class="success" role="status">{{ page.props.flash.success }}</p>
        <div v-if="Object.keys(page.props.errors).length" class="server-errors" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <p class="management-context">คลังรายวิชาและสถานการณ์ · ชุดแนะนำปรับได้ก่อนส่งคำขอจอง</p>
        <div v-if="canManage" class="form-grid">
            <section class="panel">
                <h2>เพิ่มรายวิชา</h2>
                <form class="stack" @submit.prevent="submitCourse">
                    <label>รหัสรายวิชา<input v-model="courseForm.code" maxlength="64" placeholder="เช่น NUR-201"></label>
                    <label>ชื่อรายวิชา<input v-model="courseForm.name" required maxlength="255"></label>
                    <p v-if="courseForm.errors.code" class="error">{{ courseForm.errors.code }}</p>
                    <p v-if="courseForm.errors.name" class="error">{{ courseForm.errors.name }}</p>
                    <button type="submit" :disabled="courseForm.processing">เพิ่มรายวิชา</button>
                </form>
            </section>

            <section class="panel">
                <h2>เพิ่ม Scenario</h2>
                <form class="stack" @submit.prevent="submitScenario">
                    <label>รายวิชาแนะนำ
                        <select v-model="scenarioForm.course_id" required>
                            <option :value="null" disabled>เลือกรายวิชา</option>
                            <option v-for="course in courses" :key="course.id" :value="course.id">
                                {{ course.code ? course.code + ' · ' : '' }}{{ course.name }}
                            </option>
                        </select>
                    </label>
                    <label>ชื่อ Scenario<input v-model="scenarioForm.name" required maxlength="255"></label>
                    <label>รายละเอียด<textarea v-model="scenarioForm.description" rows="3" maxlength="2000"></textarea></label>
                    <label class="checkbox"><input v-model="scenarioForm.is_active" type="checkbox"> เปิดให้แนะนำในการจอง</label>
                    <p v-if="scenarioForm.errors.course_id" class="error">{{ scenarioForm.errors.course_id }}</p>
                    <p v-if="scenarioForm.errors.name" class="error">{{ scenarioForm.errors.name }}</p>
                    <button type="submit" :disabled="scenarioForm.processing || courses.length === 0">เพิ่ม Scenario</button>
                </form>
            </section>
        </div>

        <section class="panel">
            <div v-if="courses.length" class="course-list">
                <article v-for="course in courses" :key="course.id" class="course-card">
                    <header>
                        <div>
                            <small>{{ course.code ?? 'ไม่ระบุรหัส' }}</small>
                            <h2>{{ course.name }}</h2>
                        </div>
                        <span>{{ course.scenarios.length }} Scenario</span>
                    </header>

                    <div v-if="course.scenarios.length" class="scenario-list">
                        <div v-for="scenario in course.scenarios" :key="scenario.id" class="scenario-card">
                            <div class="scenario-summary">
                                <div>
                                    <strong>{{ scenario.name }}</strong>
                                    <p>{{ scenario.description ?? 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
                                </div>
                                <div class="status-actions">
                                    <span :class="scenario.is_active ? 'active' : 'inactive'">
                                        {{ scenario.is_active ? 'พร้อมแนะนำ' : 'ปิดการแนะนำ' }}
                                    </span>
                                    <button v-if="canManage" type="button" @click="toggleScenario(scenario)">
                                        {{ scenario.is_active ? 'ปิด' : 'เปิด' }}
                                    </button>
                                </div>
                            </div>

                            <section class="template simulator-template">
                                <div class="template-heading"><div><h3>ประเภทเครื่องจำลองที่แนะนำ</h3><p>ผู้จองเลือกเครื่องจริงเองได้ การแนะนำไม่บังคับการเลือก</p></div></div>
                                <form v-if="canUpdate" class="suggestions-form" @submit.prevent="saveSimulatorSuggestions(scenario)">
                                    <div v-if="simulatorTypes.length" class="simulator-options"><label v-for="item in simulatorTypes" :key="item.id"><input v-model="simulatorSuggestions[scenario.id]" type="checkbox" :value="item.id" :disabled="savingSuggestions[scenario.id]"><span>{{ item.name }}{{ item.is_active ? '' : ' (ปิดใช้งาน)' }}</span></label></div>
                                    <p v-else class="template-empty">ยังไม่มีประเภทเครื่องจำลองในหน่วยงานนี้</p>
                                    <p class="suggestions-help">นำเครื่องหมายออกทั้งหมดแล้วบันทึก เพื่อล้างรายการแนะนำ</p>
                                    <button type="submit" class="save-template" :disabled="savingSuggestions[scenario.id]">{{ savingSuggestions[scenario.id] ? 'กำลังบันทึก...' : 'บันทึกประเภทแนะนำ' }}</button>
                                </form>
                                <div v-else-if="scenario.recommended_simulator_types.length" class="readonly-equipment"><span v-for="item in scenario.recommended_simulator_types" :key="item.id"><strong>{{ item.name }}</strong><small v-if="!item.is_active">ปิดใช้งาน</small></span></div>
                                <p v-else class="template-empty">ยังไม่ได้กำหนดประเภทเครื่องจำลองที่แนะนำ</p>
                            </section>

                            <div class="template">
                                <div class="template-heading">
                                    <div>
                                        <h3>อุปกรณ์แนะนำ</h3>
                                        <p>เป็นค่าเริ่มต้นสำหรับผู้จอง ไม่ได้ล็อกรายการสุดท้าย</p>
                                    </div>
                                    <button
                                        v-if="canManage && equipment.length"
                                        type="button"
                                        class="save-template"
                                        @click="saveTemplate(scenario)"
                                    >
                                        บันทึกชุดแนะนำ
                                    </button>
                                </div>

                                <div v-if="canManage && equipment.length" class="equipment-grid">
                                    <label v-for="item in equipment" :key="item.id" class="equipment-item">
                                        <span>
                                            <strong>{{ item.name }}</strong>
                                            <small>{{ statusLabel(item.status) }} · มีในระบบ {{ item.quantity_total }} หน่วย</small>
                                        </span>
                                        <input
                                            type="number"
                                            min="0"
                                            max="100000"
                                            :value="quantityFor(scenario.id, item.id)"
                                            aria-label="จำนวนอุปกรณ์แนะนำ"
                                            @input="updateQuantity(scenario.id, item.id, $event)"
                                        >
                                    </label>
                                </div>

                                <div v-else-if="scenario.recommended_resources.length" class="readonly-equipment">
                                    <span v-for="item in scenario.recommended_resources" :key="item.id">
                                        <strong>{{ item.name }}</strong>
                                        <small>{{ item.pivot.quantity }} หน่วย</small>
                                    </span>
                                </div>

                                <p v-else class="template-empty">
                                    {{ equipment.length ? 'ยังไม่ได้กำหนดอุปกรณ์แนะนำ' : 'ยังไม่มีอุปกรณ์ในคลังของหน่วยงาน' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="empty">ยังไม่มี Scenario ในรายวิชานี้</p>
                </article>
            </div>
            <p v-else class="empty">ยังไม่มีรายวิชาในหน่วยงานนี้</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.success,.server-errors{margin:0 0 14px;border:1px solid #b9d8c4;border-radius:10px;padding:12px 16px;background:#edf7f0;color:#2f6f4e}.server-errors{border-color:#e5c1c1;background:#fff;color:#a43b3b}.server-errors p{margin:4px 0}.simulator-options{display:flex;flex-wrap:wrap;gap:12px;margin:14px 0}.simulator-options label{display:flex;align-items:center;gap:6px;color:#40566b;font-size:13px}.simulator-options input{width:16px;height:16px}.suggestions-help{color:var(--sim-muted);font-size:12px;margin:10px 0}.suggestions-form button:disabled{opacity:.55;cursor:not-allowed}
.heading{margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0}.heading span{line-height:1.6}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}.panel h2{margin:0}.stack{display:grid;gap:12px;margin-top:16px}.stack label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.stack .checkbox{display:flex;align-items:center;gap:8px}.stack .checkbox input{width:auto}.stack button,.status-actions button,.save-template{border:0;border-radius:9px;background:var(--sim-navy);color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}.stack button:disabled{opacity:.55;cursor:not-allowed}.error{margin:0;color:#a43b3b;font-size:12px}.course-list{display:grid;gap:14px}.course-card{border:1px solid #e3e9ef;border-radius:13px;padding:16px}.course-card>header{display:flex;align-items:center;justify-content:space-between;gap:16px}.course-card small{color:var(--sim-muted)}.course-card h2{margin:3px 0 0;font-size:18px}.course-card>header>span{border-radius:999px;background:#f3f6f8;color:var(--sim-text);padding:6px 10px;font-size:12px;font-weight:800}.scenario-list{display:grid;gap:12px;margin-top:14px;border-top:1px solid var(--sim-border);padding-top:14px}.scenario-card{border:1px solid #e4eaf0;border-radius:12px;padding:15px}.scenario-summary{display:flex;align-items:center;justify-content:space-between;gap:20px}.scenario-summary>div>strong{color:var(--sim-text)}.scenario-summary p{margin:4px 0 0;color:var(--sim-muted);font-size:13px}.status-actions{display:flex;align-items:center;gap:10px;white-space:nowrap}.status-actions span{font-size:12px;font-weight:800}.status-actions .active{color:#2f6f4e}.status-actions .inactive{color:#8a6262}.status-actions button{padding:7px 10px;background:#fff;color:var(--sim-navy);border:1px solid #cfd8e1}.template{margin-top:14px;border-top:1px solid var(--sim-border);padding-top:14px}.template-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.template-heading h3{margin:0;color:#30475d;font-size:14px}.template-heading p{margin:3px 0 0;color:var(--sim-muted);font-size:12px}.save-template{padding:8px 11px;font-size:12px}.equipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px}.equipment-item{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--sim-border);border-radius:10px;padding:10px}.equipment-item>span{display:grid;gap:2px}.equipment-item strong{color:#34495e;font-size:13px}.equipment-item small{color:var(--sim-muted);font-size:11px}.equipment-item input{width:78px;border:1px solid #cfd8e1;border-radius:8px;padding:8px;text-align:right}.readonly-equipment{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}.readonly-equipment span{display:flex;gap:8px;border-radius:999px;background:#f3f6f8;padding:7px 10px;color:#40566b;font-size:12px}.readonly-equipment small{color:var(--sim-muted)}.template-empty,.empty{margin:0;padding:18px;color:var(--sim-muted);text-align:center}@media(max-width:760px){.form-grid,.equipment-grid{grid-template-columns:1fr}.scenario-summary,.template-heading{align-items:flex-start;flex-direction:column}.course-card>header{align-items:flex-start}.save-template{width:100%}}
.management-context{font-size:13px;color:var(--sim-muted);margin:0 0 16px}</style>
