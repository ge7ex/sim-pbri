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
    resources: Array<{ id: number; name: string; kind: string }>;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface SharedProps {
    auth: {
        user: any;
        permissions: string[];
    };
}

const props = defineProps<{
    bookings: Pagination<BookingRow>;
    filters: {
        status?: string | null;
        date_from?: string | null;
        date_to?: string | null;
    };
}>();

const page = usePage<SharedProps>();
const filters = reactive({
    status: props.filters.status ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

function applyFilters(): void {
    router.get('/app/bookings', filters, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="ประวัติการจอง" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <div>
                <p>Booking History</p>
                <h1>ประวัติการจอง</h1>
            </div>
            <Link v-if="page.props.auth.permissions.includes('booking.create')" class="primary" href="/app/bookings/create">
                ส่งคำขอจอง
            </Link>
        </header>

        <form class="filters" @submit.prevent="applyFilters">
            <label>สถานะ
                <select v-model="filters.status">
                    <option value="">ทั้งหมด</option>
                    <option value="pending">รอตรวจสอบ</option>
                    <option value="approved">อนุมัติแล้ว</option>
                    <option value="rejected">ไม่อนุมัติ</option>
                    <option value="cancelled">ยกเลิก</option>
                </select>
            </label>
            <label>จากวันที่<input v-model="filters.date_from" type="date"></label>
            <label>ถึงวันที่<input v-model="filters.date_to" type="date"></label>
            <button type="submit">กรองรายการ</button>
        </form>

        <section class="panel">
            <div v-if="bookings.data.length" class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อผู้จอง</th>
                            <th>ทรัพยากร</th>
                            <th>ช่วงเวลา</th>
                            <th>สถานะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in bookings.data" :key="booking.id">
                            <td>{{ booking.requester_name }}</td>
                            <td>{{ booking.resources.map((item) => item.name).join(', ') || '-' }}</td>
                            <td>
                                <time>{{ new Date(booking.starts_at).toLocaleString('th-TH') }}</time>
                                <span>ถึง {{ new Date(booking.ends_at).toLocaleString('th-TH') }}</span>
                            </td>
                            <td><BookingStatusBadge :status="booking.status" /></td>
                            <td><Link :href="`/app/bookings/${booking.id}`">ดูรายละเอียด</Link></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="empty">ยังไม่มีรายการจองตามเงื่อนไขที่เลือก</div>

            <div v-if="bookings.last_page > 1" class="pager">
                <Link v-if="bookings.prev_page_url" :href="bookings.prev_page_url">ก่อนหน้า</Link>
                <span>หน้า {{ bookings.current_page }} / {{ bookings.last_page }}</span>
                <Link v-if="bookings.next_page_url" :href="bookings.next_page_url">ถัดไป</Link>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:20px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em}.heading h1{margin:5px 0 0;color:#17324f;font-size:34px}.primary{border-radius:10px;background:#17324f;color:#fff;padding:11px 16px;font-weight:800;text-decoration:none}.filters{display:grid;grid-template-columns:1.2fr 1fr 1fr auto;gap:12px;align-items:end;margin-bottom:16px;border:1px solid #dfe6ee;border-radius:14px;background:#fff;padding:16px}.filters label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.filters input,.filters select{min-height:40px;border:1px solid #cfd8e1;border-radius:9px;background:#fff;padding:0 10px}.filters button{min-height:40px;border:0;border-radius:9px;background:#315b7c;color:#fff;padding:0 15px;font-weight:800}.panel{border:1px solid #dfe6ee;border-radius:16px;background:#fff;overflow:hidden}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:15px;text-align:left;border-bottom:1px solid #edf1f4;vertical-align:top}th{background:#f7f9fb;color:#66788a;font-size:12px}td{color:#263849;font-size:14px}td time,td span{display:block}td span{margin-top:3px;color:#718096;font-size:12px}td a{color:#315b7c;font-weight:800;text-decoration:none}.empty{padding:42px;text-align:center;color:#718096}.pager{display:flex;justify-content:center;gap:16px;padding:16px;color:#66788a}.pager a{color:#315b7c;font-weight:800;text-decoration:none}@media(max-width:760px){.filters{grid-template-columns:1fr}.heading{align-items:flex-start;flex-direction:column}}
</style>
