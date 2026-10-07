<script setup lang="ts">
import { computed, reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { formatBookingDateTime } from '../../../Support/dateTime';
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
const eventDays = computed(() => {
    const groups = new Map<string, CalendarEvent[]>();
    for (const event of props.events) {
        const day = new Date(event.starts_at).toLocaleDateString('th-TH', { dateStyle: 'full' });
        groups.set(day, [...(groups.get(day) ?? []), event]);
    }
    return Array.from(groups, ([day, events]) => ({ day, events }));
});
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

        <section class="schedule" aria-label="ตารางการใช้งานตามวัน">
            <section v-for="group in eventDays" :key="group.day" class="schedule-day"><h2>{{ group.day }}</h2>
            <article v-for="(event, index) in group.events" :key="event.id ?? `private-${index}`" class="event">
                <div class="time">
                    <strong>{{ formatBookingDateTime(event.starts_at) }}</strong>
                    <span>ถึง {{ formatBookingDateTime(event.ends_at) }}</span>
                </div>
                <div class="detail">
                    <strong>{{ event.title }}</strong>
                    <span v-if="event.resources">{{ event.resources.map((item) => item.name).join(', ') }}</span>
                </div>
                <BookingStatusBadge v-if="event.status" :status="event.status" />
                <span v-else class="private-badge">ข้อมูลปกปิด</span>
            </article>
            </section>
            <p v-if="events.length === 0" class="empty">ไม่พบรายการในช่วงเวลาที่เลือก</p>
        </section>
    </AppLayout>
</template>

<style scoped>
.heading{margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0}.filters{display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin-bottom:16px;border:1px solid var(--sim-border);border-radius:14px;background:#fff;padding:16px}.filters label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.filters input{min-height:40px}.filters button{min-height:40px;border:0;border-radius:9px;background:var(--sim-blue);color:#fff;padding:0 15px;font-weight:800}.schedule{display:grid;gap:10px}.event{display:grid;grid-template-columns:minmax(190px,.8fr) minmax(220px,1.4fr) auto;gap:18px;align-items:center;border:1px solid var(--sim-border);border-radius:14px;background:#fff;padding:16px}.time,.detail{display:grid;gap:4px}.time span,.detail span{color:var(--sim-muted);font-size:12px}.private-badge{border-radius:999px;background:#eef2f6;color:var(--sim-muted);padding:5px 9px;font-size:12px;font-weight:800}.empty{padding:42px;text-align:center;color:var(--sim-muted)}@media(max-width:720px){.event{grid-template-columns:1fr}}
.schedule-day{display:grid;gap:10px}.schedule-day h2{font-size:16px;font-weight:800;margin:12px 0 4px}.schedule .empty{background:#fff;border:1px solid var(--sim-border);border-radius:16px}</style>
