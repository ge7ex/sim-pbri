<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import RoomImageInput from '../../../Components/RoomImageInput.vue';

interface StaffItem { id: number; name: string }
interface ResourceItem {
    image_url: string | null;
    id: number; name: string; kind: 'room' | 'equipment'; status: 'ready' | 'pending' | 'maintenance';
    quantity_total: number; is_exclusive: boolean; location: string | null; description: string | null;
    building: string | null; floor: string | null; capacity: number | null;
    responsible_staff_user_id: number | null; responsible_staff: StaffItem | null;
}
interface SharedProps { auth: { user: any; permissions: string[] } }

const props = defineProps<{ resources: ResourceItem[]; responsibleStaff: StaffItem[] }>();
const page = usePage<SharedProps>();
const canManage = computed(() => page.props.auth.permissions.includes('sim-resource.create') && page.props.auth.permissions.includes('sim-resource.update'));
const form = useForm({ name: '', kind: 'room' as 'room' | 'equipment', status: 'ready', quantity_total: 1, is_exclusive: true, location: '', description: '', building: '', floor: '', capacity: null as number | null, responsible_staff_user_id: null as number | null, image: null as File | null, remove_image: false });
const editingRoomId = ref<number | null>(null);
const editingRoom = computed(() => props.resources.find(resource => resource.id === editingRoomId.value) ?? null);
const roomEditPanel = ref<HTMLElement | null>(null);
const roomEditTrigger = ref<HTMLButtonElement | null>(null);
function closeRoomEdit(): void {
    editingRoomId.value = null;
    nextTick(() => roomEditTrigger.value?.focus());
}
const roomEdit = useForm({ name: '', kind: 'room' as 'room' | 'equipment', status: 'ready', quantity_total: 1, is_exclusive: true, location: '', description: '', building: '', floor: '', capacity: null as number | null, responsible_staff_user_id: null as number | null, image: null as File | null, remove_image: false });

function handleKindChange(): void { if (form.kind === 'room') { form.quantity_total = 1; form.is_exclusive = true; } else { form.image = null; } }
function submit(): void { form.post('/app/resources', { onSuccess: () => form.reset() }); }
function startRoomEdit(resource: ResourceItem, event: Event): void {
    roomEditTrigger.value = event.currentTarget as HTMLButtonElement;
    editingRoomId.value = resource.id;
    roomEdit.clearErrors(); roomEdit.image = null; roomEdit.remove_image = false;
    roomEdit.name = resource.name; roomEdit.kind = resource.kind; roomEdit.status = resource.status;
    roomEdit.quantity_total = resource.quantity_total; roomEdit.is_exclusive = resource.is_exclusive;
    roomEdit.location = resource.location ?? ''; roomEdit.description = resource.description ?? '';
    roomEdit.building = resource.building ?? ''; roomEdit.floor = resource.floor ?? '';
    roomEdit.capacity = resource.capacity; roomEdit.responsible_staff_user_id = resource.responsible_staff_user_id;
    nextTick(() => { roomEditPanel.value?.focus({ preventScroll: true }); roomEditPanel.value?.scrollIntoView({ block: 'start' }); });
}
function saveRoom(): void {
    if (editingRoomId.value === null) return;
    roomEdit.transform(data => ({ ...data, _method: 'put' })).post(`/app/resources/${editingRoomId.value}`, { preserveScroll: true, onSuccess: closeRoomEdit });
}
function handleStatusChange(resource: ResourceItem, event: Event): void {
    const status = (event.target as HTMLSelectElement).value as ResourceItem['status'];
    router.put(`/app/resources/${resource.id}`, {
        name: resource.name, kind: resource.kind, status, quantity_total: resource.quantity_total,
        is_exclusive: resource.is_exclusive, location: resource.location, description: resource.description,
        building: resource.building, floor: resource.floor, capacity: resource.capacity,
        responsible_staff_user_id: resource.responsible_staff_user_id,
    }, { preserveScroll: true });
}
</script>

