<script setup lang="ts">
import { computed, nextTick, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import MetricCard from '../../../Components/MetricCard.vue';
import UsageRanking from '../Components/UsageRanking.vue';
import BookingTrend from '../Components/BookingTrend.vue';

interface Option { id: number; name: string; course_id?: number }
interface Filters { date_from: string; date_to: string; status: string | null; room_id: number | null; simulator_asset_id: number | null; course_id: number | null; scenario_id: number | null }
interface Usage { id: number; name: string; bookings: number; hours?: number; participants?: number; average_participants?: number | null; capacity?: number | null; average_capacity_percent?: number | null; last_used_at?: string | null; type_name?: string; status?: string; quantity?: number; course_name?: string }
interface Statistics {
    workflow: { total: number; pending: number; approved: number; rejected: number; cancelled: number };
    trend: { date: string; total: number; approved: number }[];
    rooms: Usage[]; simulators: Usage[]; equipment: Usage[]; courses: Usage[]; scenarios: Usage[];
    participants: { total: number; average: number | null; known_count: number; missing_count: number };

}
interface AssetStatistics { total: number; status_counts: Record<string, number>; maintenance_events: number; maintenance_cost: string; purchase_value: string; current_value: string; valuation_year: number; unvalued_count: number }
const props = defineProps<{ assetStatistics?: AssetStatistics; filters: Filters; filterOptions: { rooms: Option[]; simulators: Option[]; courses: Option[]; scenarios: Option[] }; statistics: Statistics }>();
const page = usePage<{ auth: { user: any; permissions: string[] } }>();
const form = useForm({ ...props.filters });
const resultPanel = ref<HTMLElement | null>(null);
const scenarios = computed(() => props.filterOptions.scenarios.filter(item => !form.course_id || item.course_id === Number(form.course_id)));
const filterErrors = computed(() => Object.values(form.errors));
const statuses = [{ key: 'total', label: 'คำขอทั้งหมด' }, { key: 'pending', label: 'รอตรวจสอบ' }, { key: 'approved', label: 'อนุมัติ' }, { key: 'rejected', label: 'ไม่อนุมัติ' }, { key: 'cancelled', label: 'ยกเลิก' }] as const;
const money = (value: number | string) => Number(value).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function applyFilters(): void {
    form.get('/app/reports', { preserveState: true, preserveScroll: true, onSuccess: () => nextTick(() => resultPanel.value?.focus({ preventScroll: true })) });
}
function resetFilters(): void { router.get('/app/reports'); }
</script>

<template>
    <Head title="รายงานและสถิติ" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader title="รายงานและสถิติ" description="ภาพรวมการใช้งานศูนย์ Simulation ของวิทยาลัยคุณ" />
        <section class="panel report-filters" aria-labelledby="report-filter-title">
            <SectionHeader id="report-filter-title" title="ช่วงเวลาที่ต้องการดู" description="นับคำขอที่มีช่วงเวลาใช้งานทับซ้อนกับวันที่เลือก สูงสุด 366 วัน" />
            <form @submit.prevent="applyFilters" :aria-busy="form.processing">
                <div class="filter-primary">
                    <label>จากวันที่<input v-model="form.date_from" type="date" required><small v-if="form.errors.date_from" class="error">{{ form.errors.date_from }}</small></label>
                    <label>ถึงวันที่<input v-model="form.date_to" type="date" required><small v-if="form.errors.date_to" class="error">{{ form.errors.date_to }}</small></label>
                    <label>สถานะคำขอ<select v-model="form.status"><option :value="null">ทุกสถานะ</option><option value="pending">รอตรวจสอบ</option><option value="approved">อนุมัติ</option><option value="rejected">ไม่อนุมัติ</option><option value="cancelled">ยกเลิก</option></select></label>
                    <div class="filter-actions"><button class="button-primary" type="submit" :disabled="form.processing">{{ form.processing ? 'กำลังแสดงผล...' : 'แสดงผล' }}</button><button class="button-secondary" type="button" :disabled="form.processing" @click="resetFilters">เดือนปัจจุบัน</button></div>
                </div>
                <details class="filter-secondary"><summary>ตัวกรองเพิ่มเติม</summary><div class="filter-extra">
                    <label>ห้อง<select v-model="form.room_id"><option :value="null">ทุกห้อง</option><option v-for="item in filterOptions.rooms" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                    <label>หุ่นจำลอง<select v-model="form.simulator_asset_id"><option :value="null">ทุกหุ่นจำลอง</option><option v-for="item in filterOptions.simulators" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                    <label>รายวิชา<select v-model="form.course_id" @change="form.scenario_id = null"><option :value="null">ทุกรายวิชา</option><option v-for="item in filterOptions.courses" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                    <label>สถานการณ์<select v-model="form.scenario_id"><option :value="null">ทุกสถานการณ์</option><option v-for="item in scenarios" :key="item.id" :value="item.id">{{ item.name }}</option></select></label>
                </div></details>
                <ul v-if="filterErrors.length" class="error filter-errors" role="alert"><li v-for="error in filterErrors" :key="error">{{ error }}</li></ul>
            </form>
        </section>

        <section ref="resultPanel" class="report-summary" tabindex="-1" aria-labelledby="report-summary-title" :aria-busy="form.processing">
            <SectionHeader id="report-summary-title" title="ภาพรวมคำขอ" :description="`${filters.date_from} ถึง ${filters.date_to}`" />
            <div class="report-metrics"><MetricCard v-for="item in statuses" :key="item.key" :label="item.label" :value="statistics.workflow[item.key]" /></div>
            <p v-if="statistics.workflow.total === 0" class="report-zero" role="status">ไม่พบคำขอในช่วงเวลาและตัวกรองนี้ ลองเลือกช่วงเวลาอื่นหรือเดือนปัจจุบัน</p>
        </section>

        <section class="panel report-trend" aria-labelledby="report-trend-title">
            <SectionHeader id="report-trend-title" title="แนวโน้มคำขอ" description="นับตามวันเริ่มใช้งาน รายการที่คร่อมเข้าช่วงเวลานับในวันแรกของช่วง" />
            <BookingTrend :data="statistics.trend" />
        </section>

        <section class="report-participants" aria-labelledby="participant-title">
            <SectionHeader id="participant-title" title="ผู้เข้าร่วมในคำขอที่อนุมัติ" description="เป็นจำนวนคนตามคำขอ ไม่ใช่จำนวนบุคคลที่ไม่ซ้ำหรือหลักฐานเข้าใช้จริง" />
            <div class="participant-metrics"><MetricCard label="ผู้เข้าร่วมรวม (คน)" :value="statistics.participants.total" /><MetricCard label="เฉลี่ยต่อคำขอ (คน)" :value="statistics.participants.average ?? 0" :helper="statistics.participants.known_count ? 'เฉลี่ยเฉพาะคำขอที่มีจำนวนผู้เข้าร่วม' : 'ยังไม่มีข้อมูลจำนวนผู้เข้าร่วม'" /><MetricCard label="คำขอที่ยังไม่มีจำนวนคน" :value="statistics.participants.missing_count" helper="ข้อมูลเดิมอาจยังไม่ระบุจำนวนผู้เข้าร่วม" /></div>
        </section>

        <p class="usage-definition">สถิติการใช้งานด้านล่างนับเฉพาะคำขอที่อนุมัติ ชั่วโมงนับเฉพาะส่วนที่อยู่ในช่วงเวลาที่เลือก คำขอรอตรวจสอบเป็นความต้องการใช้งานและไม่รวมในยอดนี้</p>
        <div class="report-usage-grid">
            <UsageRanking title="การใช้ห้อง" kind="rooms" :items="statistics.rooms" description="10 อันดับแรกตามจำนวนคำขอ พร้อมชั่วโมงและผู้เข้าร่วม" />
            <UsageRanking title="การใช้หุ่นจำลอง" kind="simulators" :items="statistics.simulators" description="10 อันดับแรกของหุ่นที่เลือกจริงในคำขอ ไม่ใช่หุ่นที่แนะนำ" />
            <UsageRanking title="การใช้อุปกรณ์" kind="equipment" :items="statistics.equipment" description="10 อันดับแรกตามจำนวนชิ้น อุปกรณ์นอกระบบไม่รวมในยอดนี้" />
            <UsageRanking title="รายวิชา" kind="courses" :items="statistics.courses" description="10 อันดับแรกของรายวิชาที่เลือกไว้ในคำขอ" />
            <UsageRanking title="สถานการณ์" kind="scenarios" :items="statistics.scenarios" description="10 อันดับแรกของสถานการณ์ที่เลือกไว้ในคำขอ" />
        </div>

        <section v-if="assetStatistics" class="panel report-assets" aria-labelledby="asset-statistics-title">
            <SectionHeader id="asset-statistics-title" title="ทรัพย์สินและการบำรุงรักษา" description="เฉพาะผู้ดูแลระบบ: ทรัพย์สินเป็นยอดปัจจุบัน การบำรุงรักษาตามช่วงวันที่ โดยไม่ใช้ตัวกรองคำขออื่น" />
            <dl class="asset-stat-grid">
                <div><dt>หุ่นจำลองทั้งหมด</dt><dd>{{ assetStatistics.total.toLocaleString('th-TH') }} รายการ</dd></div>
                <div><dt>พร้อมใช้งาน</dt><dd>{{ assetStatistics.status_counts.active ?? 0 }} รายการ</dd></div><div><dt>ปิดการใช้งาน</dt><dd>{{ assetStatistics.status_counts.disabled ?? 0 }} รายการ</dd></div><div><dt>อยู่ระหว่างบำรุงรักษา</dt><dd>{{ assetStatistics.status_counts.maintenance ?? 0 }} รายการ</dd></div>
                <div><dt>ประวัติบำรุงรักษาในช่วง</dt><dd>{{ assetStatistics.maintenance_events }} ครั้ง</dd></div>
                <div><dt>ค่าใช้จ่ายบำรุงรักษา</dt><dd>{{ money(assetStatistics.maintenance_cost) }} บาท</dd></div>
                <div><dt>มูลค่าจัดซื้อรวม</dt><dd>{{ money(assetStatistics.purchase_value) }} บาท</dd></div>
                <div><dt>มูลค่าคงเหลือตามค่าเสื่อม</dt><dd>{{ money(assetStatistics.current_value) }} บาท</dd></div>
            </dl>
            <p class="section-help">ค่าเสื่อมใช้วิธีเดียวกับทะเบียนทรัพย์สิน ณ ปี {{ assetStatistics.valuation_year }} และไม่ใช่มูลค่าตลาด ข้อมูลไม่ครบสำหรับคำนวณ {{ assetStatistics.unvalued_count }} รายการ</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.report-filters,.report-summary,.report-trend,.report-participants,.report-assets{margin-bottom:var(--sim-space-lg)}
.filter-primary{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:var(--sim-space-md);align-items:start}
label{display:grid;gap:8px;font-weight:600;font-size:14px;min-width:0}input,select{width:100%;min-height:44px}.filter-actions{display:flex;gap:8px;align-self:start;padding-top:29px;flex-wrap:wrap}.filter-actions button{min-height:44px;white-space:nowrap}.filter-secondary{margin-top:16px}.filter-secondary summary{cursor:pointer;min-height:44px;display:flex;align-items:center;color:var(--sim-blue);font-weight:600}.filter-extra{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;padding-top:8px}.filter-errors{padding-left:20px}.error{color:var(--sim-danger);font-size:14px}.report-metrics{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}.participant-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.report-zero{margin:16px 0 0;padding:20px;background:var(--sim-soft);border:1px solid var(--sim-border);border-radius:var(--sim-radius);color:var(--sim-muted)}.usage-definition{max-width:80ch;color:var(--sim-muted);font-size:14px;line-height:1.7;margin:0 0 24px}.report-usage-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;margin-bottom:24px}.asset-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;margin:0}.asset-stat-grid dt{color:var(--sim-muted);font-size:14px}.asset-stat-grid dd{margin:8px 0 0;color:var(--sim-navy);font-size:22px;font-weight:700;font-variant-numeric:tabular-nums}.section-help{color:var(--sim-muted);font-size:14px;margin:24px 0 0}
@media(max-width:1100px){.filter-primary{grid-template-columns:repeat(3,minmax(0,1fr))}.filter-actions{grid-column:1/-1;padding-top:0}.report-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.filter-extra{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.filter-primary,.filter-extra,.report-usage-grid{grid-template-columns:1fr}.participant-metrics,.asset-stat-grid{grid-template-columns:1fr}.report-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.report-metrics>:first-child{grid-column:1/-1}.filter-actions{align-items:stretch}.filter-actions button{flex:1}.asset-stat-grid{gap:20px}}
</style>
