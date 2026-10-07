<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface SimulatorType { id: number; name: string; description: string | null; is_active: boolean }
interface MaintenanceRecord {
    id: number; maintenance_date: string; description: string; performed_by: string | null;
    cost?: string | number | null; note?: string | null;
}
interface Asset {
    id: number; simulator_type_id: number; asset_name: string; asset_code: string | null;
    status: 'active' | 'disabled' | 'maintenance'; location: string | null; description: string | null;
    simulator_type: { id: number; name: string; is_active: boolean };
    maintenance_records: MaintenanceRecord[];
    purchase_year?: number | null; purchase_price?: number | string | null; useful_life_years?: number | null;
    depreciation?: { year: number; annual_amount: number | string; accumulated_amount: number | string; book_value: number | string } | null;
}
interface SharedProps { auth: { user: any; permissions: string[] }; flash?: { success?: string } }
const props = defineProps<{ types: SimulatorType[]; assets: { data: Asset[]; links: { url: string | null; label: string; active: boolean }[] } }>();
const page = usePage<SharedProps>();
const canCreate = computed(() => page.props.auth.permissions.includes('simulator.create'));
const canUpdate = computed(() => page.props.auth.permissions.includes('simulator.update'));
const canMaintain = computed(() => page.props.auth.permissions.includes('simulator.maintenance'));
const canSeeFinancial = computed(() => canCreate.value || canUpdate.value);
const editingType = ref<number | null>(null);
const editingAsset = ref<number | null>(null);
const maintenanceAsset = ref<Asset | null>(null);
const expandedAssets = ref<number[]>([]);
function toggleDetails(id: number): void {
    expandedAssets.value = expandedAssets.value.includes(id)
        ? expandedAssets.value.filter((assetId) => assetId !== id) : [...expandedAssets.value, id];
}
const typeForm = useForm({ name: '', description: '', is_active: true });
const assetForm = useForm({
    simulator_type_id: null as number | null, asset_name: '', asset_code: '',
    purchase_year: null as number | null, purchase_price: null as number | string | null,
    useful_life_years: null as number | null, status: 'active' as Asset['status'], location: '', description: '',
});
const maintenanceForm = useForm({ maintenance_date: '', description: '', cost: null as number | string | null, performed_by: '', note: '' });
const showTypeForm = computed(() => editingType.value === null ? canCreate.value : canUpdate.value);
const showAssetForm = computed(() => editingAsset.value === null ? canCreate.value : canUpdate.value);

function resetType(): void { editingType.value = null; typeForm.reset(); typeForm.clearErrors(); }
function resetAsset(): void { editingAsset.value = null; assetForm.reset(); assetForm.clearErrors(); }
function editType(item: SimulatorType): void {
    typeForm.clearErrors(); editingType.value = item.id;
    Object.assign(typeForm, { name: item.name, description: item.description ?? '', is_active: item.is_active });
}
function editAsset(item: Asset): void {
    assetForm.clearErrors(); editingAsset.value = item.id;
    Object.assign(assetForm, {
        simulator_type_id: item.simulator_type_id, asset_name: item.asset_name, asset_code: item.asset_code ?? '',
        purchase_year: item.purchase_year ?? null, purchase_price: item.purchase_price ?? null,
        useful_life_years: item.useful_life_years ?? null, status: item.status,
        location: item.location ?? '', description: item.description ?? '',
    });
}
function submitType(): void {
    const options = { preserveScroll: true, onSuccess: resetType };
    if (editingType.value !== null) typeForm.put(`/app/simulators/types/${editingType.value}`, options);
    else typeForm.post('/app/simulators/types', options);
}
function submitAsset(): void {
    assetForm.transform((data) => ({ ...data,
        purchase_year: data.purchase_year === null || String(data.purchase_year) === '' ? null : data.purchase_year,
        purchase_price: data.purchase_price === null || String(data.purchase_price) === '' ? null : data.purchase_price,
        useful_life_years: data.useful_life_years === null || String(data.useful_life_years) === '' ? null : data.useful_life_years,
    }));
    const options = { preserveScroll: true, onSuccess: resetAsset };
    if (editingAsset.value !== null) assetForm.put(`/app/simulators/assets/${editingAsset.value}`, options);
    else assetForm.post('/app/simulators/assets', options);
}
function openMaintenance(item: Asset): void {
    maintenanceForm.reset(); maintenanceForm.clearErrors(); maintenanceAsset.value = item;
}
function submitMaintenance(): void {
    if (!maintenanceAsset.value) return;
    maintenanceForm.transform((data) => ({ ...data, cost: data.cost === null || String(data.cost) === '' ? null : data.cost }));
    maintenanceForm.post(`/app/simulators/assets/${maintenanceAsset.value.id}/maintenance`, {
        preserveScroll: true, onSuccess: () => { maintenanceForm.reset(); maintenanceAsset.value = null; },
    });
}
function statusLabel(status: Asset['status']): string {
    return { active: 'พร้อมใช้งาน', disabled: 'ปิดใช้งาน', maintenance: 'อยู่ระหว่างบำรุงรักษา' }[status];
}
function money(value: number | string | null | undefined): string {
    if (value === null || value === undefined) return 'ไม่ระบุ';
    const amount = Number(value);
    return Number.isFinite(amount) ? amount.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท' : 'ไม่ระบุ';
}
function paginationLabel(label: string, index: number): string {
    if (index === 0) return 'ก่อนหน้า';
    if (index === props.assets.links.length - 1) return 'ถัดไป';
    return /^\d+$/.test(label) ? label : '…';
}
</script>

