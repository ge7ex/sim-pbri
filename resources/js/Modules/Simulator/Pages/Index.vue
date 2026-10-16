<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';

interface SimulatorType { id: number; name: string; description: string | null; is_active: boolean }
interface MaintenanceRecord {
    id: number; maintenance_date: string; description: string; performed_by: string | null;
    cost?: string | number | null; note?: string | null;
}
interface Asset {
    id: number; simulator_type_id: number; asset_name: string; asset_code: string | null;
    status: 'active' | 'disabled' | 'maintenance'; location: string | null; description: string | null;
    simulator_type: { id: number; name: string; is_active: boolean } | null;
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
const editor = ref<'type' | 'asset' | null>(null);
const confirmation = ref<'type' | 'asset' | null>(null);
const selectedTypeId = ref<number | null>(props.types[0]?.id ?? null);
const selectedAssetId = ref<number | null>(props.assets.data[0]?.id ?? null);
const selectedType = computed(() => props.types.find(item => item.id === selectedTypeId.value) ?? null);
const selectedAsset = computed(() => props.assets.data.find(item => item.id === selectedAssetId.value) ?? null);
const editorOpen = computed(() => editor.value !== null || maintenanceAsset.value !== null);
watch(() => props.assets.data, items => { if (!items.some(item => item.id === selectedAssetId.value)) selectedAssetId.value = items[0]?.id ?? null; });
watch(() => props.types, items => { if (!items.some(item => item.id === selectedTypeId.value)) selectedTypeId.value = items[0]?.id ?? null; });
const registryPanel = ref<HTMLElement | null>(null);
const typePanel = ref<HTMLElement | null>(null);
const assetPanel = ref<HTMLElement | null>(null);
const maintenancePanel = ref<HTMLElement | null>(null);
function focusPanel(panel: { value: HTMLElement | null }): void {
    nextTick(() => { panel.value?.focus({ preventScroll: true }); panel.value?.scrollIntoView({ block: 'start' }); });
}
const typeForm = useForm({ name: '', description: '', is_active: true });
const assetForm = useForm({
    simulator_type_id: null as number | null, asset_name: '', asset_code: '',
    purchase_year: null as number | null, purchase_price: null as number | string | null,
    useful_life_years: null as number | null, status: 'active' as Asset['status'], location: '', description: '',
});
const maintenanceForm = useForm({ maintenance_date: '', description: '', cost: null as number | string | null, performed_by: '', note: '' });
const showTypeForm = computed(() => editor.value === 'type' && (editingType.value === null ? canCreate.value : canUpdate.value));
const showAssetForm = computed(() => editor.value === 'asset' && (editingAsset.value === null ? canCreate.value : canUpdate.value));

function resetType(): void { editor.value = null; editingType.value = null; typeForm.reset(); typeForm.clearErrors(); focusPanel(registryPanel); }
function resetAsset(): void { editor.value = null; editingAsset.value = null; assetForm.reset(); assetForm.clearErrors(); focusPanel(registryPanel); }
function editType(item: SimulatorType): void {
    editor.value = 'type';
    focusPanel(typePanel);
    typeForm.clearErrors(); editingType.value = item.id;
    Object.assign(typeForm, { name: item.name, description: item.description ?? '', is_active: item.is_active });
}
function editAsset(item: Asset): void {
    editor.value = 'asset';
    focusPanel(assetPanel);
    assetForm.clearErrors(); editingAsset.value = item.id;
    Object.assign(assetForm, {
        simulator_type_id: item.simulator_type_id, asset_name: item.asset_name, asset_code: item.asset_code ?? '',
        purchase_year: item.purchase_year ?? null, purchase_price: item.purchase_price ?? null,
        useful_life_years: item.useful_life_years ?? null, status: item.status,
        location: item.location ?? '', description: item.description ?? '',
    });
}
function openCreate(kind: 'type' | 'asset'): void {
    resetType(); resetAsset(); editor.value = kind;
    focusPanel(kind === 'type' ? typePanel : assetPanel);
}
function requestTypeSave(): void {
    if (!typeForm.processing) { if (editingType.value === null) submitType(); else confirmation.value = 'type'; }
}
function requestAssetSave(): void {
    if (!assetForm.processing) { if (editingAsset.value === null) submitAsset(); else confirmation.value = 'asset'; }
}
function confirmSave(): void {
    if (typeForm.processing || assetForm.processing) return;
    const target = confirmation.value;
    confirmation.value = null;
    if (target === 'type') submitType();
    if (target === 'asset') submitAsset();
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
    focusPanel(maintenancePanel);
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
        <PageHeader title="ทะเบียนเครื่องจำลองและทรัพย์สิน" description="ประเภทเครื่องจำลอง ทะเบียนทรัพย์สิน และประวัติการบำรุงรักษาของหน่วยงาน"></PageHeader>
        <p v-if="page.props.flash?.success" class="success" role="status">{{ page.props.flash.success }}</p>

        <section class="panel type-panel">
            <SectionHeader title="ประเภทเครื่องจำลอง" description="เลือกเพื่ออ่านรายละเอียด กดแก้ไขเมื่อต้องการเปลี่ยนข้อมูล"><template #actions><button v-if="canCreate" type="button" :disabled="editorOpen" @click="openCreate('type')">เพิ่มประเภท</button></template></SectionHeader>
            <div v-if="types.length" class="registry-select"><label for="registry-type">เลือกประเภทเครื่องจำลอง</label><select id="registry-type" v-model="selectedTypeId" :disabled="editorOpen"><option v-for="item in types" :key="item.id" :value="item.id">{{ item.name }}</option></select></div>
            <article v-if="selectedType" class="type-item"><div><strong>{{ selectedType.name }}</strong><p>{{ selectedType.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p></div><div class="actions"><span class="badge" :class="selectedType.is_active ? 'active' : 'disabled'">{{ selectedType.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span><button v-if="canUpdate" type="button" class="secondary" :disabled="editorOpen" @click="editType(selectedType)">แก้ไขประเภท</button></div></article>
            <p v-else class="empty">ยังไม่มีประเภทเครื่องจำลอง</p>
        </section>

        <section ref="registryPanel" class="panel" tabindex="-1">
            <SectionHeader title="ทะเบียนทรัพย์สิน" description="เลือกทรัพย์สิน → อ่านรายละเอียด → กดแก้ไข → ยืนยันการบันทึก"><template #actions><button v-if="canCreate" type="button" :disabled="editorOpen || !types.length" @click="openCreate('asset')">เพิ่มทรัพย์สิน</button></template></SectionHeader>
            <div v-if="assets.data.length" class="registry-select"><label for="registry-asset">เลือกทรัพย์สินในหน้านี้</label><select id="registry-asset" v-model="selectedAssetId" :disabled="editorOpen"><option v-for="item in assets.data" :key="item.id" :value="item.id">{{ item.asset_name }} · {{ item.asset_code || 'ไม่มีรหัส' }}</option></select></div>
            <article v-for="item in selectedAsset ? [selectedAsset] : []" :key="item.id" class="asset-card">
                <div class="asset-heading"><div><h3>{{ item.asset_name }}</h3><p>{{ item.simulator_type?.name ?? 'ไม่พบประเภทที่เข้าถึงได้' }}{{ item.simulator_type && !item.simulator_type.is_active ? ' (ปิดใช้งานประเภท)' : '' }}</p></div><span class="badge" :class="item.status">{{ statusLabel(item.status) }}</span></div>
                <dl class="financial-summary"><div><dt>รหัสทรัพย์สิน</dt><dd>{{ item.asset_code || 'ไม่ระบุ' }}</dd></div><div><dt>สถานที่จัดเก็บ</dt><dd>{{ item.location || 'ไม่ระบุ' }}</dd></div></dl>
                    <p class="description">{{ item.description || 'ไม่มีรายละเอียดเพิ่มเติม' }}</p>
                    <SectionHeader v-if="canSeeFinancial" :level="3" title="ข้อมูลการจัดซื้อและค่าเสื่อม" /><dl v-if="canSeeFinancial" class="financial-summary"><div><dt>ปีที่ซื้อ (ค.ศ.)</dt><dd>{{ item.purchase_year ?? 'ไม่ระบุ' }}</dd></div><div><dt>ราคาซื้อ</dt><dd>{{ money(item.purchase_price) }}</dd></div><div><dt>อายุการใช้งาน</dt><dd>{{ item.useful_life_years == null ? 'ไม่ระบุ' : item.useful_life_years + ' ปี' }}</dd></div><template v-if="item.depreciation"><div><dt>ค่าเสื่อมรายปี</dt><dd>{{ money(item.depreciation.annual_amount) }}</dd></div><div><dt>ค่าเสื่อมสะสม ปี {{ item.depreciation.year }}</dt><dd>{{ money(item.depreciation.accumulated_amount) }}</dd></div><div><dt>มูลค่าตามบัญชี</dt><dd>{{ money(item.depreciation.book_value) }}</dd></div></template></dl>
                    <p v-if="canSeeFinancial" class="hint">ค่าเสื่อมราคาเป็นค่าประมาณแบบเส้นตรง มูลค่าคงเหลือเป็นศูนย์ เริ่มคำนวณตั้งแต่ปีถัดจากปีที่ซื้อ</p>
                    <div v-if="canUpdate || canMaintain" class="actions asset-actions"><button v-if="canUpdate" type="button" class="secondary" :disabled="editorOpen" @click="editAsset(item)">แก้ไขทรัพย์สิน / สถานะ</button><button v-if="canMaintain" type="button" class="secondary" :disabled="editorOpen" @click="openMaintenance(item)">เพิ่มประวัติบำรุงรักษา</button></div>
                    <section class="history"><SectionHeader :level="3" title="ประวัติการบำรุงรักษา" :description="`${item.maintenance_records.length} รายการ`" /><ol v-if="item.maintenance_records.length"><li v-for="record in item.maintenance_records" :key="record.id"><strong>{{ record.maintenance_date }}</strong><p>{{ record.description }}</p><small>ผู้ดำเนินการ: {{ record.performed_by || 'ไม่ระบุ' }}<template v-if="canSeeFinancial"> · ค่าใช้จ่าย: {{ money(record.cost) }}</template></small><p v-if="canSeeFinancial && record.note" class="note">หมายเหตุ: {{ record.note }}</p></li></ol><p v-else class="empty">ยังไม่มีประวัติการบำรุงรักษา</p></section>
            </article>
            <p v-if="!assets.data.length" class="empty">ยังไม่มีทรัพย์สินในหน่วยงานนี้</p>
            <nav v-if="assets.links.length > 3" class="pagination" aria-label="หน้ารายการทรัพย์สิน"><template v-for="(link, index) in assets.links" :key="index"><Link v-if="link.url" :href="link.url" :class="{ current: link.active }" :aria-current="link.active ? 'page' : undefined" preserve-scroll>{{ paginationLabel(link.label, index) }}</Link><span v-else class="unavailable">{{ paginationLabel(link.label, index) }}</span></template></nav>
        </section>
        <div v-if="showTypeForm || showAssetForm" class="form-grid">
            <section v-if="showTypeForm" ref="typePanel" class="panel" tabindex="-1" aria-labelledby="type-form-title">
                <SectionHeader id="type-form-title" :title="editingType === null ? 'เพิ่มประเภทเครื่องจำลอง' : 'แก้ไขประเภทเครื่องจำลอง'" />
                <form class="stack" @submit.prevent="requestTypeSave">
                    <label>ชื่อประเภท<input v-model="typeForm.name" required maxlength="255"></label>
                    <label>รายละเอียด<textarea v-model="typeForm.description" rows="3" maxlength="2000"></textarea></label>
                    <label class="checkbox"><input v-model="typeForm.is_active" type="checkbox"> เปิดใช้งานประเภทนี้</label>
                    <div v-if="Object.keys(typeForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in typeForm.errors" :key="key">{{ error }}</p></div>
                    <div class="actions"><button :disabled="typeForm.processing" type="submit">{{ editingType === null ? 'เพิ่มประเภท' : 'ตรวจสอบและยืนยันการแก้ไข' }}</button><button type="button" class="secondary" :disabled="typeForm.processing" @click="resetType">ยกเลิกการแก้ไข</button></div>
                </form>
            </section>
            <section v-if="showAssetForm" ref="assetPanel" class="panel" tabindex="-1" aria-labelledby="asset-form-title">
                <SectionHeader id="asset-form-title" :title="editingAsset === null ? 'เพิ่มทรัพย์สิน' : 'แก้ไขทรัพย์สินและสถานะ'" />
                <form class="stack" @submit.prevent="requestAssetSave">
                    <label>ประเภทเครื่องจำลอง<select v-model="assetForm.simulator_type_id" required><option :value="null" disabled>เลือกประเภท</option><option v-for="item in types" :key="item.id" :value="item.id">{{ item.name }}{{ item.is_active ? '' : ' (ปิดใช้งาน)' }}</option></select></label>
                    <div class="fields"><label>ชื่อทรัพย์สิน<input v-model="assetForm.asset_name" required maxlength="255"></label><label>รหัสทรัพย์สิน (ถ้ามี)<input v-model="assetForm.asset_code" maxlength="64"></label></div>
                    <div class="fields"><label>สถานะ<select v-model="assetForm.status"><option value="active">พร้อมใช้งาน</option><option value="disabled">ปิดใช้งาน</option><option value="maintenance">อยู่ระหว่างบำรุงรักษา</option></select></label><label>สถานที่จัดเก็บ<input v-model="assetForm.location" maxlength="255"></label></div>
                    <label>รายละเอียด<textarea v-model="assetForm.description" rows="2" maxlength="2000"></textarea></label>
                    <fieldset><legend>ข้อมูลการจัดซื้อและค่าเสื่อมราคา</legend><p class="hint">กรอกทั้ง 3 ช่อง หรือเว้นว่างทั้งหมด ปีที่ซื้อใช้ปี ค.ศ.</p><div class="finance-fields"><label>ปีที่ซื้อ (ค.ศ.)<input v-model.number="assetForm.purchase_year" type="number" min="1900" max="9999" step="1"></label><label>ราคาซื้อ (บาท)<input v-model.number="assetForm.purchase_price" type="number" min="0" step="0.01"></label><label>อายุการใช้งาน (ปี)<input v-model.number="assetForm.useful_life_years" type="number" min="1" step="1"></label></div></fieldset>
                    <div v-if="Object.keys(assetForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in assetForm.errors" :key="key">{{ error }}</p></div>
                    <div class="actions"><button type="submit" :disabled="assetForm.processing || types.length === 0">{{ editingAsset === null ? 'เพิ่มทรัพย์สิน' : 'ตรวจสอบและยืนยันการแก้ไข' }}</button><button type="button" class="secondary" :disabled="assetForm.processing" @click="resetAsset">ยกเลิกการแก้ไข</button></div>
                </form>
            </section>
        </div>

        <section v-if="maintenanceAsset && canMaintain" ref="maintenancePanel" class="panel maintenance-panel" tabindex="-1" aria-labelledby="maintenance-form-title">
            <SectionHeader id="maintenance-form-title" title="เพิ่มประวัติการบำรุงรักษา" :description="maintenanceAsset.asset_name" />
            <form class="stack" @submit.prevent="submitMaintenance">
                <div class="fields"><label>วันที่บำรุงรักษา<input v-model="maintenanceForm.maintenance_date" type="date" required></label><label>ผู้ดำเนินการ<input v-model="maintenanceForm.performed_by" maxlength="255"></label></div>
                <label>รายละเอียดการบำรุงรักษา<textarea v-model="maintenanceForm.description" required rows="3" maxlength="2000"></textarea></label>
                <label>ค่าใช้จ่าย (บาท)<input v-model.number="maintenanceForm.cost" type="number" min="0" step="0.01"></label>
                <label>หมายเหตุสำหรับผู้ดูแล<textarea v-model="maintenanceForm.note" rows="2" maxlength="2000"></textarea></label>
                <div v-if="Object.keys(maintenanceForm.errors).length" class="errors" role="alert"><p v-for="(error, key) in maintenanceForm.errors" :key="key">{{ error }}</p></div>
                <div class="actions"><button type="submit" :disabled="maintenanceForm.processing">บันทึกประวัติ</button><button type="button" class="secondary" :disabled="maintenanceForm.processing" @click="maintenanceAsset = null">ยกเลิก</button></div>
            </form>
        </section>

        <ConfirmDialog :open="confirmation !== null" title="ยืนยันการแก้ไขข้อมูล"
            :description="confirmation === 'type' ? `บันทึกประเภท ${typeForm.name} · ${typeForm.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'} ใช่หรือไม่` : `บันทึกทรัพย์สิน ${assetForm.asset_name}\nสถานะ: ${statusLabel(assetForm.status)}\nสถานที่: ${assetForm.location || 'ไม่ระบุ'} ใช่หรือไม่`"
            confirm-label="ยืนยันบันทึกการแก้ไข" @close="confirmation = null" @confirm="confirmSave" />
    </AppLayout>
</template>

<style scoped>
.registry-select{display:grid;gap:8px;margin:16px 0;font-weight:800;font-size:14px}.registry-select select{min-height:44px;width:100%;max-width:640px}.form-grid{margin-top:20px}.asset-card{margin-top:16px;min-width:0;overflow-wrap:anywhere}.asset-heading p{margin:4px 0;font-size:13px;color:var(--sim-muted)}

.history h3{margin:0;color:var(--sim-text);font-size:14px;font-weight:800}
.heading{margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0}.heading span{line-height:1.6}.panel h2{margin:0}.form-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;margin-bottom:14px}.stack{display:grid;gap:12px;margin-top:16px}.stack label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.stack input,.stack select,.stack textarea{min-width:0;width:100%;box-sizing:border-box}.stack .checkbox{display:flex;align-items:center;gap:8px}.stack .checkbox input{width:auto}.fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.finance-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}fieldset{min-width:0;margin:0;border:1px solid #e3e9ef;border-radius:10px;padding:12px}legend{color:var(--sim-text);font-size:12px;font-weight:800}.hint{margin:0 0 10px;font-size:12px;color:var(--sim-muted)}.actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px}button{border:0;border-radius:9px;background:var(--sim-navy);color:#fff;padding:10px 14px;font-weight:800;cursor:pointer}button.secondary{background:#fff;color:var(--sim-navy);border:1px solid #cfd8e1}button:disabled{opacity:.55;cursor:not-allowed}button:focus-visible,summary:focus-visible,a:focus-visible{outline:3px solid #507fa7;outline-offset:3px}.errors{border:1px solid #e5c1c1;border-radius:9px;padding:10px;color:#a43b3b;font-size:12px}.errors p{margin:4px 0}.success{border:1px solid #b9d8c4;border-radius:10px;background:#edf7f0;color:#2f6f4e;padding:12px 16px}.type-panel,.maintenance-panel{margin-bottom:14px}.type-list{display:grid;margin-top:12px;gap:10px}.type-item{display:flex;align-items:center;justify-content:space-between;gap:16px;border:1px solid #e3e9ef;border-radius:10px;padding:12px}.type-item strong{color:#30475d}.type-item p{margin:4px 0 0;color:var(--sim-muted);font-size:13px;white-space:pre-wrap}.badge{display:inline-block;border-radius:999px;padding:6px 10px;background:#f3f6f8;color:var(--sim-text);font-size:12px;font-weight:800}.badge.active{background:#edf7f0;color:#2f6f4e}.badge.disabled{background:#f5eded;color:#8a6262}.badge.maintenance{background:#fff5e4;color:#886123}.asset-list{display:grid;gap:14px;margin-top:16px}.asset-card{border:1px solid #e3e9ef;border-radius:13px;padding:16px}.asset-heading{display:flex;align-items:center;justify-content:space-between;gap:16px}.asset-heading small{color:var(--sim-muted)}.asset-heading h3{margin:4px 0;color:var(--sim-navy);font-size:18px}.description{margin:8px 0;color:var(--sim-text);font-size:13px;white-space:pre-wrap;overflow-wrap:anywhere}.location{color:var(--sim-muted);font-size:12px}.financial-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;background:#f6f8fa;border-radius:10px;padding:14px}.financial-summary dt{color:var(--sim-muted);font-size:11px}.financial-summary dd{margin:4px 0 0;color:#30475d;font-size:13px;font-weight:700}.asset-actions{margin:12px 0}.history{border-top:1px solid var(--sim-border);padding-top:12px}.history summary{color:#30475d;font-size:13px;font-weight:800;cursor:pointer}.history ol{list-style:none;padding:0;margin:12px 0 0;display:grid;gap:10px}.history li{border:1px solid var(--sim-border);border-radius:9px;padding:12px;color:var(--sim-text);font-size:13px}.history p{margin:5px 0;white-space:pre-wrap;overflow-wrap:anywhere}.history small{color:var(--sim-muted)}.note{color:var(--sim-muted);font-size:12px}.empty{margin:0;padding:20px;color:var(--sim-muted);text-align:center;font-size:13px}.pagination{display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-top:20px}.pagination a,.pagination span{border:1px solid var(--sim-border);border-radius:8px;padding:8px 12px;font-size:13px;text-decoration:none;color:var(--sim-navy)}.pagination .current{background:var(--sim-navy);color:#fff}.pagination .unavailable{color:#98a5b0}@media(max-width:900px){.form-grid{grid-template-columns:1fr}}@media(max-width:600px){.fields,.finance-fields{grid-template-columns:1fr}.financial-summary{grid-template-columns:1fr 1fr}.asset-heading,.type-item{align-items:flex-start;flex-direction:column}}
.management-context{font-size:13px;color:var(--sim-muted);margin:0 0 16px}</style>
