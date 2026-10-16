<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PageHeader from '../../../Components/PageHeader.vue';
import SectionHeader from '../../../Components/SectionHeader.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import { formatBookingDateTime } from '../../../Support/dateTime';
import BookingStatusBadge from '../Components/BookingStatusBadge.vue';

interface CalendarEvent {
    id?: number;
    starts_at: string;
    ends_at: string;
    title: string;
    status?: string;
    resources?: Array<{ id: number; name: string; kind: string; is_historical_snapshot: boolean }>;
}

interface SharedProps { auth: { user: any; permissions: string[] } }

const props = defineProps<{
    events: CalendarEvent[];
    filters: { date_from?: string | null; date_to?: string | null };
}>();

const page = usePage<SharedProps>();
const view = ref<'calendar' | 'list'>('calendar');
const bangkokDay = (value: string) => new Date(value).toLocaleDateString('en-CA', { timeZone: 'Asia/Bangkok' });
const today = bangkokDay(new Date().toISOString());
const month = ref((props.filters.date_from || props.filters.date_to || today).slice(0, 7));
const selectedDay = ref('');
watch(() => props.filters, (value) => {
    filters.date_from = value.date_from ?? '';
    filters.date_to = value.date_to ?? '';
    month.value = (value.date_from || value.date_to || today).slice(0, 7);
    selectedDay.value = '';
});
const monthLabel = computed(() => new Date(`${month.value}-01T12:00:00+07:00`).toLocaleDateString('th-TH', { month: 'long', year: 'numeric', timeZone: 'Asia/Bangkok' }));
const calendarDays = computed(() => {
    const [year, number] = month.value.split('-').map(Number);
    const offset = new Date(Date.UTC(year, number - 1, 1)).getUTCDay();
    const count = new Date(Date.UTC(year, number, 0)).getUTCDate();
    return Array.from({ length: Math.ceil((offset + count) / 7) * 7 }, (_, index) => {
        const day = index - offset + 1;
        if (day < 1 || day > count) return null;
        const date = `${month.value}-${String(day).padStart(2, '0')}`;
        const events = props.events.filter((event) => {
            // End is exclusive: an event ending at midnight does not occupy the following day.
            return new Date(event.starts_at).getTime() <= new Date(`${date}T23:59:59.999+07:00`).getTime() && new Date(event.ends_at).getTime() > new Date(`${date}T00:00:00+07:00`).getTime();
        });
        return { date, day, events };
    });
});
const selectedEvents = computed(() => calendarDays.value.find((day) => day?.date === selectedDay.value)?.events ?? []);
function changeMonth(delta: number): void {
    const [year, number] = month.value.split('-').map(Number);
    const date = new Date(Date.UTC(year, number - 1 + delta, 1));
    const first = date.toISOString().slice(0, 10);
    const last = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).toISOString().slice(0, 10);
    router.get('/app/calendar', { date_from: first, date_to: last }, { preserveState: true });
}
const eventDays = computed(() => {
    const groups = new Map<string, CalendarEvent[]>();
    for (const event of props.events) {
        const day = new Date(event.starts_at).toLocaleDateString('th-TH', { dateStyle: 'full', timeZone: 'Asia/Bangkok' });
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
        <PageHeader title="ปฏิทินการใช้งาน" description="รายการของวิทยาลัยอื่นแสดงเฉพาะช่วงเวลาและข้อความ “ถูกจองแล้ว”"></PageHeader>

        <section class="section" aria-labelledby="schedule-title"><SectionHeader id="schedule-title" title="ตารางการใช้ห้องและทรัพยากร" description="ดูภาพรวมรายเดือน หรือสลับเป็นรายการเพื่ออ่านทุกคำขอในช่วงวันที่" />
        <div class="view-switch" role="group" aria-label="มุมมองการจอง">
            <button type="button" :aria-pressed="view === 'calendar'" @click="view = 'calendar'">ปฏิทิน</button>
            <button type="button" :aria-pressed="view === 'list'" @click="view = 'list'">รายการ</button>
        </div>
        <form class="filters" @submit.prevent="applyFilters">
            <label>จากวันที่<input v-model="filters.date_from" type="date" :max="filters.date_to || undefined"></label>
            <label>ถึงวันที่<input v-model="filters.date_to" type="date" :min="filters.date_from || undefined"></label>
            <button type="submit">แสดงช่วงเวลา</button>
        </form>

        <section v-if="view === 'calendar'" class="month-panel" aria-label="ปฏิทินรายเดือน">
            <div class="month-navigation"><button type="button" aria-label="เดือนก่อนหน้า" @click="changeMonth(-1)">ก่อนหน้า</button><h3 aria-live="polite">{{ monthLabel }}</h3><button type="button" aria-label="เดือนถัดไป" @click="changeMonth(1)">ถัดไป</button></div>
            <p class="calendar-hint">แสดงเดือนของวันที่เริ่มต้น เลือกวันเพื่ออ่านรายการ หากเลือกหลายเดือนให้สลับมุมมองรายการเพื่อดูครบช่วง</p>
            <div class="month-grid">
                <span v-for="weekday in ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.']" :key="weekday" class="weekday">{{ weekday }}</span>
                <template v-for="(day, index) in calendarDays" :key="index">
                    <button v-if="day" type="button" class="calendar-day" :class="{ today: day.date === today, selected: day.date === selectedDay }" :aria-current="day.date === today ? 'date' : undefined" :aria-pressed="day.date === selectedDay" :aria-label="`${day.date} · ${day.events.length} รายการ`" @click="selectedDay = day.date">
                        <strong>{{ day.day }}</strong><span v-if="day.events.length" class="event-count">{{ day.events.length }} รายการ</span><span v-for="(event, i) in day.events.slice(0, 2)" :key="i" class="day-preview">{{ event.title }}</span>
                    </button>
                    <span v-else class="calendar-blank" aria-hidden="true"></span>
                </template>
            </div>
            <section v-if="selectedDay" class="day-agenda" aria-live="polite"><h3>รายการวันที่ {{ selectedDay }}</h3>
                <article v-for="(event, i) in selectedEvents" :key="i" class="event"><div class="time"><strong>{{ formatBookingDateTime(event.starts_at) }}</strong><span>ถึง {{ formatBookingDateTime(event.ends_at) }}</span></div><div class="detail"><strong>{{ event.title }}</strong><span v-if="event.resources">{{ event.resources.map(item => item.name + (item.is_historical_snapshot ? ' · ณ เวลาส่งคำขอ' : '')).join(', ') }}</span></div><BookingStatusBadge v-if="event.status" :status="event.status" /><span v-else class="private-badge">ข้อมูลปกปิด</span></article>
                <p v-if="!selectedEvents.length" class="empty">ไม่มีรายการในวันนี้</p>
            </section>
        </section>
        <section v-else class="schedule" aria-label="ตารางการใช้งานตามวัน">
            <section v-for="group in eventDays" :key="group.day" class="schedule-day"><h3>{{ group.day }}</h3>
            <article v-for="(event, index) in group.events" :key="event.id ?? `private-${index}`" class="event">
                <div class="time">
                    <strong>{{ formatBookingDateTime(event.starts_at) }}</strong>
                    <span>ถึง {{ formatBookingDateTime(event.ends_at) }}</span>
                </div>
                <div class="detail">
                    <strong>{{ event.title }}</strong>
                    <span v-if="event.resources">{{ event.resources.map((item) => item.name + (item.is_historical_snapshot ? ' · ณ เวลาส่งคำขอ' : '')).join(', ') }}</span>
                </div>
                <BookingStatusBadge v-if="event.status" :status="event.status" />
                <span v-else class="private-badge">ข้อมูลปกปิด</span>
            </article>
            </section>
            <p v-if="events.length === 0" class="empty">ไม่พบรายการในช่วงเวลาที่เลือก</p>
        </section></section>
    </AppLayout>
</template>

<style scoped>
.view-switch{display:flex;gap:6px;margin:0 0 16px}.view-switch button,.month-navigation button{min-height:44px;padding:9px 16px;border:1px solid var(--sim-border);background:#fff;border-radius:9px;color:var(--sim-navy);font-weight:800;cursor:pointer}.view-switch button[aria-pressed=true]{background:var(--sim-navy);color:#fff}.month-panel{background:#fff;border:1px solid var(--sim-border);border-radius:14px;padding:16px}.month-navigation{display:flex;justify-content:space-between;gap:10px;align-items:center}.month-navigation h3{font-size:18px;margin:0;text-align:center}.calendar-hint{font-size:13px;color:var(--sim-muted);line-height:1.6}.month-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px}.weekday{text-align:center;padding:8px 0;font-weight:800;font-size:13px}.calendar-day,.calendar-blank{min-height:116px;border:1px solid var(--sim-border);border-radius:8px;background:var(--sim-soft);min-width:0}.calendar-day{display:flex;flex-direction:column;gap:5px;text-align:left;padding:8px;color:var(--sim-text);cursor:pointer}.calendar-day.today{border-color:var(--sim-blue)}.calendar-day.selected{background:var(--sim-tint);outline:2px solid var(--sim-blue);outline-offset:-2px}.event-count{font-size:12px;color:var(--sim-blue);font-weight:800}.day-preview{display:block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px}.calendar-blank{background:#fff;border-color:transparent}.day-agenda{margin-top:20px}.day-agenda .event{margin-top:10px}button:focus-visible{outline:3px solid #507fa7;outline-offset:2px}@media(max-width:600px){.month-panel{padding:8px}.month-grid{gap:2px}.calendar-day,.calendar-blank{min-height:72px}.calendar-day{padding:5px 2px}.day-preview{display:none}.event-count{font-size:10px}.month-navigation button{padding:8px}.month-navigation h3{font-size:16px}}

.heading{margin-bottom:20px}.heading p{margin:0}.heading h1{margin:5px 0}.filters{display:flex;flex-wrap:wrap;gap:12px;align-items:end;margin-bottom:16px;border:1px solid var(--sim-border);border-radius:14px;background:#fff;padding:16px}.filters label{display:grid;gap:6px;color:var(--sim-text);font-size:12px;font-weight:800}.filters input{min-height:44px}.filters button{min-height:44px;border:0;border-radius:9px;background:var(--sim-blue);color:#fff;padding:0 15px;font-weight:800}.schedule{display:grid;gap:10px}.event{display:grid;grid-template-columns:minmax(190px,.8fr) minmax(220px,1.4fr) auto;gap:18px;align-items:center;border:1px solid var(--sim-border);border-radius:14px;background:#fff;padding:16px}.time,.detail{display:grid;gap:4px}.time span,.detail span{color:var(--sim-muted);font-size:12px}.private-badge{border-radius:999px;background:#eef2f6;color:var(--sim-muted);padding:5px 9px;font-size:12px;font-weight:800}.empty{padding:42px;text-align:center;color:var(--sim-muted)}@media(max-width:720px){.event{grid-template-columns:1fr}}
.schedule-day{display:grid;gap:10px}.schedule-day h3{font-size:16px;font-weight:800;margin:12px 0 4px}.schedule .empty{background:#fff;border:1px solid var(--sim-border);border-radius:16px}</style>
