<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface ResourceItem {
    id: number;
    name: string;
    kind: 'room' | 'equipment';
    status: string;
    quantity_total: number;
    is_exclusive: boolean;
    location: string | null;
    description: string | null;
    building: string | null;
    floor: string | null;
    capacity: number | null;
}

interface CourseItem { id: number; code: string | null; name: string }
interface SimulatorType { id: number; name: string }
interface SimulatorAsset { id: number; simulator_type_id: number; asset_name: string; asset_code: string | null; status: string; location: string | null }
interface ScenarioItem {
    id: number;
    course_id: number;
    name: string;
    description: string | null;
    course: { id: number; name: string };
    recommended_resources: Array<{
        id: number; name: string; kind: 'room' | 'equipment'; status: string;
        quantity_total: number; is_exclusive: boolean; pivot: { quantity: number };
    }>;
    recommended_simulator_types: SimulatorType[];
}

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role_label: string | null;
            college: { id: number; name: string } | null;
        };
        permissions: string[];
    };
}

const props = defineProps<{ resources: ResourceItem[]; courses: CourseItem[]; scenarios: ScenarioItem[]; simulatorTypes: SimulatorType[]; simulatorAssets: SimulatorAsset[] }>();
const page = usePage<SharedProps>();
const selectedRoomId = ref<number | null>(null);
const roomAvailability = ref<Record<number, boolean>>({});
const checkingRoomAvailability = ref(false);
const roomAvailabilityError = ref('');
const equipmentQuantities = ref<Record<number, number>>({});
const customEquipmentRows = ref<Array<{ name: string; quantity: number; note: string }>>([]);
const selectedSimulatorTypeId = ref<number | null>(null);
const simulatorAvailability = ref<Record<number, boolean>>({});
const checkingSimulatorAvailability = ref(false);
const simulatorAvailabilityError = ref('');
const filteredSimulatorAssets = computed(() => props.simulatorAssets.filter((asset) => asset.simulator_type_id === selectedSimulatorTypeId.value));
const recommendedSimulatorTypeIds = computed(() => new Set(selectedScenario.value?.recommended_simulator_types.map((item) => item.id) ?? []));

const rooms = computed(() => props.resources.filter((item) => item.kind === 'room'));
const selectedRoom = computed(() => rooms.value.find((item) => item.id === selectedRoomId.value) ?? null);
const equipment = computed(() => props.resources.filter((item) => item.kind === 'equipment'));
const selectedScenario = computed(() => props.scenarios.find((item) => item.id === form.scenario_id) ?? null);
const preferredScenarios = computed(() => props.scenarios.filter((item) => item.course_id === form.course_id));
const otherScenarios = computed(() => props.scenarios.filter((item) => item.course_id !== form.course_id));
const recommendedEquipmentIds = computed(() => new Set(
    selectedScenario.value?.recommended_resources
        .filter((item) => item.kind === 'equipment')
        .map((item) => item.id) ?? [],
));
const recommendedEquipment = computed(() => equipment.value.filter((item) => recommendedEquipmentIds.value.has(item.id)));
const additionalEquipment = computed(() => equipment.value.filter((item) => !recommendedEquipmentIds.value.has(item.id)));
const unavailableRecommended = computed(() => (selectedScenario.value?.recommended_resources ?? [])
    .filter((item) => item.kind === 'equipment' && item.status !== 'ready'));

const form = useForm({
    course_id: null as number | null,
    scenario_id: null as number | null,
    simulator_asset_id: null as number | null,
    resources: [] as Array<{ id: number; quantity: number }>,
    custom_equipment: [] as Array<{ name: string; quantity: number; note: string }>,
    starts_at: '',
    ends_at: '',
    participant_count: null as number | null,
    requester_phone: '',
    note: '',
});

