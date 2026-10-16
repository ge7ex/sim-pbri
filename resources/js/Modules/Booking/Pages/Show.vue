<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { bookingStatusLabel } from '../../../Support/bookingStatus';
import { formatBookingDateTime } from '../../../Support/dateTime';
import BookingStatusBadge from '../Components/BookingStatusBadge.vue';

interface BookingDetail {
    id: number;
    requested_by_user_id: number;
    requester_name: string;
    requester_phone: string | null;
    requested_by: { id: number; name: string } | null;
    starts_at: string;
    ends_at: string;
    participant_count: number | null;
    note: string | null;
    status: string;
    review_reason: string | null;
    course: { id: number; code: string | null; name: string } | null;
    scenario: { id: number; name: string } | null;
    simulator_asset: { asset_name: string; asset_code: string | null; status: string; location: string | null; simulator_type: { id: number; name: string; is_active: boolean } | null } | null;
    resources: Array<{ id: number; name: string; kind: string; status: string; pivot: { quantity: number; is_auto_recommended: boolean; resource_name_snapshot: string | null; resource_location_snapshot: string | null; room_building_snapshot: string | null; room_floor_snapshot: string | null; room_capacity_snapshot: number | null } }>;
    custom_equipment_requests: Array<{ id: number; name: string; quantity: number; note: string | null }>;
    participant_amendments: Array<{ id: number; participant_count: number; reason: string; created_at: string; actor: { name: string } | null }>;
    status_transitions: Array<{
        id: number;
        from_status: string | null;
        to_status: string;
        reason: string | null;
        created_at: string;
        actor: { id: number; name: string } | null;
    }>;
}

interface SharedProps {
    errors: Record<string, string>;
    auth: {
        user: { id: number; name: string; role_label: string | null; college: any };
        permissions: string[];
    };
}

const props = defineProps<{ booking: BookingDetail; canFillParticipantCount: boolean }>();
const page = usePage<SharedProps>();
const recallReason = ref('');
const participantForm = useForm({ participant_count: null as number | null, reason: '' });
function fillParticipantCount(): void {
    participantForm.post(`/app/bookings/${props.booking.id}/participant-count`, { preserveScroll: true });
}
function simulatorStatusLabel(status: string): string {
    const labels: Record<string, string> = { active: 'พร้อมใช้งาน', disabled: 'ปิดใช้งาน', maintenance: 'อยู่ระหว่างบำรุงรักษา' };
    return labels[status] ?? status;
}
const canCancel = computed(() =>
    page.props.auth.permissions.includes('booking.cancel')
    && props.booking.requested_by_user_id === page.props.auth.user.id
    && ['pending', 'approved'].includes(props.booking.status),
);
const canRecall = computed(() =>
    page.props.auth.permissions.includes('booking.approve')
    && ['approved', 'rejected'].includes(props.booking.status),
);

const confirmation = ref<'cancel' | 'recall' | null>(null);
const actionProcessing = ref(false);
const actionError = ref('');
function cancelBooking(): void { if (!actionProcessing.value) confirmation.value = 'cancel'; }
function recallBooking(): void { if (!actionProcessing.value && recallReason.value.trim()) confirmation.value = 'recall'; }
function confirmAction(): void {
    if (!confirmation.value || actionProcessing.value) return;
    const action = confirmation.value;
    if (action === 'recall' && !recallReason.value.trim()) return;
    actionProcessing.value = true;
    actionError.value = '';
    router.post(`/app/bookings/${props.booking.id}/${action}`, action === 'recall' ? { confirmed: true, reason: recallReason.value.trim() } : {}, {
        onFinish: () => { actionProcessing.value = false; confirmation.value = null; },
        onNetworkError: () => { actionError.value = 'เชื่อมต่อระบบไม่ได้ กรุณาตรวจสอบสถานะก่อนลองอีกครั้ง'; return false; },
        onHttpException: () => { actionError.value = 'ระบบตอบกลับไม่สำเร็จ กรุณาตรวจสอบสถานะก่อนลองอีกครั้ง'; return false; },
    });
}
</script>

