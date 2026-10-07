<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import BookingStatusBadge from '../Components/BookingStatusBadge.vue';

interface BookingRow {
    id: number;
    requester_name: string;
    starts_at: string;
    ends_at: string;
    status: string;
    participant_count: number | null;
    course: { id: number; code: string | null; name: string } | null;
    scenario: { id: number; name: string } | null;
    simulator_asset: { asset_name: string; asset_code: string | null; status: string; location: string | null; simulator_type: { id: number; name: string; is_active: boolean } } | null;
    resources: Array<{ id: number; name: string; kind: string; status: string; pivot: { is_auto_recommended: boolean } }>;
    custom_equipment_requests: Array<{ id: number; name: string; quantity: number; note: string | null }>;
}

interface Pagination<T> { data: T[]; current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null }
interface SharedProps { auth: { user: any; permissions: string[] }; errors: Record<string, string> }

const props = defineProps<{ bookings: Pagination<BookingRow> }>();
const selectedId = ref<number | null>(props.bookings.data[0]?.id ?? null);
const selectedBooking = computed(() => props.bookings.data.find((item) => item.id === selectedId.value) ?? props.bookings.data[0] ?? null);
watch(() => props.bookings.data, (items) => { if (!items.some((item) => item.id === selectedId.value)) selectedId.value = items[0]?.id ?? null; });
const page = usePage<SharedProps>();
const reasons = reactive<Record<number, string>>({});
function simulatorStatusLabel(status: string): string {
    const labels: Record<string, string> = { active: 'พร้อมใช้งาน', disabled: 'ปิดใช้งาน', maintenance: 'อยู่ระหว่างบำรุงรักษา' };
    return labels[status] ?? status;
}

function approve(id: number): void {
    if (!window.confirm('ยืนยันอนุมัติคำขอนี้หรือไม่')) {
        return;
    }
    router.post(`/app/bookings/${id}/approve`);
}

function reject(id: number): void {
    const reason = (reasons[id] ?? '').trim();
    if (!reason) {
        return;
    }
    router.post(`/app/bookings/${id}/reject`, { reason });
}
</script>

<template>
    <Head title="ตรวจสอบคำขอ" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>Review Queue</p>
            <h1>ตรวจสอบคำขอ</h1>
            <span>คำขอสถานะรอตรวจสอบจะกันช่วงเวลาทรัพยากรไว้จนกว่าจะถูกไม่อนุมัติหรือยกเลิก</span>
        </header>
        <div v-if="Object.keys(page.props.errors).length" class="action-errors" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <nav class="workflow-links" aria-label="กลุ่มคำขอ">
            <Link href="/app/review" aria-current="page">รอตรวจสอบ ({{ bookings.total }})</Link>
            <Link href="/app/bookings?status=approved">อนุมัติแล้ว</Link>
            <Link href="/app/bookings?status=rejected">ไม่อนุมัติ</Link>
        </nav>
        <div v-if="bookings.data.length" class="review-workspace">
            <section class="panel request-list" aria-label="เลือกคำขอที่รอตรวจสอบ">
                <h2>รายการรอตรวจสอบ</h2>
                <button v-for="item in bookings.data" :key="item.id" type="button" class="request-option" :class="{ selected: selectedBooking?.id === item.id }" :aria-pressed="selectedBooking?.id === item.id" @click="selectedId = item.id">
                    <strong>#{{ item.id }} · {{ item.requester_name }}</strong><span>{{ new Date(item.starts_at).toLocaleString('th-TH') }}</span>
                </button>
            </section>
            <div class="review-list">
            <article v-for="booking in selectedBooking ? [selectedBooking] : []" :key="booking.id" class="review-card">
                <div class="review-main">
                    <div class="review-title">
                        <div>
                            <strong>#{{ booking.id }} · {{ booking.requester_name }}</strong>
                            <span>{{ new Date(booking.starts_at).toLocaleString('th-TH') }} — {{ new Date(booking.ends_at).toLocaleString('th-TH') }}</span>
                        </div>
                        <BookingStatusBadge :status="booking.status" />
                    </div>

                    <dl>
                        <div><dt>รายวิชา</dt><dd>{{ booking.course?.name ?? 'ไม่ได้ระบุ' }}</dd></div>
                        <div><dt>สถานการณ์จำลอง</dt><dd>{{ booking.scenario?.name ?? 'ไม่ได้ระบุ' }}</dd></div>
                        <div><dt>ห้อง</dt><dd>{{ booking.resources.filter((item) => item.kind === 'room').map((item) => item.name).join(', ') || 'ไม่ได้ระบุ' }}</dd></div>
                        <div><dt>เครื่องจำลอง</dt><dd v-if="booking.simulator_asset">{{ booking.simulator_asset.simulator_type.name }}{{ booking.simulator_asset.simulator_type.is_active ? '' : ' (ปิดใช้งานประเภท)' }} · {{ booking.simulator_asset.asset_name }}{{ booking.simulator_asset.asset_code ? ' · ' + booking.simulator_asset.asset_code : '' }}<br>สถานะปัจจุบัน: {{ simulatorStatusLabel(booking.simulator_asset.status) }} · สถานที่: {{ booking.simulator_asset.location || 'ไม่ระบุ' }}</dd><dd v-else>ไม่ได้เลือกเครื่องจำลอง</dd></div>
                        <div><dt>อุปกรณ์จากแค็ตตาล็อก</dt><dd>{{ booking.resources.filter((item) => item.kind === 'equipment').map((item) => item.name + (item.pivot.is_auto_recommended ? ' (อุปกรณ์จากชุดแนะนำ)' : '')).join(', ') || 'ไม่ได้ระบุ' }}</dd></div>
                        <div><dt>จำนวนผู้เข้าใช้งาน</dt><dd>{{ booking.participant_count ?? '-' }}</dd></div>
                        <div v-if="booking.custom_equipment_requests.length"><dt>คำขออุปกรณ์เพิ่มเติม</dt><dd>{{ booking.custom_equipment_requests.map((item) => item.name + ' × ' + item.quantity + (item.note ? ' — ' + item.note : '')).join(', ') }}</dd></div>
                    </dl>
                    <p v-if="booking.participant_count === null" class="legacy-count-note">คำขอเดิมยังไม่ระบุจำนวนผู้เข้าใช้งาน กรุณาเติมข้อมูลที่หน้ารายละเอียดก่อนอนุมัติ</p>
                    <Link :href="`/app/bookings/${booking.id}`">ดูรายละเอียดและประวัติการตรวจสอบ</Link>
                </div>

                <div class="review-actions">
                    <button class="approve" type="button" @click="approve(booking.id)">อนุมัติ</button>
                    <label :for="`reject-reason-${booking.id}`">เหตุผลกรณีไม่อนุมัติ</label><textarea :id="`reject-reason-${booking.id}`" v-model="reasons[booking.id]" rows="3" maxlength="2000" placeholder="ระบุเหตุผลให้ผู้ขอทราบ"></textarea>
                    <button type="button" :disabled="!(reasons[booking.id] ?? '').trim()" @click="reject(booking.id)">ไม่อนุมัติ</button>
                </div>
            </article>
            </div>
        </div>
        <p v-else class="empty panel">ไม่มีคำขอที่รอตรวจสอบ</p>
        <nav v-if="bookings.last_page > 1" class="review-pager" aria-label="หน้าคำขอ"><Link v-if="bookings.prev_page_url" :href="bookings.prev_page_url">ก่อนหน้า</Link><span>หน้า {{ bookings.current_page }} / {{ bookings.last_page }}</span><Link v-if="bookings.next_page_url" :href="bookings.next_page_url">ถัดไป</Link></nav>
    </AppLayout>
