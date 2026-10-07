<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface ScenarioItem {
    id: number;
    course_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
}

interface CourseItem {
    id: number;
    code: string | null;
    name: string;
    scenarios: ScenarioItem[];
}

interface SharedProps { auth: { user: any; permissions: string[] } }

const props = defineProps<{ courses: CourseItem[] }>();
const page = usePage<SharedProps>();
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
</script>

<template>
    <Head title="รายวิชาและสถานการณ์จำลอง" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>Scenario Library</p>
            <h1>รายวิชาและสถานการณ์จำลอง</h1>
            <span>ความสัมพันธ์รายวิชา → Scenario ใช้เพื่อจัดกลุ่มและแนะนำเท่านั้น ไม่ได้ล็อกการเลือกตอนจอง</span>
        </header>

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
                        <div v-for="scenario in course.scenarios" :key="scenario.id" class="scenario-row">
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
                    </div>
                    <p v-else class="empty">ยังไม่มี Scenario ในรายวิชานี้</p>
                </article>
            </div>
            <p v-else class="empty">ยังไม่มีรายวิชาในหน่วยงานนี้</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{margin-bottom:20px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.heading h1{margin:5px 0;color:#17324f;font-size:34px}.heading span{color:#718096;line-height:1.6}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}.panel{border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:20px}.panel h2{margin:0;color:#17324f}.stack{display:grid;gap:12px;margin-top:16px}.stack label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.stack input,.stack select,.stack textarea{border:1px solid #cfd8e1;border-radius:9px;background:#fff;padding:10px;font:inherit}.stack .checkbox{display:flex;align-items:center;gap:8px}.stack .checkbox input{width:auto}.stack button,.status-actions button{border:0;border-radius:9px;background:#17324f;color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}.stack button:disabled{opacity:.55;cursor:not-allowed}.error{margin:0;color:#a43b3b;font-size:12px}.course-list{display:grid;gap:14px}.course-card{border:1px solid #e3e9ef;border-radius:13px;padding:16px}.course-card>header{display:flex;align-items:center;justify-content:space-between;gap:16px}.course-card small{color:#718096}.course-card h2{margin:3px 0 0;font-size:18px}.course-card>header>span{border-radius:999px;background:#f3f6f8;color:#526578;padding:6px 10px;font-size:12px;font-weight:800}.scenario-list{margin-top:14px;border-top:1px solid #edf1f4}.scenario-row{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:14px 0;border-bottom:1px solid #edf1f4}.scenario-row:last-child{border-bottom:0}.scenario-row strong{color:#263849}.scenario-row p{margin:4px 0 0;color:#718096;font-size:13px}.status-actions{display:flex;align-items:center;gap:10px;white-space:nowrap}.status-actions span{font-size:12px;font-weight:800}.status-actions .active{color:#2f6f4e}.status-actions .inactive{color:#8a6262}.status-actions button{padding:7px 10px;background:#fff;color:#17324f;border:1px solid #cfd8e1}.empty{margin:0;padding:18px;color:#718096;text-align:center}@media(max-width:760px){.form-grid{grid-template-columns:1fr}.scenario-row{align-items:flex-start;flex-direction:column}.course-card>header{align-items:flex-start}}
</style>