<template>
    <Head :title="`รายละเอียดคำขอ #${booking.id}`" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader :title="`รายละเอียดคำขอ #${booking.id}`"><Link class="button-secondary" href="/app/bookings">กลับประวัติการจอง</Link><BookingStatusBadge :status="booking.status" /></PageHeader>
        <p v-if="actionError" class="action-errors" role="alert">{{ actionError }}</p>
        <div v-if="Object.keys(page.props.errors).length" class="action-errors" role="alert"><p v-for="(error, key) in page.props.errors" :key="key">{{ error }}</p></div>

        <section class="panel"><SectionHeader title="ข้อมูลคำขอและช่วงเวลา" /><div class="detail-grid">
            <div><span>ชื่อผู้ใช้ห้อง / ผู้ประสานงาน</span><strong>{{ booking.requester_name }}</strong></div>
            <div><span>ผู้ส่งคำขอ</span><strong>{{ booking.requested_by?.name ?? 'ไม่พบผู้ส่งคำขอในหน่วยงานนี้' }}</strong></div>
            <div><span>เบอร์ติดต่อ</span><strong>{{ booking.requester_phone ?? '-' }}</strong></div>
            <div><span>เริ่มใช้งาน</span><strong>{{ formatBookingDateTime(booking.starts_at) }}</strong></div>
            <div><span>สิ้นสุด</span><strong>{{ formatBookingDateTime(booking.ends_at) }}</strong></div>
            <div><span>จำนวนผู้เข้าใช้งาน</span><strong>{{ booking.participant_count ?? '-' }}</strong></div>
            <div><span>รายวิชา</span><strong>{{ booking.course?.name ?? 'ไม่ได้ระบุ' }}</strong></div>
            <div><span>สถานการณ์จำลอง</span><strong>{{ booking.scenario?.name ?? 'ไม่ได้ระบุ' }}</strong></div>
            <div><span>ผลการตรวจสอบ</span><strong>{{ booking.review_reason ?? '-' }}</strong></div>
        </div></section>

        <section v-if="canFillParticipantCount" class="panel">
            <SectionHeader title="เติมจำนวนผู้เข้าใช้งานของคำขอเดิม" />
            <p>ตรวจสอบจำนวนกับผู้จองก่อนบันทึก ระบบจะตรวจความจุห้องและเก็บประวัติการแก้ไข</p>
            <form class="recall" @submit.prevent="fillParticipantCount">
                <label>จำนวนผู้เข้าใช้งาน (คน)<input v-model.number="participantForm.participant_count" type="number" min="1" max="10000" required></label>
                <label>เหตุผลและแหล่งยืนยันจำนวน<textarea v-model="participantForm.reason" rows="3" maxlength="2000" required></textarea></label>
                <p v-if="participantForm.errors.participant_count" role="alert">{{ participantForm.errors.participant_count }}</p>
                <p v-if="participantForm.errors.reason" role="alert">{{ participantForm.errors.reason }}</p>
                <button type="submit" :disabled="participantForm.processing">บันทึกจำนวนผู้เข้าใช้งาน</button>
            </form>
        </section>

        <section v-if="booking.participant_amendments.length" class="panel">
            <SectionHeader title="ประวัติการเติมจำนวนผู้เข้าใช้งาน" />
            <ul class="timeline"><li v-for="amendment in booking.participant_amendments" :key="amendment.id">
                <strong>ยังไม่ระบุ → {{ amendment.participant_count }} คน</strong>
                <p>{{ amendment.actor?.name ?? 'ไม่พบผู้ดำเนินการที่เข้าถึงได้' }} · {{ formatBookingDateTime(amendment.created_at, true) }}</p>
                <p>{{ amendment.reason }}</p>
            </li></ul>
        </section>

        <section class="panel">
            <SectionHeader title="เครื่องจำลอง" />
            <div v-if="booking.simulator_asset" class="detail-grid">
                <div><span>ประเภท</span><strong>{{ booking.simulator_asset.simulator_type?.name ?? 'ไม่พบประเภทที่เข้าถึงได้' }}{{ booking.simulator_asset.simulator_type && !booking.simulator_asset.simulator_type.is_active ? ' (ปิดใช้งานประเภท)' : '' }}</strong></div>
                <div><span>เครื่องจำลอง</span><strong>{{ booking.simulator_asset.asset_name }}</strong></div>
                <div><span>รหัสทรัพย์สิน</span><strong>{{ booking.simulator_asset.asset_code || 'ไม่ระบุ' }}</strong></div>
                <div><span>สถานที่จัดเก็บ</span><strong>{{ booking.simulator_asset.location || 'ไม่ระบุ' }}</strong></div>
                <div><span>สถานะปัจจุบันของเครื่อง</span><strong>{{ simulatorStatusLabel(booking.simulator_asset.status) }}</strong></div>
            </div>
            <p v-else>ไม่ได้เลือกเครื่องจำลอง</p>
        </section>

        <section class="panel">
            <SectionHeader title="ห้องและอุปกรณ์" />
            <div class="resource-list">
                <div v-for="resource in booking.resources" :key="resource.id">
                    <strong>{{ resource.pivot.resource_name_snapshot ?? resource.name }} <small>{{ resource.kind === 'room' ? 'ห้อง' : 'อุปกรณ์จากแค็ตตาล็อก' }}<template v-if="!resource.pivot.resource_name_snapshot"> · รายการเดิมใช้ข้อมูลปัจจุบัน</template></small><small v-if="resource.pivot.resource_name_snapshot"> · ณ เวลาส่งคำขอ<template v-if="resource.pivot.resource_location_snapshot"> · {{ resource.pivot.resource_location_snapshot }}</template><template v-if="resource.kind === 'room' && resource.pivot.room_building_snapshot"> · {{ resource.pivot.room_building_snapshot }}</template><template v-if="resource.kind === 'room' && resource.pivot.room_floor_snapshot"> · ชั้น {{ resource.pivot.room_floor_snapshot }}</template><template v-if="resource.kind === 'room' && resource.pivot.room_capacity_snapshot"> · {{ resource.pivot.room_capacity_snapshot }} คน</template></small></strong>
                    <span>จำนวน {{ resource.pivot.quantity }}<template v-if="resource.pivot.is_auto_recommended"> · อุปกรณ์จากชุดแนะนำ</template></span>
                </div>
            </div>
        </section>

        <section v-if="booking.custom_equipment_requests.length" class="panel">
            <SectionHeader title="คำขออุปกรณ์เพิ่มเติม" />
            <div class="resource-list">
                <div v-for="item in booking.custom_equipment_requests" :key="item.id">
                    <strong>{{ item.name }} <small v-if="item.note">{{ item.note }}</small></strong>
                    <span>จำนวน {{ item.quantity }}</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <SectionHeader title="ประวัติสถานะ" />
            <ol class="timeline">
                <li v-for="transition in booking.status_transitions" :key="transition.id">
                    <div>
                        <strong>{{ bookingStatusLabel(transition.from_status) }} → {{ bookingStatusLabel(transition.to_status) }}</strong>
                        <span>{{ transition.actor?.name ?? 'ไม่พบผู้ดำเนินการที่เข้าถึงได้' }} · {{ formatBookingDateTime(transition.created_at, true) }}</span>
                    </div>
                    <p v-if="transition.reason">{{ transition.reason }}</p>
                </li>
            </ol>
        </section>

        <section v-if="canCancel || canRecall" class="panel"><SectionHeader title="การดำเนินการ" description="ยกเลิกหรือเรียกกลับตามสิทธิ์และสถานะปัจจุบันของคำขอ" /><div class="actions">
            <button v-if="canCancel" type="button" :disabled="actionProcessing" @click="cancelBooking">ยกเลิกคำขอ</button>
            <div v-if="canRecall" class="recall">
                <label>เหตุผลที่เรียกกลับ<textarea v-model="recallReason" rows="3" maxlength="2000"></textarea></label>
                <button type="button" :disabled="actionProcessing || !recallReason.trim()" @click="recallBooking">เรียกกลับเพื่อตรวจสอบใหม่</button>
            </div>
        </div></section>
        <ConfirmDialog :open="confirmation !== null" :busy="actionProcessing"
            :title="confirmation === 'recall' ? 'ยืนยันเรียกกลับเพื่อตรวจสอบใหม่' : 'ยืนยันยกเลิกคำขอ'"
            :description="confirmation === 'recall' ? `คำขอ #${booking.id} จะเปลี่ยนจาก${bookingStatusLabel(booking.status)}เป็นรอตรวจสอบ และต้องได้รับการพิจารณาอีกครั้ง\nเหตุผล: ${recallReason.trim()}` : `คำขอ #${booking.id} จะถูกยกเลิกและคืนช่วงเวลาที่จองไว้`"
            :confirm-label="confirmation === 'recall' ? 'ยืนยันเปลี่ยนเป็นรอตรวจสอบ' : 'ยืนยันยกเลิกคำขอ'"
            @close="confirmation = null" @confirm="confirmAction" />
    </AppLayout>
