<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import Time24Field from '../../../Components/Time24Field.vue';

interface ResourceItem {
    image_url: string | null;
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

const rooms = computed(() => props.resources.filter((item) => item.kind === 'room')
    .sort((a, b) => Number(b.status === 'ready' && b.capacity !== null) - Number(a.status === 'ready' && a.capacity !== null)));
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

const dates = reactive({ startDate: '', startTime: '', endDate: '', endTime: '' });
const activeRoomIndex = ref(0);
const roomPickerOpen = ref(true);
const visibleRoom = computed(() => rooms.value[activeRoomIndex.value] ?? rooms.value[0] ?? null);
const additionalEquipmentId = ref<number | null>(null);
const additionalEquipmentQuantity = ref(1);
const selectedAdditionalEquipment = computed(() => additionalEquipment.value.filter(item => (equipmentQuantities.value[item.id] ?? 0) > 0));
const dateTimeValid = computed(() => !!form.starts_at && !!form.ends_at && new Date(form.ends_at) > new Date(form.starts_at));
const canSubmit = computed(() => !form.processing && dateTimeValid.value && selectedRoomId.value !== null
    && roomAvailability.value[selectedRoomId.value] === true && !!selectedRoom.value?.capacity
    && Number.isInteger(form.participant_count) && form.participant_count! > 0
    && form.participant_count! <= selectedRoom.value.capacity && form.requester_phone.trim().length >= 7
    && !(form.simulator_asset_id !== null && simulatorAvailability.value[form.simulator_asset_id] === false));
const submissionHelp = computed(() => {
    if (!selectedRoom.value) return 'เลือกห้องที่พร้อมใช้งานในหัวข้อ 1';
    if (!dateTimeValid.value) return 'ระบุวันเวลาเริ่มและสิ้นสุด โดยเวลาสิ้นสุดต้องอยู่หลังเวลาเริ่ม';
    if (roomAvailability.value[selectedRoom.value.id] !== true) return checkingRoomAvailability.value ? 'กำลังตรวจเวลาว่างของห้อง' : 'เลือกห้องหรือช่วงเวลาใหม่ให้ห้องว่างก่อนส่งคำขอ';
    if (!Number.isInteger(form.participant_count) || form.participant_count! < 1 || form.participant_count! > selectedRoom.value.capacity!) return 'ระบุจำนวนผู้เข้าใช้งานไม่เกินความจุห้องในหัวข้อ 2';
    if (form.requester_phone.trim().length < 7) return 'ระบุเบอร์ติดต่อในหัวข้อ 2';
    if (form.simulator_asset_id !== null && simulatorAvailability.value[form.simulator_asset_id] === false) return 'เปลี่ยนเครื่องจำลองหรือช่วงเวลาในหัวข้อ 3';
    return '';
});
watch(() => [dates.startDate, dates.startTime, dates.endDate, dates.endTime], () => {
    form.starts_at = dates.startDate && dates.startTime ? `${dates.startDate}T${dates.startTime}:00` : '';
    form.ends_at = dates.endDate && dates.endTime ? `${dates.endDate}T${dates.endTime}:00` : '';
});
function roomSelectable(room: ResourceItem): boolean { return room.status === 'ready' && room.capacity !== null; }
function chooseRoom(room: ResourceItem): void {
    if (!roomSelectable(room)) return;
    selectedRoomId.value = room.id;
    roomPickerOpen.value = false;
}
function changeRoom(): void { roomPickerOpen.value = true; }
function nextRoom(direction: number): void { activeRoomIndex.value = (activeRoomIndex.value + direction + rooms.value.length) % rooms.value.length; }
function roomStatusLabel(room: ResourceItem): string {
    if (room.status !== 'ready') return room.status === 'maintenance' ? 'ปิดปรับปรุง' : 'รอตรวจสอบ';
    if (room.capacity === null) return 'ยังไม่กำหนดความจุ';
    return 'พร้อมใช้งาน';
}
function availabilityLabel(room: ResourceItem): string {
    if (!roomSelectable(room)) return 'ยังไม่พร้อมให้จอง';
    if (checkingRoomAvailability.value) return 'กำลังตรวจสอบเวลาว่าง';
    if (roomAvailabilityError.value) return 'ยังตรวจสอบเวลาว่างไม่ได้';
    if (!dateTimeValid.value) return 'เลือกห้อง แล้วระบุวันเวลาเพื่อตรวจสอบเวลาว่าง';
    return roomAvailability.value[room.id] === true ? 'ว่างในช่วงเวลาที่เลือก' : 'ไม่ว่างในช่วงเวลาที่เลือก';
}
function addEquipment(): void {
    const item = additionalEquipment.value.find(item => item.id === additionalEquipmentId.value);
    const amount = additionalEquipmentQuantity.value;
    if (!item || !Number.isInteger(amount) || amount < 1) return;
    equipmentQuantities.value[item.id] = Math.min((equipmentQuantities.value[item.id] ?? 0) + amount, item.is_exclusive ? 1 : item.quantity_total);
    additionalEquipmentId.value = null;
    additionalEquipmentQuantity.value = 1;
}
function removeEquipment(id: number): void { delete equipmentQuantities.value[id]; }

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
    <Head title="สร้างคำขอจอง" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader title="สร้างคำขอจองห้อง Simulation" description="เลือกห้อง วันเวลา และทรัพยากร แล้วส่งให้เจ้าหน้าที่ตรวจสอบ"><Link class="button-secondary" href="/app/calendar">ดูปฏิทินการใช้ห้อง</Link></PageHeader>
        <form class="booking-form" @submit.prevent="submit">
            <section class="panel booking-step-panel room-picker-panel" aria-labelledby="room-title">
                <SectionHeader id="room-title" title="1) ห้องและช่วงเวลา" description="เลือกห้องในวิทยาลัยของคุณ แล้วระบุวันเวลาเพื่อให้ระบบตรวจเวลาว่าง" />
                <div v-if="selectedRoom && !roomPickerOpen" class="selected-room-summary">
                    <div><span>ห้องที่เลือก</span><strong>{{ selectedRoom.name }}</strong><p>{{ [selectedRoom.building, selectedRoom.floor ? `ชั้น ${selectedRoom.floor}` : null].filter(Boolean).join(' / ') || 'ไม่ระบุอาคารและชั้น' }} · ความจุ {{ selectedRoom.capacity ?? 'ยังไม่กำหนด' }} คน</p><small role="status">{{ availabilityLabel(selectedRoom) }}</small></div>
                    <button type="button" class="button-secondary" @click="changeRoom">เปลี่ยนห้อง</button>
                </div>
                <div v-else-if="visibleRoom" class="room-carousel" aria-label="เลือกห้อง Simulation">
                    <button v-if="rooms.length > 1" class="room-carousel-control" type="button" aria-label="ดูห้องก่อนหน้า" @click="nextRoom(-1)">‹</button>
                    <article class="room-card" :class="{ unavailable: !roomSelectable(visibleRoom) }">
                        <img v-if="visibleRoom.image_url" :src="visibleRoom.image_url" :alt="`รูปห้อง ${visibleRoom.name}`" class="room-photo">
                        <div v-else class="room-visual" aria-label="ยังไม่มีภาพห้อง">
                            <svg viewBox="0 0 240 130" fill="none" aria-hidden="true"><path d="M30 105V25h180v80M30 105h180M60 105V60h45v45M132 47h51v34h-51z" stroke="currentColor" stroke-width="2"/><path d="M140 64h34M157 49v28M40 25l15-10h140l15 10" stroke="currentColor" stroke-width="2"/></svg>
                            <strong>{{ visibleRoom.name }}</strong><small>ยังไม่มีภาพห้อง</small>
                        </div>
                        <div class="room-card-content">
                            <div class="room-card-heading"><div><h3>{{ visibleRoom.name }}</h3><p>{{ visibleRoom.description || 'ห้องปฏิบัติการ Simulation ของหน่วยงาน' }}</p></div><span class="room-status" :class="{ ready: roomSelectable(visibleRoom) }">{{ roomStatusLabel(visibleRoom) }}</span></div>
                            <dl class="room-meta-grid"><div><dt>อาคาร / ชั้น</dt><dd>{{ [visibleRoom.building, visibleRoom.floor ? `ชั้น ${visibleRoom.floor}` : null].filter(Boolean).join(' / ') || 'ไม่ระบุ' }}</dd></div><div><dt>ความจุ</dt><dd>{{ visibleRoom.capacity === null ? 'ยังไม่กำหนด' : `${visibleRoom.capacity} คน` }}</dd></div><div><dt>ตำแหน่ง</dt><dd>{{ visibleRoom.location || 'ไม่ระบุ' }}</dd></div></dl>
                            <p class="room-availability" role="status">{{ availabilityLabel(visibleRoom) }}</p>
                            <button class="button-primary room-select-button" type="button" :disabled="!roomSelectable(visibleRoom)" @click="chooseRoom(visibleRoom)">เลือกห้องนี้</button>
                        </div>
                    </article>
                    <button v-if="rooms.length > 1" class="room-carousel-control" type="button" aria-label="ดูห้องถัดไป" @click="nextRoom(1)">›</button>
                </div>
                <p v-else class="empty-state">ขณะนี้ไม่มีห้องในหน่วยงาน กรุณาติดต่อเจ้าหน้าที่ศูนย์ SIM</p>
                <p v-if="roomPickerOpen && rooms.length > 1" class="room-position" aria-live="polite">ห้อง {{ activeRoomIndex + 1 }} จาก {{ rooms.length }}</p>
                <p v-if="form.errors.resources" class="error" role="alert">{{ form.errors.resources }}</p>
                <SectionHeader class="section-subgroup" id="time-title" :level="3" title="ช่วงเวลาใช้งาน" description="เวลาแบบ 24 ชั่วโมง · ระบบตรวจเวลาว่างซ้ำก่อนบันทึกคำขอ" />
                <div class="date-time-grid">
                    <label class="field">วันที่เริ่ม<input v-model="dates.startDate" type="date" required></label>
                    <div class="field"><span>เวลาเริ่มต้น</span><Time24Field v-model="dates.startTime" label="เวลาเริ่มต้น" /></div>
                    <label class="field">วันที่สิ้นสุด<input v-model="dates.endDate" type="date" required></label>
                    <div class="field"><span>เวลาสิ้นสุด</span><Time24Field v-model="dates.endTime" label="เวลาสิ้นสุด" /></div>