<template>
    <Head title="ทะเบียนเครื่องจำลองและทรัพย์สิน" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>Simulator / Asset Management</p>
            <h1>ทะเบียนเครื่องจำลองและทรัพย์สิน</h1>
            <span>จัดการประเภทเครื่องจำลอง รายการทรัพย์สิน และประวัติการบำรุงรักษาของหน่วยงาน</span>
        </header>
        <p v-if="page.props.flash?.success" class="success" role="status">{{ page.props.flash.success }}</p>

        <div v-if="showTypeForm || showAssetForm" class="form-grid">
            <section v-if="showTypeForm" class="panel">
                <h2>{{ editingType === null ? 'เพิ่มประเภทเครื่องจำลอง' : 'แก้ไขประเภทเครื่องจำลอง' }}</h2>
                <form class="stack" @submit.prevent="submitType">
                    <label>ชื่อประเภท<input v-model="typeForm.name" required maxlength="255"></label>
                    <label>รายละเอียด<textarea v-model="typeForm.description" rows="3" maxlength="2000"></textarea></label>
                    <label class="checkbox"><input v-model="typeForm.is_active" type="checkbox"> เปิดใช้งานประเภทนี้</label>
                    <div v-if="Object.keys(typeForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in typeForm.errors" :key="key">{{ error }}</p></div>
                    <div class="actions"><button :disabled="typeForm.processing" type="submit">{{ editingType === null ? 'เพิ่มประเภท' : 'บันทึกประเภท' }}</button><button v-if="editingType !== null" type="button" class="secondary" :disabled="typeForm.processing" @click="resetType">ยกเลิกการแก้ไข</button></div>
                </form>
            </section>
            <section v-if="showAssetForm" class="panel">
                <h2>{{ editingAsset === null ? 'เพิ่มทรัพย์สิน' : 'แก้ไขทรัพย์สินและสถานะ' }}</h2>
                <form class="stack" @submit.prevent="submitAsset">
                    <label>ประเภทเครื่องจำลอง<select v-model="assetForm.simulator_type_id" required><option :value="null" disabled>เลือกประเภท</option><option v-for="item in types" :key="item.id" :value="item.id">{{ item.name }}{{ item.is_active ? '' : ' (ปิดใช้งาน)' }}</option></select></label>
                    <div class="fields"><label>ชื่อทรัพย์สิน<input v-model="assetForm.asset_name" required maxlength="255"></label><label>รหัสทรัพย์สิน (ถ้ามี)<input v-model="assetForm.asset_code" maxlength="64"></label></div>
                    <div class="fields"><label>สถานะ<select v-model="assetForm.status"><option value="active">พร้อมใช้งาน</option><option value="disabled">ปิดใช้งาน</option><option value="maintenance">อยู่ระหว่างบำรุงรักษา</option></select></label><label>สถานที่จัดเก็บ<input v-model="assetForm.location" maxlength="255"></label></div>
                    <label>รายละเอียด<textarea v-model="assetForm.description" rows="2" maxlength="2000"></textarea></label>
                    <fieldset><legend>ข้อมูลการจัดซื้อและค่าเสื่อมราคา</legend><p class="hint">กรอกทั้ง 3 ช่อง หรือเว้นว่างทั้งหมด ปีที่ซื้อใช้ปี ค.ศ.</p><div class="finance-fields"><label>ปีที่ซื้อ (ค.ศ.)<input v-model.number="assetForm.purchase_year" type="number" min="1900" max="9999" step="1"></label><label>ราคาซื้อ (บาท)<input v-model.number="assetForm.purchase_price" type="number" min="0" step="0.01"></label><label>อายุการใช้งาน (ปี)<input v-model.number="assetForm.useful_life_years" type="number" min="1" step="1"></label></div></fieldset>
                    <div v-if="Object.keys(assetForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in assetForm.errors" :key="key">{{ error }}</p></div>
                    <div class="actions"><button type="submit" :disabled="assetForm.processing || types.length === 0">{{ editingAsset === null ? 'เพิ่มทรัพย์สิน' : 'บันทึกทรัพย์สิน' }}</button><button v-if="editingAsset !== null" type="button" class="secondary" :disabled="assetForm.processing" @click="resetAsset">ยกเลิกการแก้ไข</button></div>
                </form>
            </section>
        </div>

        <section class="panel type-panel">
            <h2>ประเภทเครื่องจำลอง</h2>
            <div v-if="types.length" class="type-list"><article v-for="item in types" :key="item.id" class="type-item"><div><strong>{{ item.name }}</strong><p>{{ item.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p></div><div class="actions"><span class="badge" :class="item.is_active ? 'active' : 'disabled'">{{ item.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span><button v-if="canUpdate" type="button" class="secondary" :disabled="typeForm.processing" @click="editType(item)">แก้ไข</button></div></article></div>
            <p v-else class="empty">ยังไม่มีประเภทเครื่องจำลอง</p>
        </section>

        <section v-if="maintenanceAsset && canMaintain" class="panel maintenance-panel">
            <h2>เพิ่มประวัติการบำรุงรักษา · {{ maintenanceAsset.asset_name }}</h2>
            <form class="stack" @submit.prevent="submitMaintenance">
                <div class="fields"><label>วันที่บำรุงรักษา<input v-model="maintenanceForm.maintenance_date" type="date" required></label><label>ผู้ดำเนินการ<input v-model="maintenanceForm.performed_by" maxlength="255"></label></div>
                <label>รายละเอียดการบำรุงรักษา<textarea v-model="maintenanceForm.description" required rows="3" maxlength="2000"></textarea></label>
                <label>ค่าใช้จ่าย (บาท)<input v-model.number="maintenanceForm.cost" type="number" min="0" step="0.01"></label>
                <label>หมายเหตุสำหรับผู้ดูแล<textarea v-model="maintenanceForm.note" rows="2" maxlength="2000"></textarea></label>
                <div v-if="Object.keys(maintenanceForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in maintenanceForm.errors" :key="key">{{ error }}</p></div>
                <div class="actions"><button type="submit" :disabled="maintenanceForm.processing">บันทึกประวัติ</button><button type="button" class="secondary" :disabled="maintenanceForm.processing" @click="maintenanceAsset = null">ยกเลิก</button></div>
            </form>
        </section>

        <section class="panel">
            <h2>รายการทรัพย์สิน</h2>
            <div v-if="assets.data.length" class="table-scroll" role="region" aria-label="ทะเบียนทรัพย์สิน" tabindex="0">
                <table class="asset-table">
                    <thead><tr><th scope="col">ทรัพย์สิน</th><th scope="col">ประเภท</th><th scope="col">รหัส</th><th scope="col">สถานที่จัดเก็บ</th><th scope="col">สถานะ</th><th v-if="canSeeFinancial" scope="col">ปีที่ซื้อ (ค.ศ.)</th><th v-if="canSeeFinancial" scope="col">อายุ (ปี)</th><th scope="col">รายละเอียด</th></tr></thead>
                    <tbody><template v-for="item in assets.data" :key="item.id">
                    <tr>
                        <th scope="row">{{ item.asset_name }}</th><td>{{ item.simulator_type.name }}<small v-if="!item.simulator_type.is_active" class="type-disabled">ปิดใช้งานประเภท</small></td><td>{{ item.asset_code || 'ไม่ระบุ' }}</td><td>{{ item.location || 'ไม่ระบุ' }}</td><td><span class="badge" :class="item.status">{{ statusLabel(item.status) }}</span></td><td v-if="canSeeFinancial">{{ item.purchase_year ?? 'ไม่ระบุ' }}</td><td v-if="canSeeFinancial">{{ item.useful_life_years ?? 'ไม่ระบุ' }}</td><td><button type="button" class="secondary detail-toggle" :aria-expanded="expandedAssets.includes(item.id)" :aria-controls="`asset-details-${item.id}`" @click="toggleDetails(item.id)">{{ expandedAssets.includes(item.id) ? 'ปิดรายละเอียด' : 'ดูรายละเอียด' }}</button></td>
                    </tr>
                    <tr v-if="expandedAssets.includes(item.id)" :id="`asset-details-${item.id}`" class="details-row"><td :colspan="canSeeFinancial ? 8 : 6">
                    <p class="description">{{ item.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
                    <dl v-if="canSeeFinancial" class="financial-summary"><div><dt>ปีที่ซื้อ (ค.ศ.)</dt><dd>{{ item.purchase_year ?? 'ไม่ระบุ' }}</dd></div><div><dt>ราคาซื้อ</dt><dd>{{ money(item.purchase_price) }}</dd></div><div><dt>อายุการใช้งาน</dt><dd>{{ item.useful_life_years == null ? 'ไม่ระบุ' : item.useful_life_years + ' ปี' }}</dd></div><template v-if="item.depreciation"><div><dt>ค่าเสื่อมรายปี</dt><dd>{{ money(item.depreciation.annual_amount) }}</dd></div><div><dt>ค่าเสื่อมสะสม ปี {{ item.depreciation.year }}</dt><dd>{{ money(item.depreciation.accumulated_amount) }}</dd></div><div><dt>มูลค่าตามบัญชี</dt><dd>{{ money(item.depreciation.book_value) }}</dd></div></template></dl>
                    <p v-if="canSeeFinancial" class="hint">ค่าเสื่อมราคาเป็นค่าประมาณแบบเส้นตรง มูลค่าคงเหลือเป็นศูนย์ เริ่มคำนวณตั้งแต่ปีถัดจากปีที่ซื้อ</p>
                    <div v-if="canUpdate || canMaintain" class="actions asset-actions"><button v-if="canUpdate" type="button" class="secondary" :disabled="assetForm.processing" @click="editAsset(item)">แก้ไขทรัพย์สิน / สถานะ</button><button v-if="canMaintain" type="button" class="secondary" :disabled="maintenanceForm.processing" @click="openMaintenance(item)">เพิ่มประวัติบำรุงรักษา</button></div>
                    <section class="history"><h3>ประวัติการบำรุงรักษา ({{ item.maintenance_records.length }})</h3><ol v-if="item.maintenance_records.length"><li v-for="record in item.maintenance_records" :key="record.id"><strong>{{ record.maintenance_date }}</strong><p>{{ record.description }}</p><small>ผู้ดำเนินการ: {{ record.performed_by || 'ไม่ระบุ' }}<template v-if="canSeeFinancial"> · ค่าใช้จ่าย: {{ money(record.cost) }}</template></small><p v-if="canSeeFinancial && record.note" class="note">หมายเหตุ: {{ record.note }}</p></li></ol><p v-else class="empty">ยังไม่มีประวัติการบำรุงรักษา</p></section>
                    </td></tr>
                    </template></tbody>
                </table>
            </div>
            <p v-else class="empty">ยังไม่มีทรัพย์สินในหน่วยงานนี้</p>
            <nav v-if="assets.links.length > 3" class="pagination" aria-label="หน้ารายการทรัพย์สิน"><template v-for="(link, index) in assets.links" :key="index"><Link v-if="link.url" :href="link.url" :class="{ current: link.active }" :aria-current="link.active ? 'page' : undefined" preserve-scroll>{{ paginationLabel(link.label, index) }}</Link><span v-else class="unavailable">{{ paginationLabel(link.label, index) }}</span></template></nav>
        </section>
    </AppLayout>
</template>

<style scoped>
.table-scroll{margin-top:16px;overflow-x:auto}.table-scroll:focus-visible{outline:3px solid #507fa7;outline-offset:3px}.asset-table{width:100%;min-width:720px;border-collapse:collapse;text-align:left;font-size:13px}.asset-table th,.asset-table td{border-bottom:1px solid #e3e9ef;padding:12px 10px;vertical-align:top}.asset-table thead th{background:#f4f7fa;color:#526578;font-size:12px;font-weight:800;white-space:nowrap}.asset-table tbody th{color:#17324f;font-weight:800;overflow-wrap:anywhere;min-width:140px}.asset-table td{color:#526578}.asset-table .badge{white-space:nowrap}.detail-toggle{padding:6px 9px;font-size:12px;white-space:nowrap}.type-disabled{display:block;margin-top:4px;color:#8a6262;font-size:11px}.asset-table .details-row>td{padding:16px 20px;background:#fbfcfd}.history h3{margin:0;color:#30475d;font-size:13px;font-weight:800}.details-row .financial-summary{max-width:780px}
.heading{margin-bottom:20px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em}.heading h1{margin:5px 0;color:#17324f;font-size:32px}.heading span{color:#718096;line-height:1.6}.panel{border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:20px}.panel h2{margin:0;color:#17324f;font-size:19px}.form-grid{display:grid;grid-template-columns:1fr 1.5fr;gap:14px;margin-bottom:14px}.stack{display:grid;gap:12px;margin-top:16px}.stack label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.stack input,.stack select,.stack textarea{min-width:0;width:100%;box-sizing:border-box;border:1px solid #cfd8e1;border-radius:9px;background:#fff;padding:10px;font:inherit;color:#263849}.stack .checkbox{display:flex;align-items:center;gap:8px}.stack .checkbox input{width:auto}.fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.finance-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}fieldset{min-width:0;margin:0;border:1px solid #e3e9ef;border-radius:10px;padding:12px}legend{color:#526578;font-size:12px;font-weight:800}.hint{margin:0 0 10px;font-size:12px;color:#718096}.actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px}button{border:0;border-radius:9px;background:#17324f;color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}button.secondary{background:#fff;color:#17324f;border:1px solid #cfd8e1}button:disabled{opacity:.55;cursor:not-allowed}button:focus-visible,summary:focus-visible,a:focus-visible{outline:3px solid #507fa7;outline-offset:3px}.errors{border-left:3px solid #a43b3b;padding-left:10px;color:#a43b3b;font-size:12px}.errors p{margin:4px 0}.success{border:1px solid #b9d8c4;border-radius:10px;background:#edf7f0;color:#2f6f4e;padding:12px 16px}.type-panel,.maintenance-panel{margin-bottom:14px}.type-list{display:grid;margin-top:12px;gap:10px}.type-item{display:flex;align-items:center;justify-content:space-between;gap:16px;border:1px solid #e3e9ef;border-radius:10px;padding:12px}.type-item strong{color:#30475d}.type-item p{margin:4px 0 0;color:#718096;font-size:13px;white-space:pre-wrap}.badge{display:inline-block;border-radius:999px;padding:6px 10px;background:#f3f6f8;color:#526578;font-size:12px;font-weight:800}.badge.active{background:#edf7f0;color:#2f6f4e}.badge.disabled{background:#f5eded;color:#8a6262}.badge.maintenance{background:#fff5e4;color:#886123}.asset-list{display:grid;gap:14px;margin-top:16px}.asset-card{border:1px solid #e3e9ef;border-radius:13px;padding:16px}.asset-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.asset-heading small{color:#718096}.asset-heading h3{margin:4px 0;color:#17324f;font-size:18px}.description{margin:8px 0;color:#526578;font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}.location{color:#718096;font-size:12px}.financial-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;background:#f6f8fa;border-radius:10px;padding:14px}.financial-summary dt{color:#718096;font-size:11px}.financial-summary dd{margin:4px 0 0;color:#30475d;font-size:13px;font-weight:700}.asset-actions{margin:12px 0}.history{border-top:1px solid #edf1f4;padding-top:12px}.history summary{color:#30475d;font-size:13px;font-weight:800;cursor:pointer}.history ol{list-style:none;padding:0;margin:12px 0 0;display:grid;gap:10px}.history li{border-left:3px solid #dfe6ee;padding:4px 0 4px 12px;color:#526578;font-size:13px}.history p{margin:5px 0;white-space:pre-wrap;overflow-wrap:anywhere}.history small{color:#718096}.note{color:#718096;font-size:12px}.empty{margin:0;padding:20px;color:#718096;text-align:center;font-size:13px}.pagination{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:20px}.pagination a,.pagination span{border:1px solid #dfe6ee;border-radius:8px;padding:8px 12px;font-size:13px;text-decoration:none;color:#17324f}.pagination .current{background:#17324f;color:#fff}.pagination .unavailable{color:#98a5b0}@media(max-width:900px){.form-grid{grid-template-columns:1fr}.heading h1{font-size:27px}}@media(max-width:600px){.fields,.finance-fields{grid-template-columns:1fr}.financial-summary{grid-template-columns:1fr 1fr}.asset-heading,.type-item{align-items:flex-start;flex-direction:column}.panel{padding:16px}.heading h1{font-size:24px}}
</style>
