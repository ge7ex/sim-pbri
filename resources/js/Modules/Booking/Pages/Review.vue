<script setup lang="ts">
import { reactive } from 'vue';
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

interface Pagination<T> { data: T[]; current_page: number; last_page: number }
interface SharedProps { auth: { user: any; permissions: string[] }; errors: Record<string, string> }

defineProps<{ bookings: Pagination<BookingRow> }>();
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

        <div v-if="bookings.data.length" class="review-list">
            <article v-for="booking in bookings.data" :key="booking.id" class="review-card">
                <div class="review-main">
                    <div class="review-title">
                        <div>
                            <strong>{{ booking.requester_name }}</strong>
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
                    <Link :href="`/app/bookings/${booking.id}`">ดูรายละเอียดและประวัติ</Link>
                </div>

                <div class="review-actions">
                    <button class="approve" type="button" @click="approve(booking.id)">อนุมัติ</button>
                    <textarea v-model="reasons[booking.id]" rows="2" placeholder="เหตุผลกรณีไม่อนุมัติ"></textarea>
                    <button type="button" :disabled="!(reasons[booking.id] ?? '').trim()" @click="reject(booking.id)">ไม่อนุมัติ</button>
                </div>
            </article>
        </div>
        <p v-else class="empty">ไม่มีคำขอที่รอตรวจสอบ</p>
    </AppLayout>
</template>

<style scoped>
.action-errors{margin-bottom:14px;border:1px solid #e5c1c1;border-radius:10px;padding:12px 16px;color:#a43b3b;background:#fff}.action-errors p{margin:4px 0}
.heading{margin-bottom:20px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.heading h1{margin:5px 0;color:#17324f;font-size:34px}.heading span{color:#718096}.review-list{display:grid;gap:14px}.review-card{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:20px}.review-main{display:grid;gap:14px}.review-title{display:flex;justify-content:space-between;gap:16px}.review-title>div{display:grid;gap:4px}.review-title span,dt{color:#718096;font-size:12px}dl{display:grid;gap:8px;margin:0}dl div{display:grid;grid-template-columns:140px 1fr;gap:12px}dd{margin:0;color:#263849}.review-main a{width:fit-content;color:#315b7c;font-weight:800;text-decoration:none}.review-actions{display:grid;gap:9px;border-left:1px solid #edf1f4;padding-left:20px}.review-actions textarea{width:100%;border:1px solid #cfd8e1;border-radius:9px;padding:10px;font:inherit}.review-actions button{border:1px solid #cfd8e1;border-radius:9px;background:#fff;color:#7c3434;padding:10px;font-weight:800;cursor:pointer}.review-actions button.approve{border-color:#315b7c;background:#315b7c;color:#fff}.review-actions button:disabled{opacity:.5;cursor:not-allowed}.empty{border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:42px;text-align:center;color:#718096}@media(max-width:780px){.review-card{grid-template-columns:1fr}.review-actions{border-left:0;border-top:1px solid #edf1f4;padding:16px 0 0}}
</style>
