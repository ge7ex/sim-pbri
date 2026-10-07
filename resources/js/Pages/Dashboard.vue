<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import PageHeader from '../Components/PageHeader.vue';
import MetricCard from '../Components/MetricCard.vue';
import BookingStatusBadge from '../Modules/Booking/Components/BookingStatusBadge.vue';

interface RecentBooking { id: number; requester_name: string; rooms: string[]; scenario_name: string | null; starts_at: string; ends_at: string; status: string }
const props = defineProps<{ dashboard: { scope_label: string; summary: { total: number; pending: number; approved: number; rejected: number }; recent_bookings: RecentBooking[] } }>();
const page = usePage<{ auth: { user: any; permissions: string[] } }>();
const metrics = [ { key: 'total', label: 'คำขอทั้งหมด', helper: 'รวมทุกสถานะ' }, { key: 'pending', label: 'รอตรวจสอบ', helper: 'รอเจ้าหน้าที่พิจารณา' }, { key: 'approved', label: 'อนุมัติแล้ว', helper: 'ได้รับอนุมัติให้ใช้งาน' }, { key: 'rejected', label: 'ไม่อนุมัติ', helper: 'ดูเหตุผลในรายละเอียดคำขอ' } ] as const;
function dateLabel(value: string): string { return new Date(value).toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' }); }
</script>
<template>
    <Head title="ภาพรวมระบบ" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader eyebrow="Dashboard" title="ภาพรวมระบบจองศูนย์ Simulation" :description="`${page.props.auth.user.name} · ${page.props.auth.user.role_label} · ${page.props.auth.user.college?.name ?? ''}`">
            <Link v-if="page.props.auth.permissions.includes('booking.create')" class="primary" href="/app/bookings/create">+ สร้างคำขอจอง</Link>
        </PageHeader>
        <p class="scope-caption">{{ dashboard.scope_label }} · จำนวนทั้งหมดรวมรายการที่ยกเลิก</p>
        <section class="metric-grid" aria-label="สรุปสถานะคำขอ"><MetricCard v-for="metric in metrics" :key="metric.key" :label="metric.label" :value="dashboard.summary[metric.key]" :helper="metric.helper" /></section>
        <section class="panel" aria-labelledby="recent-title">
            <div class="panel-header"><div><h2 id="recent-title">รายการคำขอล่าสุด</h2><p>แสดงสูงสุด 8 รายการ ตามเวลาสร้างคำขอ</p></div><Link href="/app/bookings" class="button-secondary">ดูประวัติการจอง</Link></div>
            <div v-if="dashboard.recent_bookings.length" class="table-wrap" tabindex="0" role="region" aria-label="รายการคำขอล่าสุด เลื่อนแนวนอนได้">
                <table><caption class="sr-only">{{ dashboard.scope_label }}</caption><thead><tr><th scope="col">รหัสคำขอ</th><th scope="col">ผู้ขอจอง</th><th scope="col">ห้อง</th><th scope="col">Scenario</th><th scope="col">วันเวลา</th><th scope="col">สถานะ</th></tr></thead>
                    <tbody><tr v-for="booking in dashboard.recent_bookings" :key="booking.id"><td><Link :href="`/app/bookings/${booking.id}`">#{{ booking.id }}</Link></td><td>{{ booking.requester_name }}</td><td>{{ booking.rooms.join(', ') || 'ไม่ได้ระบุ' }}</td><td>{{ booking.scenario_name ?? 'ไม่ได้ระบุ' }}</td><td><time :datetime="booking.starts_at">{{ dateLabel(booking.starts_at) }}</time><span class="end-time">ถึง {{ dateLabel(booking.ends_at) }}</span></td><td><BookingStatusBadge :status="booking.status" /></td></tr></tbody>
                </table>
            </div>
            <div v-else class="empty"><p>ยังไม่มีคำขอในขอบเขตนี้</p><Link v-if="page.props.auth.permissions.includes('booking.create')" href="/app/bookings/create">สร้างคำขอจองแรก</Link></div>
        </section>
    </AppLayout>
</template>
<style scoped>
.scope-caption{margin:0 0 12px;color:var(--sim-muted);font-size:13px}.end-time{display:block;color:var(--sim-muted);font-size:12px}table{min-width:720px}.page-header-actions{flex-shrink:0}
</style>