                </div>
                <p v-if="dates.startDate && dates.startTime && dates.endDate && dates.endTime && !dateTimeValid" class="error" role="alert">วันเวลาสิ้นสุดต้องอยู่หลังวันเวลาเริ่ม</p>
                <p v-if="form.errors.starts_at" class="error" role="alert">{{ form.errors.starts_at }}</p><p v-if="form.errors.ends_at" class="error" role="alert">{{ form.errors.ends_at }}</p>
                <p v-if="checkingRoomAvailability" class="section-help" role="status">กำลังตรวจสอบเวลาว่างของห้อง...</p><p v-if="roomAvailabilityError" class="error" role="alert">{{ roomAvailabilityError }}</p>
                <p v-if="selectedRoom && dateTimeValid && !checkingRoomAvailability" class="section-help" role="status">{{ selectedRoom.name }} · {{ availabilityLabel(selectedRoom) }}</p>
            </section>

            <section class="panel booking-step-panel" aria-labelledby="requester-title"><SectionHeader id="requester-title" title="2) ข้อมูลการใช้งาน" description="ระบุจำนวนผู้เข้าใช้งานและเบอร์ติดต่อ ข้อมูลบัญชีมาจากผู้เข้าสู่ระบบ" /><div class="requester-grid"><label class="field">จำนวนผู้เข้าใช้งาน<input v-model.number="form.participant_count" aria-label="จำนวนผู้เข้าใช้งาน" :aria-invalid="Boolean(form.errors.participant_count)" :aria-describedby="form.errors.participant_count ? 'participant-error' : undefined" type="number" min="1" :max="selectedRoom?.capacity ?? 10000" required :disabled="!selectedRoom?.capacity"><small v-if="selectedRoom">ห้องที่เลือกรองรับได้สูงสุด {{ selectedRoom.capacity ?? 'ยังไม่กำหนด' }} คน</small><small v-else>เลือกห้องก่อนระบุจำนวนผู้เข้าใช้งาน</small><small v-if="form.errors.participant_count" id="participant-error" class="error" role="alert">{{ form.errors.participant_count }}</small></label><label class="field">หน่วยงาน<input :value="page.props.auth.user.college?.name ?? ''" readonly><small>ตามวิทยาลัย / หน่วยงานของคุณ</small></label><label class="field">ชื่อผู้จอง<input :value="page.props.auth.user.name" readonly><small>บันทึกตามบัญชีที่เข้าสู่ระบบ</small></label><label class="field">เบอร์ติดต่อ<input v-model="form.requester_phone" :aria-invalid="Boolean(form.errors.requester_phone)" :aria-describedby="form.errors.requester_phone ? 'phone-error' : undefined" type="tel" inputmode="tel" minlength="7" maxlength="32" autocomplete="tel" required><small v-if="form.errors.requester_phone" id="phone-error" class="error" role="alert">{{ form.errors.requester_phone }}</small></label></div></section>