<template>
    <Head title="ทรัพยากร SIM" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader title="ทรัพยากร SIM" description="ห้องและอุปกรณ์ของหน่วยงาน จัดการข้อมูลตามสิทธิ์ของคุณ"></PageHeader>
        <p v-if="!canManage" class="section-help">คุณมีสิทธิ์ดูข้อมูล แต่ไม่มีสิทธิ์แก้ไขทรัพยากร</p>
        <details v-if="canManage" class="panel resource-create">
            <summary>+ เพิ่มทรัพยากร</summary>
            <SectionHeader title="เพิ่มทรัพยากร" description="เลือกประเภทแล้วกรอกข้อมูลห้องหรืออุปกรณ์ของหน่วยงาน" />
            <form class="resource-form" @submit.prevent="submit">
                <label>ชื่อ<input v-model="form.name" required maxlength="255"><small v-if="form.errors.name" class="error">{{ form.errors.name }}</small></label>
                <label>ประเภท<select v-model="form.kind" @change="handleKindChange"><option value="room">ห้องปฏิบัติการ</option><option value="equipment">อุปกรณ์เสริม</option></select></label>
                <label>สถานะ<select v-model="form.status"><option value="ready">พร้อมใช้งาน</option><option value="pending">รอตรวจสอบ</option><option value="maintenance">ปิดปรับปรุง</option></select></label>
                <label>จำนวน<input v-model.number="form.quantity_total" type="number" min="1" :readonly="form.kind === 'room'"></label>




                <label>ตำแหน่ง<input v-model="form.location" maxlength="255"></label>
                <label v-if="form.kind === 'equipment'" class="checkbox"><input v-model="form.is_exclusive" type="checkbox"> กันเวลาแบบ exclusive</label>
                <label class="wide">รายละเอียด<textarea v-model="form.description" rows="3" maxlength="2000"></textarea></label>
                <fieldset v-if="form.kind === 'room'" class="section-fieldset"><legend>ข้อมูลห้อง</legend><div class="section-field-grid"><label>อาคาร<input v-model="form.building" maxlength="120"><small v-if="form.errors.building" class="error">{{ form.errors.building }}</small></label><label>ชั้น<input v-model="form.floor" maxlength="64"><small v-if="form.errors.floor" class="error">{{ form.errors.floor }}</small></label><label>ความจุสูงสุด (คน)<input v-model.number="form.capacity" type="number" min="1" max="10000" required><small v-if="form.errors.capacity" class="error">{{ form.errors.capacity }}</small></label><label>ผู้รับผิดชอบ<select v-model="form.responsible_staff_user_id"><option :value="null">ไม่ระบุ</option><option v-for="staff in responsibleStaff" :key="staff.id" :value="staff.id">{{ staff.name }}</option></select><small v-if="form.errors.responsible_staff_user_id" class="error">{{ form.errors.responsible_staff_user_id }}</small></label><RoomImageInput v-model="form.image" :error="form.errors.image" :disabled="form.processing" /></div></fieldset>

                <progress v-if="form.progress" :value="form.progress.percentage" max="100" aria-label="ความคืบหน้าการอัปโหลด"></progress>
                <button type="submit" :disabled="form.processing">{{ form.processing ? 'กำลังบันทึก...' : 'เพิ่มทรัพยากร' }}</button>
            </form>
        </details>
        <section class="panel">
            <SectionHeader title="ทรัพยากรของหน่วยงาน" :description="`${resources.filter(item => item.kind === 'room').length} ห้อง · ${resources.filter(item => item.kind === 'equipment').length} รายการอุปกรณ์`" />
            <div v-if="resources.length" class="table-wrap" tabindex="0" role="region" aria-label="ห้องและอุปกรณ์ เลื่อนแนวนอนได้">
                <table><thead><tr><th scope="col">ชื่อ</th><th scope="col">ประเภท</th><th scope="col">อาคาร / ชั้น</th><th scope="col">ความจุ / จำนวน</th><th scope="col">ผู้รับผิดชอบ</th><th scope="col">ตำแหน่ง</th><th scope="col">สถานะ</th><th v-if="canManage">จัดการ</th></tr></thead>
                    <tbody><template v-for="resource in resources" :key="resource.id">
                        <tr>
                            <td><img v-if="resource.image_url" :src="resource.image_url" :alt="`รูปห้อง ${resource.name}`" class="room-thumb" loading="lazy"><strong>{{ resource.name }}</strong><span>{{ resource.description ?? '' }}</span></td>
                            <td>{{ resource.kind === 'room' ? 'ห้องปฏิบัติการ' : 'อุปกรณ์เสริม' }}<span v-if="resource.kind === 'equipment'">{{ resource.is_exclusive ? 'ใช้แยกเฉพาะการจอง' : 'แบ่งใช้ตามจำนวน' }}</span></td>
                            <td>{{ resource.kind === 'room' ? [resource.building, resource.floor ? `ชั้น ${resource.floor}` : null].filter(Boolean).join(' / ') || 'ไม่ระบุ' : '—' }}</td>
                            <td>{{ resource.kind === 'room' ? (resource.capacity === null ? 'ยังไม่กำหนด — จองไม่ได้' : `${resource.capacity} คน`) : `${resource.quantity_total} ชิ้น` }}</td>
                            <td>{{ resource.responsible_staff?.name ?? '—' }}</td>
                            <td>{{ resource.location ?? '—' }}</td>
                            <td><select v-if="canManage" :value="resource.status" :aria-label="`สถานะ ${resource.name}`" @change="handleStatusChange(resource, $event)"><option value="ready">พร้อมใช้งาน</option><option value="pending">รอตรวจสอบ</option><option value="maintenance">ปิดปรับปรุง</option></select><span v-else>{{ resource.status }}</span></td>
                            <td v-if="canManage"><button v-if="resource.kind === 'room'" type="button" class="secondary" :aria-expanded="editingRoomId === resource.id" aria-controls="room-edit-panel" @click="startRoomEdit(resource, $event)">แก้ไขข้อมูลห้อง</button></td>
                        </tr>

                    </template></tbody>
                </table>
            </div>
            <p v-else class="empty">ยังไม่มีทรัพยากรในหน่วยงานนี้</p>
        </section>
                        <section v-if="editingRoom && canManage" id="room-edit-panel" ref="roomEditPanel" class="panel section" tabindex="-1" aria-labelledby="room-edit-title"><SectionHeader id="room-edit-title" :title="`แก้ไขห้อง · ${editingRoom.name}`" /><form class="resource-form edit-form" @submit.prevent="saveRoom">
                            <label>ชื่อ<input v-model="roomEdit.name" required maxlength="255"><small v-if="roomEdit.errors.name" class="error">{{ roomEdit.errors.name }}</small></label>




                            <label>ตำแหน่ง<input v-model="roomEdit.location" maxlength="255"></label>
                            <label>สถานะ<select v-model="roomEdit.status"><option value="ready">พร้อมใช้งาน</option><option value="pending">รอตรวจสอบ</option><option value="maintenance">ปิดปรับปรุง</option></select></label>
                            <label class="wide">รายละเอียด<textarea v-model="roomEdit.description" rows="2" maxlength="2000"></textarea></label>
                <fieldset class="section-fieldset"><legend>ข้อมูลห้อง</legend><div class="section-field-grid"><label>อาคาร<input v-model="roomEdit.building" maxlength="120"><small v-if="roomEdit.errors.building" class="error">{{ roomEdit.errors.building }}</small></label><label>ชั้น<input v-model="roomEdit.floor" maxlength="64"><small v-if="roomEdit.errors.floor" class="error">{{ roomEdit.errors.floor }}</small></label><label>ความจุสูงสุด (คน)<input v-model.number="roomEdit.capacity" type="number" min="1" max="10000"><small v-if="roomEdit.errors.capacity" class="error">{{ roomEdit.errors.capacity }}</small></label><label>ผู้รับผิดชอบ<select v-model="roomEdit.responsible_staff_user_id"><option :value="null">ไม่ระบุ</option><option v-for="staff in responsibleStaff" :key="staff.id" :value="staff.id">{{ staff.name }}</option></select><small v-if="roomEdit.errors.responsible_staff_user_id" class="error">{{ roomEdit.errors.responsible_staff_user_id }}</small></label><RoomImageInput v-model="roomEdit.image" v-model:remove="roomEdit.remove_image" :current-url="editingRoom.image_url" :error="roomEdit.errors.image || roomEdit.errors.remove_image" :disabled="roomEdit.processing" /></div></fieldset>

                            <progress v-if="roomEdit.progress" :value="roomEdit.progress.percentage" max="100" aria-label="ความคืบหน้าการอัปโหลด"></progress>
                            <div class="edit-actions"><button type="submit" :disabled="roomEdit.processing">บันทึกข้อมูลห้อง</button><button type="button" class="secondary" @click="closeRoomEdit">ยกเลิก</button></div>
                        </form></section>
    </AppLayout>
