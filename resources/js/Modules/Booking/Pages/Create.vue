<script setup lang="ts">
import { computed, ref } from 'vue';
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

const props = defineProps<{ resources: ResourceItem[] }>();
const page = usePage<SharedProps>();
const selectedRoomId = ref<number | null>(null);
const equipmentQuantities = ref<Record<number, number>>({});

const rooms = computed(() => props.resources.filter((item) => item.kind === 'room'));
const equipment = computed(() => props.resources.filter((item) => item.kind === 'equipment'));

const form = useForm({
    resources: [] as Array<{ id: number; quantity: number }>,
    starts_at: '',
    ends_at: '',
    participant_count: null as number | null,
    requester_phone: '',
    note: '',
});

function setEquipmentQuantity(resource: ResourceItem, value: string): void {
    const quantity = Number(value);

    if (!Number.isInteger(quantity) || quantity <= 0) {
        delete equipmentQuantities.value[resource.id];
        return;
    }

    equipmentQuantities.value[resource.id] = Math.min(quantity, resource.quantity_total);
}

function submit(): void {
    const resources: Array<{ id: number; quantity: number }> = [];

    if (selectedRoomId.value !== null) {
        resources.push({ id: selectedRoomId.value, quantity: 1 });
    }

    for (const [id, quantity] of Object.entries(equipmentQuantities.value)) {
        resources.push({ id: Number(id), quantity });
    }

    form.resources = resources;
    form.post('/app/bookings');
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
                <h2>1. เลือกห้องปฏิบัติการ</h2>
                <p class="section-help">แสดงเฉพาะห้องสถานะพร้อมใช้งานในหน่วยงานของคุณ</p>
                <div v-if="rooms.length" class="resource-grid">
                    <label v-for="room in rooms" :key="room.id" class="resource-card" :class="{ selected: selectedRoomId === room.id }">
                        <input v-model="selectedRoomId" type="radio" name="room" :value="room.id">
                        <strong>{{ room.name }}</strong>
                        <span>{{ room.location ?? 'ไม่ระบุตำแหน่ง' }}</span>
                        <small>{{ room.description ?? 'ไม่มีรายละเอียดเพิ่มเติม' }}</small>
                    </label>
                </div>
                <p v-else class="empty">ยังไม่มีห้องที่พร้อมให้จอง</p>
                <p v-if="form.errors.resources" class="error">{{ form.errors.resources }}</p>
            </section>

            <section class="panel">
                <h2>2. วันเวลาและจำนวนผู้เข้าใช้งาน</h2>
                <div class="field-grid">
                    <label>เริ่มใช้งาน<input v-model="form.starts_at" type="datetime-local" required></label>
                    <label>สิ้นสุด<input v-model="form.ends_at" type="datetime-local" required></label>
                    <label>จำนวนผู้เข้าใช้งาน<input v-model.number="form.participant_count" type="number" min="1" max="10000"></label>
                </div>
                <p v-if="form.errors.starts_at" class="error">{{ form.errors.starts_at }}</p>
                <p v-if="form.errors.ends_at" class="error">{{ form.errors.ends_at }}</p>
            </section>

            <section class="panel">
                <h2>3. อุปกรณ์เสริมเพิ่มเติม</h2>
                <p class="section-help">กรอกจำนวนเฉพาะอุปกรณ์ที่ต้องการ ระบบจะตรวจจำนวนคงเหลือในช่วงเวลาที่เลือกเมื่อส่งคำขอ</p>
                <div v-if="equipment.length" class="equipment-list">
                    <label v-for="item in equipment" :key="item.id">
                        <span><strong>{{ item.name }}</strong><small>พร้อมให้ใช้สูงสุด {{ item.quantity_total }} หน่วย</small></span>
                        <input type="number" min="0" :max="item.quantity_total" placeholder="0" @input="setEquipmentQuantity(item, ($event.target as HTMLInputElement).value)">
                    </label>
                </div>
                <p v-else class="empty">ไม่มีอุปกรณ์เสริมที่พร้อมใช้งาน</p>
            </section>

            <section class="panel">
                <h2>4. ข้อมูลผู้จอง</h2>
                <div class="field-grid">
                    <label>วิทยาลัย / หน่วยงาน<input :value="page.props.auth.user.college?.name ?? ''" readonly></label>
                    <label>ชื่อผู้จอง<input :value="page.props.auth.user.name" readonly></label>
                    <label>เบอร์ติดต่อ<input v-model="form.requester_phone" type="text" maxlength="32"></label>
                </div>
                <label class="full-field">หมายเหตุ<textarea v-model="form.note" rows="4" maxlength="2000"></textarea></label>
                <p class="section-help">ระบบบันทึกผู้ส่งคำขอตามบัญชีที่เข้าสู่ระบบ การจองแทนบุคคลอื่นยังไม่เปิดใช้งานจนกว่าจะมีสิทธิ์เฉพาะรองรับ</p>
            </section>

            <div class="form-actions">
                <button type="submit" :disabled="form.processing || selectedRoomId === null">
                    {{ form.processing ? 'กำลังส่ง...' : 'ยืนยันส่งคำขอ' }}
                </button>
            </div>
        </form>
    </AppLayout>
</template>

<style scoped>
.page-heading { display:flex; justify-content:space-between; gap:20px; margin-bottom:24px; } .eyebrow{margin:0 0 6px;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase} h1{margin:0;color:#16324f;font-size:34px} .page-heading p{color:#66788a}.booking-form{display:grid;gap:18px}.panel{border:1px solid #dfe6ee;border-radius:18px;background:#fff;padding:24px}.panel h2{margin:0;color:#17324f;font-size:19px}.section-help,.empty{color:#718096;line-height:1.6}.resource-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:18px}.resource-card{display:grid;gap:7px;border:1px solid #d9e1e8;border-radius:14px;padding:16px;cursor:pointer}.resource-card.selected{border-color:#315b7c;background:#f4f7fa}.resource-card input{width:auto}.resource-card span,.resource-card small{color:#718096}.field-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-top:18px}label{display:grid;gap:7px;color:#44576a;font-size:13px;font-weight:800}input,textarea{width:100%;border:1px solid #cdd7e0;border-radius:10px;background:#fff;padding:11px 12px;color:#172033;font:inherit}input[readonly]{background:#f4f6f8;color:#627386}.equipment-list{display:grid;gap:10px;margin-top:16px}.equipment-list label{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:1px solid #edf1f4;padding:10px 0}.equipment-list span{display:grid;gap:3px}.equipment-list small{color:#718096;font-weight:500}.equipment-list input{width:110px}.full-field{margin-top:14px}.error{color:#a43b3b;font-size:13px}.form-actions{display:flex;justify-content:flex-end}.form-actions button{border:0;border-radius:11px;background:#17324f;color:#fff;padding:13px 20px;font-weight:800;cursor:pointer}.form-actions button:disabled{opacity:.55;cursor:not-allowed}@media(max-width:720px){.field-grid{grid-template-columns:1fr}.equipment-list label{align-items:flex-start}.resource-grid{grid-template-columns:1fr}}
</style>