            <section class="panel booking-step-panel" aria-labelledby="teaching-title">
                <SectionHeader id="teaching-title" title="3) รายวิชาและสถานการณ์ (ถ้ามี)" description="รายวิชา สถานการณ์ และเครื่องจำลองเป็นตัวเลือก ชุดแนะนำปรับได้ก่อนส่งคำขอ" />
                <div class="field-grid">
                    <label class="field">รายวิชา<select v-model="form.course_id"><option :value="null">ไม่ระบุรายวิชา</option><option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code ? course.code + ' · ' : '' }}{{ course.name }}</option></select></label>
                    <label class="field">สถานการณ์จำลอง<select v-model="form.scenario_id"><option :value="null">ไม่เลือกสถานการณ์</option><optgroup v-if="preferredScenarios.length" label="สถานการณ์จำลองที่แนะนำ"><option v-for="scenario in preferredScenarios" :key="scenario.id" :value="scenario.id">{{ scenario.name }}</option></optgroup><optgroup v-if="otherScenarios.length" label="สถานการณ์จำลองอื่น"><option v-for="scenario in otherScenarios" :key="scenario.id" :value="scenario.id">{{ scenario.name }} · {{ scenario.course.name }}</option></optgroup></select></label>
                    <label class="field">ประเภทเครื่องจำลอง<select v-model="selectedSimulatorTypeId"><option :value="null">ไม่เลือกเครื่องจำลอง</option><option v-for="item in simulatorTypes" :key="item.id" :value="item.id">{{ item.name }}{{ recommendedSimulatorTypeIds.has(item.id) ? ' (แนะนำ)' : '' }}</option></select></label>
                    <label class="field">เครื่องจำลอง<select v-model="form.simulator_asset_id" :disabled="selectedSimulatorTypeId === null"><option :value="null">ไม่เลือกเครื่องจำลอง</option><option v-for="asset in filteredSimulatorAssets" :key="asset.id" :value="asset.id" :disabled="asset.status !== 'active' || simulatorAvailability[asset.id] === false">{{ asset.asset_name }}{{ asset.asset_code ? ' · ' + asset.asset_code : '' }}{{ simulatorAvailability[asset.id] === false ? ' (ไม่ว่าง)' : '' }}</option></select></label>
                </div>
                <p v-if="selectedSimulatorTypeId !== null && !filteredSimulatorAssets.length" class="section-help">ยังไม่มีเครื่องจำลองที่พร้อมใช้งานในประเภทนี้</p>
                <p v-if="form.simulator_asset_id !== null && simulatorAvailability[form.simulator_asset_id] === true" class="section-help" role="status">เครื่องจำลองที่เลือกว่างในช่วงเวลานี้ ระบบจะตรวจสอบซ้ำเมื่อส่งคำขอ</p>
                <p v-if="selectedScenario?.recommended_simulator_types.length" class="section-help">ประเภทที่แนะนำ: {{ selectedScenario.recommended_simulator_types.map(item => item.name).join(', ') }}</p>
                <p v-if="form.errors.course_id" class="error">{{ form.errors.course_id }}</p><p v-if="form.errors.scenario_id" class="error">{{ form.errors.scenario_id }}</p><p v-if="form.errors.simulator_asset_id" class="error" role="alert">{{ form.errors.simulator_asset_id }}</p>
                <p v-if="checkingSimulatorAvailability" class="section-help" role="status">กำลังตรวจสอบเวลาว่างของเครื่องจำลอง...</p><p v-if="simulatorAvailabilityError" class="error" role="alert">{{ simulatorAvailabilityError }}</p><p v-if="form.simulator_asset_id !== null && simulatorAvailability[form.simulator_asset_id] === false" class="error" role="alert">เครื่องจำลองที่เลือกไม่ว่างในช่วงเวลานี้ กรุณาเลือกเครื่องอื่นหรือปรับวันเวลา</p>
            </section>

            <section class="panel booking-step-panel" aria-labelledby="equipment-title">
                <SectionHeader id="equipment-title" title="4) อุปกรณ์" description="ปรับชุดแนะนำ เลือกอุปกรณ์เพิ่ม หรือระบุคำขอนอกแค็ตตาล็อก" />
                <div v-if="recommendedEquipment.length" class="equipment-group"><h3>อุปกรณ์จากชุดแนะนำ</h3><div class="equipment-list"><div v-for="item in recommendedEquipment" :key="item.id" class="equipment-row"><div><strong>{{ item.name }}</strong><small>แนะนำจาก {{ selectedScenario?.name }} · สูงสุด {{ item.is_exclusive ? 1 : item.quantity_total }} หน่วย</small></div><input :aria-label="'จำนวน ' + item.name" type="number" min="0" :max="item.is_exclusive ? 1 : item.quantity_total" :value="equipmentQuantities[item.id] ?? 0" @input="handleEquipmentInput(item, $event)"><button type="button" class="button-secondary" @click="removeEquipment(item.id)">เอาออก</button></div></div></div>
                <p v-if="unavailableRecommended.length" class="section-help">อุปกรณ์แนะนำที่ยังไม่พร้อมให้จอง: {{ unavailableRecommended.map(item => item.name).join(', ') }}</p>
                <p v-if="!equipment.length" class="section-help">ไม่มีอุปกรณ์ที่พร้อมให้จองในแค็ตตาล็อก</p>
                <div class="additional-equipment-controls"><label class="field">เลือกอุปกรณ์<select v-model="additionalEquipmentId"><option :value="null">เลือกอุปกรณ์เพิ่มเติม</option><option v-for="item in additionalEquipment" :key="item.id" :value="item.id">{{ item.name }} · สูงสุด {{ item.is_exclusive ? 1 : item.quantity_total }} หน่วย</option></select></label><label class="field">จำนวน<input v-model.number="additionalEquipmentQuantity" type="number" min="1" :max="additionalEquipment.find(item => item.id === additionalEquipmentId)?.is_exclusive ? 1 : additionalEquipment.find(item => item.id === additionalEquipmentId)?.quantity_total ?? 10000"></label><button type="button" class="button-secondary" :disabled="additionalEquipmentId === null" @click="addEquipment">เพิ่มอุปกรณ์</button></div>
                <div v-if="selectedAdditionalEquipment.length" class="equipment-list"><div v-for="item in selectedAdditionalEquipment" :key="item.id" class="equipment-row"><div><strong>{{ item.name }}</strong><small>สูงสุด {{ item.is_exclusive ? 1 : item.quantity_total }} หน่วย</small></div><input :aria-label="'จำนวน ' + item.name" type="number" min="0" :max="item.is_exclusive ? 1 : item.quantity_total" :value="equipmentQuantities[item.id]" @input="handleEquipmentInput(item, $event)"><button type="button" class="button-secondary" @click="removeEquipment(item.id)">เอาออก</button></div></div><p v-else class="empty-state">ยังไม่มีอุปกรณ์เสริมเพิ่มเติม</p>
                <h3 class="custom-equipment-title">คำขออุปกรณ์นอกแค็ตตาล็อก</h3><p class="section-help">เจ้าหน้าที่จะตรวจสอบรายการเหล่านี้แยกจากจำนวนคงเหลือ</p>
                <div v-for="(item, index) in customEquipmentRows" :key="index" class="custom-row"><label class="field">ชื่ออุปกรณ์<input v-model="item.name" :id="'custom-name-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.name'] ? 'custom-name-error-' + index : undefined" maxlength="255" required></label><label class="field">จำนวน<input v-model.number="item.quantity" :id="'custom-quantity-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.quantity'] ? 'custom-quantity-error-' + index : undefined" type="number" min="1" max="10000" required></label><label class="field">รายละเอียด<textarea v-model="item.note" :id="'custom-note-' + index" :aria-describedby="form.errors['custom_equipment.' + index + '.note'] ? 'custom-note-error-' + index : undefined" maxlength="2000" rows="2"></textarea></label><button type="button" class="button-secondary" :aria-label="'นำคำขออุปกรณ์ ' + (index + 1) + ' ออก'" @click="customEquipmentRows.splice(index, 1)">นำรายการออก</button><p v-for="field in ['name','quantity','note']" v-show="form.errors['custom_equipment.' + index + '.' + field]" :key="field" :id="'custom-' + field + '-error-' + index" class="error" role="alert">{{ form.errors['custom_equipment.' + index + '.' + field] }}</p></div>
                <p v-if="form.errors.custom_equipment" class="error" role="alert">{{ form.errors.custom_equipment }}</p><p v-if="form.errors.resources" class="error" role="alert">{{ form.errors.resources }}</p><button type="button" class="button-secondary" :disabled="customEquipmentRows.length >= 50" @click="addCustomEquipment">เพิ่มคำขออุปกรณ์</button>
            </section>


            <section class="panel booking-step-panel"><SectionHeader title="5) หมายเหตุและตรวจสอบก่อนส่ง" description="ตรวจห้อง วันเวลา จำนวนผู้ใช้ และอุปกรณ์ให้ครบก่อนส่งคำขอ เจ้าหน้าที่จะพิจารณาอนุมัติอีกครั้ง" /><label class="field">หมายเหตุ<textarea v-model="form.note" rows="4" maxlength="2000"></textarea></label><p v-if="form.errors.note" class="error">{{ form.errors.note }}</p></section>
            <p v-if="!canSubmit && !form.processing" id="submission-help" class="section-help" role="status">{{ submissionHelp }}</p>
            <div class="form-actions"><Link href="/app/bookings" class="button-secondary">กลับประวัติการจอง</Link><button class="button-primary" type="submit" :disabled="!canSubmit" :aria-describedby="!canSubmit ? 'submission-help' : undefined">{{ form.processing ? 'กำลังส่ง...' : 'ยืนยันส่งคำขอ' }}</button></div>
        </form>
    </AppLayout>
