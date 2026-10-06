<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import BookingStatusBadge from '../Components/BookingStatusBadge.vue';

interface BookingDetail {
    id: number;
    requested_by_user_id: number;
    requester_name: string;
    requester_phone: string | null;
    starts_at: string;
    ends_at: string;
    participant_count: number | null;
    note: string | null;
    status: string;
    review_reason: string | null;
    resources: Array<{ id: number; name: string; kind: string; status: string; pivot: { quantity: number } }>;
    status_transitions: Array<{
        id: number;
        from_status: string | null;
        to_status: string;
        reason: string | null;
        created_at: string;
        actor: { id: number; name: string };
    }>;
}

interface SharedProps {
    auth: {
        user: { id: number; name: string; role_label: string | null; college: any };
        permissions: string[];
    };
}

const props = defineProps<{ booking: BookingDetail }>();
const page = usePage<SharedProps>();
const recallReason = ref('');
const canCancel = computed(() =>
    page.props.auth.permissions.includes('booking.cancel')
    && props.booking.requested_by_user_id === page.props.auth.user.id
    && ['pending', 'approved'].includes(props.booking.status),
);
const canRecall = computed(() =>
    page.props.auth.permissions.includes('booking.approve')
    && ['approved', 'rejected'].includes(props.booking.status),
);

function cancelBooking(): void {
    if (window.confirm('ยืนยันยกเลิกคำขอนี้หรือไม่')) {
        router.post(`/app/bookings/${props.booking.id}/cancel`);
    }
}

function recallBooking(): void {
    const reason = recallReason.value.trim();
    if (!reason || !window.confirm('ยืนยันเรียกกลับรายการนี้เข้าสถานะรอตรวจสอบหรือไม่')) {
        return;
    }

    router.post(`/app/bookings/${props.booking.id}/recall`, {
        confirmed: true,
        reason,
    });
}
</script>

<template>
    <Head :title="`รายละเอียดคำขอ #${booking.id}`" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <div>
                <Link href="/app/bookings">← กลับประวัติการจอง</Link>
                <h1>รายละเอียดคำขอ #{{ booking.id }}</h1>
            </div>
            <BookingStatusBadge :status="booking.status" />
        </header>

        <section class="panel detail-grid">
            <div><span>ชื่อผู้จอง</span><strong>{{ booking.requester_name }}</strong></div>
            <div><span>เบอร์ติดต่อ</span><strong>{{ booking.requester_phone ?? '-' }}</strong></div>
            <div><span>เริ่มใช้งาน</span><strong>{{ new Date(booking.starts_at).toLocaleString('th-TH') }}</strong></div>
            <div><span>สิ้นสุด</span><strong>{{ new Date(booking.ends_at).toLocaleString('th-TH') }}</strong></div>
            <div><span>จำนวนผู้เข้าใช้งาน</span><strong>{{ booking.participant_count ?? '-' }}</strong></div>
            <div><span>ผลการตรวจสอบ</span><strong>{{ booking.review_reason ?? '-' }}</strong></div>
        </section>

        <section class="panel">
            <h2>ทรัพยากร</h2>
            <div class="resource-list">
                <div v-for="resource in booking.resources" :key="resource.id">
                    <strong>{{ resource.name }}</strong>
                    <span>จำนวน {{ resource.pivot.quantity }}</span>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2>ประวัติสถานะ</h2>
            <ol class="timeline">
                <li v-for="transition in booking.status_transitions" :key="transition.id">
                    <div>
                        <strong>{{ transition.from_status ?? 'เริ่มต้น' }} → {{ transition.to_status }}</strong>
                        <span>{{ transition.actor.name }} · {{ new Date(transition.created_at).toLocaleString('th-TH') }}</span>
                    </div>
                    <p v-if="transition.reason">{{ transition.reason }}</p>
                </li>
            </ol>
        </section>

        <section v-if="canCancel || canRecall" class="panel actions">
            <button v-if="canCancel" type="button" @click="cancelBooking">ยกเลิกคำขอ</button>
            <div v-if="canRecall" class="recall">
                <label>เหตุผลที่เรียกกลับ<textarea v-model="recallReason" rows="3" maxlength="2000"></textarea></label>
                <button type="button" :disabled="!recallReason.trim()" @click="recallBooking">เรียกกลับเพื่อตรวจสอบใหม่</button>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}.heading a{color:#315b7c;font-size:13px;font-weight:800;text-decoration:none}.heading h1{margin:8px 0 0;color:#17324f;font-size:32px}.panel{margin-bottom:14px;border:1px solid #dfe6ee;border-radius:16px;background:#fff;padding:20px}.panel h2{margin:0 0 14px;color:#17324f;font-size:18px}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.detail-grid div{display:grid;gap:5px}.detail-grid span{color:#718096;font-size:12px}.detail-grid strong{color:#263849}.resource-list{display:grid;gap:8px}.resource-list div{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #edf1f4;padding:9px 0}.resource-list span{color:#718096}.timeline{display:grid;gap:12px;margin:0;padding-left:20px}.timeline li{padding-left:6px}.timeline li>div{display:flex;justify-content:space-between;gap:16px}.timeline span{color:#718096;font-size:12px}.timeline p{margin:5px 0 0;color:#53677a}.actions{display:flex;gap:16px;align-items:flex-start}.actions button{border:1px solid #cfd8e1;border-radius:9px;background:#fff;color:#7c3434;padding:10px 14px;font-weight:800;cursor:pointer}.recall{display:grid;gap:8px;flex:1}.recall label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.recall textarea{border:1px solid #cfd8e1;border-radius:9px;padding:10px;font:inherit}.recall button{width:fit-content;color:#315b7c}.recall button:disabled{opacity:.5}@media(max-width:760px){.detail-grid{grid-template-columns:1fr}.timeline li>div,.actions{display:grid}}
</style>