watch(selectedSimulatorTypeId, () => { form.simulator_asset_id = null; });
watch(() => [form.starts_at, form.ends_at], async ([startsAt, endsAt], _old, onCleanup) => {
    roomAvailability.value = {};
    roomAvailabilityError.value = '';
    checkingRoomAvailability.value = false;
    const start = new Date(startsAt);
    const end = new Date(endsAt);
    if (!startsAt || !endsAt || !Number.isFinite(start.getTime()) || !Number.isFinite(end.getTime()) || end <= start) return;
    const controller = new AbortController();
    let current = true;
    onCleanup(() => { current = false; controller.abort(); });
    checkingRoomAvailability.value = true;
    try {
        const query = new URLSearchParams({ starts_at: start.toISOString(), ends_at: end.toISOString() });
        const response = await fetch(`/app/resources/availability?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
        if (!response.ok) throw new Error('availability');
        const data = await response.json();
        if (!Array.isArray(data.rooms) || data.rooms.some((room: any) => !Number.isInteger(room.id) || typeof room.available !== 'boolean')) throw new Error('availability');
        if (current) {
            const returnedAvailability = new Map<number, boolean>(data.rooms.map((room: { id: number; available: boolean }) => [room.id, room.available]));
            roomAvailability.value = Object.fromEntries(rooms.value.map((room) => [room.id, returnedAvailability.get(room.id) ?? false]));
        }
    } catch {
        if (current) roomAvailabilityError.value = 'ยังตรวจสอบเวลาว่างของห้องไม่ได้ กรุณาลองระบุวันเวลาอีกครั้ง';
    } finally {
        if (current) checkingRoomAvailability.value = false;
    }
});
watch(() => [form.starts_at, form.ends_at], async ([startsAt, endsAt], _old, onCleanup) => {
    simulatorAvailability.value = {};
    simulatorAvailabilityError.value = '';
    checkingSimulatorAvailability.value = false;
    const start = new Date(startsAt);
    const end = new Date(endsAt);
    if (!startsAt || !endsAt || !Number.isFinite(start.getTime()) || !Number.isFinite(end.getTime()) || end <= start) return;
    const controller = new AbortController();
    let current = true;
    onCleanup(() => { current = false; controller.abort(); });
    checkingSimulatorAvailability.value = true;
    try {
        const query = new URLSearchParams({ starts_at: start.toISOString(), ends_at: end.toISOString() });
        const response = await fetch(`/app/simulators/availability?${query}`, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
        if (!response.ok) throw new Error('availability');
        const data = await response.json();
        if (!Array.isArray(data.assets) || data.assets.some((asset: any) => !Number.isInteger(asset.id) || typeof asset.available !== 'boolean')) throw new Error('availability');
        if (current) {
            const returnedAvailability = new Map<number, boolean>(data.assets.map((asset: { id: number; available: boolean }) => [asset.id, asset.available]));
            simulatorAvailability.value = Object.fromEntries(props.simulatorAssets.map((asset) => [asset.id, returnedAvailability.get(asset.id) ?? false]));
        }
    } catch {
        if (current) simulatorAvailabilityError.value = 'ยังตรวจสอบเวลาว่างของเครื่องจำลองไม่ได้ กรุณาลองระบุวันเวลาอีกครั้ง ระบบจะตรวจสอบซ้ำเมื่อส่งคำขอ';
    } finally {
        if (current) checkingSimulatorAvailability.value = false;
    }
});

watch(() => form.scenario_id, () => {
    equipmentQuantities.value = Object.fromEntries(
        (selectedScenario.value?.recommended_resources ?? [])
            .filter((resource) => resource.kind === 'equipment' && resource.status === 'ready' && equipment.value.some((item) => item.id === resource.id))
            .map((resource) => [resource.id, resource.is_exclusive ? 1 : Math.min(resource.pivot.quantity, resource.quantity_total)]),
    );
});

function handleEquipmentInput(resource: ResourceItem, event: Event): void {
    const target = event.target as HTMLInputElement;
    const quantity = Number(target.value);

    if (!Number.isInteger(quantity) || quantity <= 0) {
        delete equipmentQuantities.value[resource.id];
        return;
    }

    equipmentQuantities.value[resource.id] = Math.min(
        quantity,
        resource.is_exclusive ? 1 : resource.quantity_total,
    );
}

function submit(): void {
    if (selectedRoomId.value === null || !form.starts_at || !form.ends_at || roomAvailability.value[selectedRoomId.value] !== true
        || !selectedRoom.value?.capacity || !Number.isInteger(form.participant_count) || form.participant_count! < 1
        || form.participant_count! > selectedRoom.value.capacity || form.requester_phone.trim().length < 7) {
        return;
    }

    const resources: Array<{ id: number; quantity: number }> = [
        { id: selectedRoomId.value, quantity: 1 },
    ];

    for (const [id, quantity] of Object.entries(equipmentQuantities.value)) {
        resources.push({ id: Number(id), quantity });
    }

    form.resources = resources;
    form.custom_equipment = customEquipmentRows.value.filter((item) => item.name.trim());

    form
        .transform((data) => ({
            ...data,
            starts_at: new Date(data.starts_at).toISOString(),
            ends_at: new Date(data.ends_at).toISOString(),
        }))
        .post('/app/bookings');
}

function addCustomEquipment(): void {
    if (customEquipmentRows.value.length < 50) customEquipmentRows.value.push({ name: '', quantity: 1, note: '' });
}
</script>

<template>
    <Head title="ส่งคำขอจอง" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <div class="page-heading">
            <div>
                <p class="eyebrow">Booking Request</p>
                <h1>ส่งคำขอจอง</h1>
                <p>เลือกทรัพยากรที่พร้อมใช้งาน ระบุช่วงเวลา และส่งให้เจ้าหน้าที่ตรวจสอบ</p>
            </div>
        </div>

        <form class="booking-form" @submit.prevent="submit">
            <section class="panel">
                <h2>1. วันที่และเวลา</h2>
                <p class="section-help">ระบุช่วงเวลาเพื่อให้ระบบตรวจสอบห้องและเครื่องจำลองที่ว่าง</p>
                <div class="field-grid"><label>เริ่มใช้งาน<input v-model="form.starts_at" type="datetime-local" required></label><label>สิ้นสุด<input v-model="form.ends_at" type="datetime-local" required></label></div>
                <p v-if="form.errors.starts_at" class="error">{{ form.errors.starts_at }}</p><p v-if="form.errors.ends_at" class="error">{{ form.errors.ends_at }}</p>
            </section>

            <section class="panel">
                <h2>2. ห้องและจำนวนผู้เข้าใช้งาน</h2>
                <p class="section-help">แสดงห้องในหน่วยงานของคุณ พร้อมอาคาร ชั้น และความจุ ระบบไม่แสดงรายละเอียดการจองของผู้อื่น</p>
                <p v-if="checkingRoomAvailability" class="section-help" role="status">กำลังตรวจสอบเวลาว่างของห้อง...</p>
                <p v-if="roomAvailabilityError" class="error" role="alert">{{ roomAvailabilityError }}</p>
                <div v-if="rooms.length" class="resource-grid">
                    <label v-for="room in rooms" :key="room.id" class="resource-card" :class="{ selected: selectedRoomId === room.id, unavailable: room.status !== 'ready' || room.capacity === null || roomAvailability[room.id] !== true }">
                        <input v-model="selectedRoomId" type="radio" name="room" :value="room.id" :disabled="room.status !== 'ready' || room.capacity === null || roomAvailability[room.id] !== true || checkingRoomAvailability || !!roomAvailabilityError">
                        <strong>{{ room.name }}</strong>
                        <span>{{ [room.building, room.floor ? `ชั้น ${room.floor}` : null].filter(Boolean).join(' / ') || 'ไม่ระบุอาคารและชั้น' }}</span>
                        <span>{{ room.capacity === null ? 'ยังไม่กำหนดความจุ — จองไม่ได้' : `ความจุ ${room.capacity} คน` }}</span>
                        <span>{{ room.location ?? 'ไม่ระบุตำแหน่ง' }}</span>
                        <small>{{ roomAvailabilityError ? 'ตรวจสอบเวลาไม่ได้' : room.status !== 'ready' ? 'ห้องยังไม่พร้อมใช้งาน' : room.capacity === null ? 'รอผู้ดูแลกำหนดความจุ' : roomAvailability[room.id] === true ? 'ว่างในช่วงเวลานี้' : checkingRoomAvailability ? 'กำลังตรวจสอบ...' : form.starts_at && form.ends_at ? 'ไม่ว่างในช่วงเวลานี้' : (room.description ?? 'ระบุวันเวลาเพื่อตรวจสอบ') }}</small>
                    </label>
                </div>
                <p v-else class="empty">ยังไม่มีห้องที่พร้อมให้จอง</p>
                <div class="field-grid participant-field"><label>จำนวนผู้เข้าใช้งาน<input v-model.number="form.participant_count" type="number" min="1" :max="selectedRoom?.capacity ?? 10000" required :disabled="!selectedRoom?.capacity"></label><p v-if="selectedRoom" class="section-help">ห้องนี้รองรับได้สูงสุด {{ selectedRoom.capacity ?? 'ไม่ทราบ' }} คน</p></div>
                <p v-if="form.errors.participant_count" class="error" role="alert">{{ form.errors.participant_count }}</p><p v-if="form.errors.resources" class="error" role="alert">{{ form.errors.resources }}</p>
            </section>

            <section class="panel">
                <h2>3. เครื่องจำลอง (ถ้ามี)</h2>
                <p class="section-help">เลือกประเภท แล้วเลือกเครื่องที่ต้องการใช้ รายการแนะนำจากสถานการณ์จำลองไม่บังคับการเลือก</p>
                <p v-if="selectedScenario?.recommended_simulator_types.length" class="section-help">ประเภทที่แนะนำ: {{ selectedScenario.recommended_simulator_types.map((item) => item.name).join(', ') }}</p>
                <div class="field-grid">
                    <label>ประเภทเครื่องจำลอง<select v-model="selectedSimulatorTypeId"><option :value="null">ไม่เลือกเครื่องจำลอง</option><option v-for="item in simulatorTypes" :key="item.id" :value="item.id">{{ item.name }}{{ recommendedSimulatorTypeIds.has(item.id) ? ' (แนะนำ)' : '' }}</option></select></label>
                    <label>เครื่องจำลอง<select v-model="form.simulator_asset_id" :disabled="selectedSimulatorTypeId === null"><option :value="null">ไม่เลือกเครื่องจำลอง</option><option v-for="asset in filteredSimulatorAssets" :key="asset.id" :value="asset.id" :disabled="asset.status !== 'active' || simulatorAvailability[asset.id] === false">{{ asset.asset_name }}{{ asset.asset_code ? ' · ' + asset.asset_code : '' }}{{ simulatorAvailability[asset.id] === false ? ' (ไม่ว่าง)' : '' }}</option></select></label>
                </div>
                <p v-if="selectedSimulatorTypeId !== null && !filteredSimulatorAssets.length" class="section-help">ยังไม่มีเครื่องจำลองที่พร้อมใช้งานในประเภทนี้</p>
                <p v-if="checkingSimulatorAvailability" class="section-help" role="status">กำลังตรวจสอบเวลาว่างของเครื่องจำลอง...</p>
                <p v-if="simulatorAvailabilityError" class="error" role="alert">{{ simulatorAvailabilityError }}</p>
                <p v-if="form.simulator_asset_id !== null && simulatorAvailability[form.simulator_asset_id] === false" class="error" role="alert">เครื่องจำลองที่เลือกไม่ว่างในช่วงเวลานี้ กรุณาเลือกเครื่องอื่นหรือปรับวันเวลา</p>
                <p v-if="form.simulator_asset_id !== null && simulatorAvailability[form.simulator_asset_id] === true" class="section-help" role="status">เครื่องจำลองที่เลือกว่างในช่วงเวลานี้ ระบบจะตรวจสอบซ้ำเมื่อส่งคำขอ</p>
                <p v-if="form.errors.simulator_asset_id" class="error" role="alert">{{ form.errors.simulator_asset_id }}</p>
            </section>

            <section class="panel">
                <h2>4. รายวิชาและสถานการณ์จำลอง</h2>
                <p class="section-help">สถานการณ์ที่สัมพันธ์กับรายวิชาจะแสดงเป็นรายการแนะนำ คุณยังเลือกสถานการณ์อื่นหรือไม่เลือกก็ได้</p>
                <div class="field-grid">
                    <label>รายวิชา<select v-model="form.course_id"><option :value="null">ไม่ระบุรายวิชา</option><option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code ? course.code + ' · ' : '' }}{{ course.name }}</option></select></label>
                    <label>สถานการณ์จำลอง<select v-model="form.scenario_id"><option :value="null">ไม่เลือกสถานการณ์</option><optgroup v-if="preferredScenarios.length" label="สถานการณ์จำลองที่แนะนำ"><option v-for="scenario in preferredScenarios" :key="scenario.id" :value="scenario.id">{{ scenario.name }}</option></optgroup><optgroup v-if="otherScenarios.length" label="สถานการณ์จำลองอื่น"><option v-for="scenario in otherScenarios" :key="scenario.id" :value="scenario.id">{{ scenario.name }} · {{ scenario.course.name }}</option></optgroup></select></label>
                </div>
                <p v-if="form.errors.course_id" class="error">{{ form.errors.course_id }}</p><p v-if="form.errors.scenario_id" class="error">{{ form.errors.scenario_id }}</p>
            </section>

            <section class="panel">
                <h2>5. อุปกรณ์จากแค็ตตาล็อก</h2>
                <p class="section-help">รายการแนะนำเป็นค่าเริ่มต้น คุณนำออกหรือเปลี่ยนจำนวนได้ อุปกรณ์เพิ่มเติมเลือกได้ตามต้องการ</p>
                <div v-if="recommendedEquipment.length" class="equipment-group">
                    <h3>อุปกรณ์จากชุดแนะนำ</h3>
                    <div class="equipment-list">
                        <label v-for="item in recommendedEquipment" :key="item.id">
                            <span><strong>{{ item.name }}</strong><small>พร้อมให้ใช้สูงสุด {{ item.quantity_total }} หน่วย · แนะนำจาก {{ selectedScenario?.name }}</small></span>
                            <input :aria-label="'จำนวน ' + item.name" type="number" min="0" :max="item.is_exclusive ? 1 : item.quantity_total" placeholder="0" :value="equipmentQuantities[item.id] ?? ''" @input="handleEquipmentInput(item, $event)">
                        </label>
                    </div>
                </div>
                <p v-if="unavailableRecommended.length" class="section-help">อุปกรณ์แนะนำที่ยังไม่พร้อมให้จอง: {{ unavailableRecommended.map((item) => item.name).join(', ') }}</p>
                <div v-if="additionalEquipment.length" class="equipment-group">
                    <h3>{{ selectedScenario ? 'อุปกรณ์เพิ่มเติมจากแค็ตตาล็อก' : 'เลือกอุปกรณ์จากแค็ตตาล็อก' }}</h3>
                    <div class="equipment-list">
                        <label v-for="item in additionalEquipment" :key="item.id">
                            <span><strong>{{ item.name }}</strong><small>พร้อมให้ใช้สูงสุด {{ item.quantity_total }} หน่วย</small></span>
                            <input :aria-label="'จำนวน ' + item.name" type="number" min="0" :max="item.is_exclusive ? 1 : item.quantity_total" placeholder="0" :value="equipmentQuantities[item.id] ?? ''" @input="handleEquipmentInput(item, $event)">
                        </label>
                    </div>
                </div>
                <p v-if="!equipment.length" class="empty">ไม่มีอุปกรณ์ที่พร้อมให้จองในแค็ตตาล็อก</p>
            </section>

            <section class="panel">
                <h2>6. คำขออุปกรณ์เพิ่มเติม</h2>
                <p class="section-help">ใช้สำหรับอุปกรณ์ที่ไม่มีในแค็ตตาล็อก เจ้าหน้าที่จะตรวจสอบคำขอนี้แยกจากจำนวนคงเหลือ</p>
                <div v-for="(item, index) in customEquipmentRows" :key="index" class="custom-row">
                    <label>ชื่ออุปกรณ์<input v-model="item.name" :id="'custom-name-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.name'] ? 'custom-name-error-' + index : undefined" maxlength="255" required></label>
                    <label>จำนวน<input v-model.number="item.quantity" :id="'custom-quantity-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.quantity'] ? 'custom-quantity-error-' + index : undefined" type="number" min="1" max="10000" required></label>
                    <label>รายละเอียด<textarea v-model="item.note" :id="'custom-note-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.note'] ? 'custom-note-error-' + index : undefined" maxlength="2000" rows="2"></textarea></label>
                    <button type="button" :aria-label="'นำคำขออุปกรณ์ ' + (index + 1) + ' ออก'" @click="customEquipmentRows.splice(index, 1)">นำรายการออก</button>
                    <p v-if="form.errors['custom_equipment.' + index + '.name']" :id="'custom-name-error-' + index" class="error" role="alert">{{ form.errors['custom_equipment.' + index + '.name'] }}</p>
                    <p v-if="form.errors['custom_equipment.' + index + '.quantity']" :id="'custom-quantity-error-' + index" class="error" role="alert">{{ form.errors['custom_equipment.' + index + '.quantity'] }}</p>
                    <p v-if="form.errors['custom_equipment.' + index + '.note']" :id="'custom-note-error-' + index" class="error" role="alert">{{ form.errors['custom_equipment.' + index + '.note'] }}</p>
                </div>
                <p v-if="form.errors.custom_equipment" class="error" role="alert">{{ form.errors.custom_equipment }}</p>
                <button type="button" class="secondary" :disabled="customEquipmentRows.length >= 50" @click="addCustomEquipment">เพิ่มคำขออุปกรณ์</button>
            </section>

            <section class="panel">
                <h2>7. ข้อมูลผู้จอง</h2>
                <div class="field-grid">
                    <label>วิทยาลัย / หน่วยงาน<input :value="page.props.auth.user.college?.name ?? ''" readonly></label>
                    <label>ชื่อผู้จอง<input :value="page.props.auth.user.name" readonly></label>
                    <label>เบอร์ติดต่อ<input v-model="form.requester_phone" type="tel" inputmode="tel" minlength="7" maxlength="32" autocomplete="tel" required><small v-if="form.errors.requester_phone" class="error">{{ form.errors.requester_phone }}</small></label>
                </div>
                <label class="full-field">หมายเหตุ<textarea v-model="form.note" rows="4" maxlength="2000"></textarea></label>
                <p class="section-help">ระบบบันทึกผู้ส่งคำขอตามบัญชีที่เข้าสู่ระบบ การจองแทนบุคคลอื่นยังไม่เปิดใช้งานจนกว่าจะมีสิทธิ์เฉพาะรองรับ</p>
            </section>

            <div class="form-actions">
                <button type="submit" :disabled="form.processing || selectedRoomId === null || roomAvailability[selectedRoomId] !== true || !selectedRoom?.capacity || !form.participant_count || form.participant_count > (selectedRoom?.capacity ?? 0) || (form.simulator_asset_id !== null && simulatorAvailability[form.simulator_asset_id] === false)">
                    {{ form.processing ? 'กำลังส่ง...' : 'ยืนยันส่งคำขอ' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>

<style scoped>
.page-heading{display:flex;justify-content:space-between;gap:20px;margin-bottom:24px}.eyebrow{margin:0 0 6px;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}h1{margin:0;color:#16324f;font-size:34px}.page-heading p{color:#66788a}.booking-form{display:grid;gap:14px}.panel{border:1px solid #dfe6ee;border-radius:14px;background:#fff;padding:20px}.panel h2{margin:0;color:#17324f;font-size:18px}.panel h3{margin:10px 0 0;color:#315b7c;font-size:14px}.section-help,.empty{color:#718096;line-height:1.55}.resource-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:14px}.resource-card{display:grid;gap:6px;border:1px solid #d9e1e8;border-radius:12px;padding:13px;cursor:pointer}.resource-card.selected{border-color:#315b7c;background:#f4f7fa}.resource-card.unavailable{opacity:.7;cursor:not-allowed}.resource-card input{width:auto}.resource-card input[type=radio]{min-height:auto;padding:0}.resource-card span,.resource-card small{color:#718096}.field-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:14px}.participant-field{align-items:end}label{display:grid;gap:6px;color:#44576a;font-size:13px;font-weight:800}input,textarea,select{width:100%;min-height:44px;border:1px solid #cdd7e0;border-radius:9px;background:#fff;padding:10px 11px;color:#172033;font:inherit}input[readonly]{background:#f4f6f8;color:#627386}.equipment-group{margin-top:14px}.equipment-list{display:grid;gap:8px;margin-top:6px}.equipment-list label{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #edf1f4;padding:9px 0}.equipment-list span{display:grid;gap:3px}.equipment-list small{color:#718096;font-weight:500}.equipment-list input{width:110px}.custom-row{display:grid;grid-template-columns:2fr 1fr 2fr auto;gap:10px;align-items:end;margin:14px 0;padding-bottom:12px;border-bottom:1px solid #edf1f4}.custom-row button,.secondary{min-height:44px;border:1px solid #cdd7e0;border-radius:9px;background:#fff;color:#315b7c;padding:9px 11px;font-weight:700;cursor:pointer}.custom-row .error{grid-column:1/-1}.custom-row button:hover,.secondary:hover:not(:disabled){background:#f4f7fa}.error{margin:5px 0;color:#a43b3b;font-size:13px}.form-actions{display:flex;justify-content:flex-end}.form-actions button{min-height:44px;border:0;border-radius:9px;background:#17324f;color:#fff;padding:11px 18px;font-weight:800;cursor:pointer}.form-actions button:disabled,.secondary:disabled{opacity:.55;cursor:not-allowed} :focus-visible{outline:3px solid #557d9d;outline-offset:2px}@media(max-width:720px){.panel{padding:16px}.field-grid,.custom-row{grid-template-columns:1fr}.equipment-list label{align-items:flex-start}.resource-grid{grid-template-columns:1fr}.custom-row button{width:fit-content}}
</style>
