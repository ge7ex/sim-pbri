<script setup lang="ts">
import { reactive } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { formatBookingDateTime } from '../../../Support/dateTime';
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
            <div v-if="bookings.data.length" class="table-wrap" tabindex="0" role="region" aria-label="ประวัติการจอง เลื่อนแนวนอนได้">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">ชื่อผู้จอง</th>
                            <th scope="col">ทรัพยากร</th>
                            <th scope="col">ช่วงเวลา</th>
                            <th scope="col">สถานะ</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in bookings.data" :key="booking.id">
                            <td>{{ booking.requester_name }}</td>
                            <td>{{ booking.resources.map((item) => item.name).join(', ') || '-' }}</td>
                            <td>
                                <time>{{ formatBookingDateTime(booking.starts_at) }}</time>
                                <span>ถึง {{ formatBookingDateTime(booking.ends_at) }}</span>
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
.heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0 0}.filters{display:grid;grid-template-columns:1.2fr 1fr 1fr auto;gap:12px;align-items:end;margin-bottom:16px;border:1px solid var(--sim-border);border-radius:14px;background:#fff;padding:16px}.filters label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.filters input,.filters select{min-height:40px}.filters button{min-height:40px;border:0;border-radius:9px;background:var(--sim-blue);color:#fff;padding:0 15px;font-weight:800}.panel{overflow:hidden}.table-wrap{overflow-x:auto}th,td{padding:15px;text-align:left;border-bottom:1px solid var(--sim-border);vertical-align:top}td time,td span{display:block}td span{margin-top:3px;color:var(--sim-muted);font-size:12px}td a{color:var(--sim-blue);font-weight:800;text-decoration:none}.empty{padding:42px;text-align:center;color:var(--sim-muted)}.pager{display:flex;justify-content:center;gap:16px;padding:16px;color:var(--sim-muted)}.pager a{color:var(--sim-blue);font-weight:800;text-decoration:none}@media(max-width:760px){.filters{grid-template-columns:1fr}.heading{align-items:flex-start;flex-direction:column}}
</style>