</template>
<style scoped>
.booking-form{display:grid;gap:20px}.booking-step-panel{padding:24px!important}.room-picker-panel{overflow:hidden}.room-carousel{display:grid;grid-template-columns:44px minmax(0,1fr) 44px;gap:12px;align-items:stretch}.room-carousel>.room-card:only-child{grid-column:1/-1}.room-carousel-control{border:1px solid var(--sim-border);border-radius:14px;background:var(--sim-soft);color:var(--sim-navy);font-size:32px;min-height:44px}.room-card{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1fr);gap:24px;min-width:0;border:1px solid var(--sim-border);border-radius:18px;padding:18px}.room-card.unavailable{opacity:.7}.room-photo{width:100%;height:100%;max-height:360px;min-height:245px;object-fit:cover;border-radius:14px}.room-visual{display:flex;flex-direction:column;justify-content:flex-end;min-height:245px;border-radius:14px;background:var(--sim-blue);color:#fff;padding:24px}.room-visual svg{width:100%;max-height:140px;margin:auto 0;opacity:.65}.room-visual strong{font-size:24px;line-height:1.4}.room-visual small{color:#dbeafe;font-size:12px}.room-card-content{display:grid;gap:16px;min-width:0}.room-card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.room-card-heading h3{margin:0;font-size:26px;color:var(--sim-navy)}.room-card-heading p{margin:6px 0 0;color:var(--sim-muted)}.room-status{flex:none;border-radius:999px;background:var(--sim-warning-bg);color:var(--sim-warning);padding:6px 10px;font-size:12px;font-weight:800}.room-status.ready{background:var(--sim-success-bg);color:var(--sim-success)}.room-meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0}.room-meta-grid>div{border:1px solid var(--sim-border);border-radius:12px;background:var(--sim-soft);padding:12px}.room-meta-grid dt{font-size:12px;color:var(--sim-muted)}.room-meta-grid dd{margin:3px 0 0;color:var(--sim-text);font-weight:700}.room-select-button{width:100%}.room-availability,.room-position{margin:0;color:var(--sim-muted);font-size:13px}.room-position{text-align:center;margin-top:12px}.selected-room-summary{display:flex;justify-content:space-between;align-items:center;gap:16px;border:1px solid var(--sim-border);border-radius:14px;background:var(--sim-soft);padding:18px}.selected-room-summary span,.selected-room-summary small{display:block;color:var(--sim-muted);font-size:12px}.selected-room-summary strong{display:block;color:var(--sim-navy);font-size:20px}.selected-room-summary p{margin:4px 0;color:var(--sim-text)}.date-time-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.field{display:grid;gap:8px;align-content:start;font-weight:700}.field input,.field select,.field textarea{width:100%}.field small{color:var(--sim-muted);font-size:12px;font-weight:500}.participant-field{grid-column:span 2}.field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.equipment-group{margin-bottom:20px}.equipment-group h3{margin:0 0 12px}.equipment-list{display:grid;gap:10px;margin:12px 0}.equipment-row{display:grid;grid-template-columns:minmax(0,1fr) 100px auto;align-items:center;gap:16px;border:1px solid var(--sim-border);border-radius:14px;background:var(--sim-soft);padding:14px}.equipment-row strong,.equipment-row small{display:block}.equipment-row small{color:var(--sim-muted);font-size:12px;margin-top:4px}.equipment-row input{width:100%;text-align:right}.additional-equipment-controls{display:grid;grid-template-columns:minmax(0,1.4fr) 140px auto;gap:14px;align-items:end}.empty-state{display:grid;min-height:86px;place-items:center;border:1px dashed #cbd5e1;border-radius:14px;background:var(--sim-soft);color:var(--sim-muted);padding:16px;text-align:center}.custom-equipment-title{margin-top:24px!important}.custom-row{display:grid;grid-template-columns:2fr 1fr 2fr auto;gap:12px;align-items:end;border-bottom:1px solid var(--sim-border);padding:12px 0;margin-bottom:12px}.custom-row .error{grid-column:1/-1}.requester-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-actions{display:flex;justify-content:flex-end;gap:12px}.error{margin:8px 0;color:var(--sim-danger);font-size:13px}.section-help{margin:12px 0;color:var(--sim-muted);font-size:13px}.booking-form .button-primary,.booking-form .button-secondary{min-height:46px}
@media(max-width:1180px){.date-time-grid,.requester-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.room-card{grid-template-columns:1fr}.room-visual{min-height:200px}}
@media(max-width:760px){.booking-step-panel{padding:16px!important}.date-time-grid,.field-grid,.requester-grid,.additional-equipment-controls,.custom-row,.equipment-row{grid-template-columns:1fr}.participant-field{grid-column:auto}.room-carousel{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.room-carousel>.room-card{grid-column:1/-1;grid-row:1}.room-carousel-control{grid-row:2;min-height:44px}.room-card{padding:12px;gap:16px}.room-card-heading,.selected-room-summary{flex-direction:column;align-items:flex-start}.room-meta-grid{grid-template-columns:1fr}.room-card-heading h3{font-size:22px}.room-visual{min-height:180px;padding:16px}.form-actions{flex-direction:column-reverse}.form-actions>*{width:100%}.equipment-row input{text-align:left}.selected-room-summary .button-secondary{width:100%}}
</style>