</template>

<style scoped>
.action-errors{margin-bottom:14px;border:1px solid #e5c1c1;border-radius:10px;padding:12px 16px;color:#a43b3b;background:#fff}.action-errors p{margin:4px 0}
.heading{margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0}.review-list{display:grid;gap:14px}.review-card{display:grid;grid-template-columns:1fr;gap:20px;border:1px solid var(--sim-border);border-radius:16px;background:#fff;padding:20px}.review-main{display:grid;gap:14px}.review-title{display:flex;justify-content:space-between;gap:16px}.review-title>div{display:grid;gap:4px}.review-title span,dt{color:var(--sim-muted);font-size:12px}dl{display:grid;gap:8px;margin:0}dl div{display:grid;grid-template-columns:140px 1fr;gap:12px}dd{margin:0;color:var(--sim-text)}.review-main a{width:fit-content;color:var(--sim-blue);font-weight:800;text-decoration:none}.review-actions{display:grid;gap:9px;border-top:1px solid var(--sim-border);padding-top:16px}.review-actions textarea{width:100%}.review-actions button{border:1px solid #cfd8e1;border-radius:9px;background:#fff;color:#7c3434;padding:10px;font-weight:800;cursor:pointer}.review-actions button.approve{border-color:var(--sim-blue);background:var(--sim-blue);color:#fff}.review-actions button:disabled{opacity:.5;cursor:not-allowed}.empty{border:1px solid var(--sim-border);border-radius:16px;background:#fff;padding:42px;text-align:center;color:var(--sim-muted)}@media(max-width:780px){.review-card{grid-template-columns:1fr}.review-actions{border-left:0;border-top:1px solid var(--sim-border);padding:16px 0 0}}
.review-workspace{display:grid;grid-template-columns:260px minmax(0,1fr);gap:20px;align-items:start}.request-list{display:grid;gap:8px}.request-option{display:grid;gap:4px;width:100%;min-width:0;text-align:left;border:1px solid var(--sim-border);border-radius:9px;background:var(--sim-soft);padding:12px;color:var(--sim-text);overflow-wrap:anywhere}.request-option span{font-size:12px;color:var(--sim-muted)}.request-option.selected{background:var(--sim-tint);border-color:var(--sim-blue)}.review-actions label{font-weight:800}.review-pager{display:flex;justify-content:center;gap:16px;margin-top:20px}@media(max-width:1100px){.review-workspace{grid-template-columns:1fr}.request-list{grid-template-columns:repeat(2,minmax(0,1fr))}.request-list h2{grid-column:1/-1}}@media(max-width:600px){.request-list{grid-template-columns:1fr}.review-main dl div{grid-template-columns:1fr;gap:4px}}
.legacy-count-note{margin:0;border:1px solid var(--sim-border);border-radius:9px;padding:12px;background:var(--sim-warning-bg);color:var(--sim-warning);font-size:13px}</style>
