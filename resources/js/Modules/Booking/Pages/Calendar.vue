<script setup lang="ts">
import { reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import BookingStatusBadge from '../Components/BookingStatusBadge.vue';

interface CalendarEvent {
    id?: number;
    starts_at: string;
    ends_at: string;
    title: string;
    status?: string;
    resources?: Array<{ id: number; name: string; kind: string }>;
}

interface SharedProps { auth: { user: any; permissions: string[] } }

const props = defineProps<{
    events: CalendarEvent[];
    filters: { date_from?: string | null; date_to?: string | null };
}>();

const page = usePage<SharedProps>();
const filters = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

function applyFilters(): void {
    router.get('/app/calendar', filters, {
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="ปฏิทินการใช้งาน" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <header class="heading">
            <p>Calendar</p>
            <h1>ปฏิทินการใช้งาน</h1>
            <span>รายการของวิทยาลัยอื่นจะแสดงเฉพาะช่วงเวลาและข้อความ “ถูกจองแล้ว”</span>
        </header>

        <form class="filters" @submit.prevent="applyFilters">
            <label>จากวันที่<input v-model="filters.date_from" type="date"></label>
            <label>ถึงวันที่<input v-model="filters.date_to" type="date"></label>
            <button type="submit">แสดงช่วงเวลา</button>
        </form>

        <section class="schedule">
            <article v-for="(event, index) in events" :key="event.id ?? `private-${index}`" class="event">
                <div class="time">
                    <strong>{{ new Date(event.starts_at).toLocaleString('th-TH') }}</strong>
                    <span>ถึง {{ new Date(event.ends_at).toLocaleString('th-TH') }}</span>
                </div>
                <div class="detail">
                    <strong>{{ event.title }}</strong>
                    <span v-if="event.resources">{{ event.resources.map((item) => item.name).join(', ') }}</span>
                </div>
                <BookingStatusBadge v-if="event.status" :status="event.status" />
                <span v-else class="private-badge">ข้อมูลปกปิด</span>
            </article>
            <p v-if="events.length === 0" class="empty">ไม่พบรายการในช่วงเวลาที่เลือก</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{margin-bottom:20px}.heading p{margin:0;color:#315b7c;font-size:12px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.heading h1{margin:5px 0;color:#17324f;font-size:34px}.heading span{color:#718096}.filters{display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin-bottom:16px;border:1px solid #dfe6ee;border-radius:14px;background:#fff;padding:16px}.filters label{display:grid;gap:6px;color:#526578;font-size:12px;font-weight:800}.filters input{min-height:40px;border:1px solid #cfd8e1;border-radius:9px;padding:0 10px}.filters button{min-height:40px;border:0;border-radius:9px;background:#315b7c;color:#fff;padding:0 15px;font-weight:800}.schedule{display:grid;gap:10px}.event{display:grid;grid-template-columns:minmax(190px,.8fr) minmax(220px,1.4fr) auto;gap:18px;align-items:center;border:1px solid #dfe6ee;border-radius:14px;background:#fff;padding:16px}.time,.detail{display:grid;gap:4px}.time span,.detail span{color:#718096;font-size:12px}.private-badge{border-radius:999px;background:#eef2f6;color:#66788a;padding:5px 9px;font-size:12px;font-weight:800}.empty{padding:42px;text-align:center;color:#718096}@media(max-width:720px){.event{grid-template-columns:1fr}}
</style>