</template>

<style scoped>
.action-errors{margin-bottom:14px;border:1px solid #e5c1c1;border-radius:10px;padding:12px 16px;color:#a43b3b;background:#fff}.action-errors p{margin:4px 0}
.heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}.heading a{color:var(--sim-blue);font-size:13px;font-weight:800;text-decoration:none}.heading h1{margin:8px 0 0}.panel{margin-bottom:14px}.panel h2{margin:0 0 14px}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.detail-grid div{display:grid;gap:5px}.detail-grid span{color:var(--sim-muted);font-size:12px}.detail-grid strong{color:var(--sim-text)}.resource-list{display:grid;gap:8px}.resource-list div{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--sim-border);padding:9px 0}.resource-list span{color:var(--sim-muted)}.timeline{display:grid;gap:12px;margin:0;padding-left:20px}.timeline li{padding-left:6px}.timeline li>div{display:flex;justify-content:space-between;gap:16px}.timeline span{color:var(--sim-muted);font-size:12px}.timeline p{margin:5px 0 0;color:#53677a}.actions{display:flex;gap:16px;align-items:flex-start}.actions button{border:1px solid #cfd8e1;border-radius:9px;background:#fff;color:#7c3434;padding:10px 14px;font-weight:800;cursor:pointer}.recall{display:grid;gap:8px;flex:1}.recall label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.recall button{width:fit-content;color:var(--sim-blue)}.recall button:disabled{opacity:.5}@media(max-width:760px){.detail-grid{grid-template-columns:1fr}.timeline li>div,.actions{display:grid}}
</style>
