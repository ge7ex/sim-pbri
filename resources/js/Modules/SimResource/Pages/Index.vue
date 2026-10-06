<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface ResourceItem {
    id: number;
    name: string;
    kind: 'room' | 'equipment';
    status: 'ready' | 'pending' | 'maintenance';
    quantity_total: number;
    is_exclusive: boolean;
    location: string | null;
    description: string | null;
}

interface SharedProps { auth: { user: any; permissions: string[] } }

const props = defineProps<{ resources: ResourceItem[] }>();
const page = usePage<SharedProps>();
const canManage = computed(() =>
    page.props.auth.permissions.includes('sim-resource.create')
    && page.props.auth.permissions.includes('sim-resource.update'),
);

const form = useForm({
    name: '',
    kind: 'room',
    status: 'ready',
    quantity_total: 1,
    is_exclusive: true,
    location: '',
    description: '',
});

function submit(): void {
    form.post('/app/resources', {
        onSuccess: () => form.reset(),
    });
}

function updateStatus(resource: ResourceItem, status: ResourceItem['status']): void {
    router.put(`/app/resources/${resource.id}`, {
        name: resource.name,
        kind: resource.kind,
        status,
        quantity_total: resource.quantity_total,
        is_exclusive: resource.is_exclusive,
        location: resource.location,
        description: resource.description,
    }, { preserveScroll: true });
}
</script>

<template>
    <Head title="ทรัพยากร SIM" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>SIM Resources</p>
            <h1>ทรัพยากร SIM</h1>
            <span v-if="!canManage">คุณมีสิทธิ์ดูข้อมูล แต่ไม่มีสิทธิ์แก้ไขทรัพยากร</span>
        </header>

        <section v-if="canManage" class="panel">
            <h2>เพิ่มทรัพยากร</h2>
            <form class="resource-form" @submit.prevent="submit">
                <label>ชื่อ<input v-model="form.name" required maxlength="255"></label>
                <label>ประเภท
                    <select v-model="form.kind" @change="form.kind === 'room' && (form.quantity_total = 1, form.is_exclusive = true)">
                        <option value="room">ห้องปฏิบัติการ</option>
                        <option value="equipment">อุปกรณ์เสริม</option>
                    </select>
                </label>
                <label>สถานะ
                    <select v-model="form.status">
                        <option value="ready">พร้อมใช้งาน</option>
                        <option value="pending">รอตรวจสอบ</option>
                        <option value="maintenance">ปิดปรับปรุง</option>
                    </select>
                </label>
                <label>จำนวน<input v-model.number="form.quantity_total" type="number" min="1" :readonly="form.kind === 'room'"></label>
                <label>ตำแหน่ง<input v-model="form.location" maxlength="255"></label>
                <label class="wide">รายละเอียด<textarea v-model="form.description" rows="3" maxlength="2000"></textarea></label>
                <label v-if="form.kind === 'equipment'" class="checkbox"><input v-model="form.is_exclusive" type="checkbox"> กันเวลาแบบ exclusive</label>
                <button type="submit" :disabled="form.processing">เพิ่มทรัพยากร</button>
            </form>
        </section>

        <section class="panel">
            <div v-if="resources.length" class="table-wrap">
                <table>
                    <thead><tr><th>ชื่อ</th><th>ประเภท</th><th>ตำแหน่ง</th><th>จำนวน</th><th>สถานะ</th></tr></thead>
                    <tbody>
                        <tr v-for="resource in resources" :key="resource.id">
                            <td><strong>{{ resource.name }}</strong><span>{{ resource.description ?? '' }}</span></td>
                            <td>{{ resource.kind === 'room' ? 'ห้องปฏิบัติการ' : 'อุปกรณ์เสริม' }}</td>
                            <td>{{ resource.location ?? '-' }}</td>
                            <td>{{ resource.quantity_total }}</td>
                            <td>
                                <select v-if="canManage" :value="resource.status" @change="updateStatus(resource, ($event.target as HTMLSelectElement).value as ResourceItem['status'])">
                                    <option value="ready">พร้อมใช้งาน</option>
                                    <option value="pending">รอตรวจสอบ</option>
                                    <option value="maintenance">ปิดปรับปรุง</option>
                                </select>
                                <span v-else>{{ resource.status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="empty">ยังไม่มีทรัพยากรในหน่วยงานนี้</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{margin-bottom:18px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.heading h1{margin:5px 0;color:#17324f;font-size:34px}.heading span{color:#718096}.panel{margin-bottom:14px;border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:20px}.panel h2{margin:0 0 16px;color:#17324f;font-size:18px}.resource-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.resource-form label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.resource-form input,.resource-form select,.resource-form textarea,table select{border:1px solid #cfd8e1;border-radius:9px;background:#fff;padding:10px;font:inherit}.resource-form .wide{grid-column:span 2}.resource-form .checkbox{display:flex;align-items:center;gap:8px}.resource-form .checkbox input{width:auto}.resource-form button{align-self:end;min-height:41px;border:0;border-radius:9px;background:#17324f;color:#fff;font-weight:800;cursor:pointer}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:13px;border-bottom:1px solid #edf1f4;text-align:left}th{background:#f7f9fb;color:#66788a;font-size:12px}td{color:#263849;font-size:14px}td strong,td span{display:block}td span{margin-top:3px;color:#718096;font-size:12px}.empty{padding:30px;text-align:center;color:#718096}@media(max-width:760px){.resource-form{grid-template-columns:1fr}.resource-form .wide{grid-column:auto}}
</style>