</template>

<style scoped>
.room-thumb{width:100px;height:65px;object-fit:cover;border-radius:8px;margin-bottom:8px}.heading{margin-bottom:18px}.heading p{margin:0}.heading h1{margin:5px 0}.panel{margin-bottom:14px}.panel h2{margin:0 0 16px}.resource-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.resource-form label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.resource-form .wide{grid-column:1/-1}.resource-form .checkbox{display:flex;align-items:center;gap:8px}.resource-form .checkbox input{width:auto}.resource-form button,.edit-actions button:not(.secondary){align-self:end;min-height:41px;border:0;border-radius:9px;background:var(--sim-navy);color:#fff;padding:9px 12px;font-weight:800;cursor:pointer}.table-wrap{overflow-x:auto}th,td{padding:13px;border-bottom:1px solid var(--sim-border);text-align:left;vertical-align:top}td strong,td span{display:block}td span{margin-top:3px;color:var(--sim-muted);font-size:12px}.secondary{cursor:pointer}.edit-form{padding:12px}.edit-actions{display:flex;gap:8px;align-items:end}.error{color:#a43b3b;font-size:12px}.empty{padding:30px;text-align:center;color:var(--sim-muted)}@media(max-width:760px){.resource-form{grid-template-columns:1fr}.resource-form .wide{grid-column:auto}}
.resource-create summary{color:var(--sim-blue);font-weight:800;cursor:pointer;min-height:32px}.resource-create[open] summary{margin-bottom:20px}table{min-width:850px}</style>
