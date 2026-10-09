<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ data: { date: string; total: number; approved: number }[] }>();
const max = computed(() => Math.max(1, ...props.data.map(item => item.total)));
const hasData = computed(() => props.data.some(item => item.total > 0));
const points = (key: 'total' | 'approved') => props.data.map((item, index) => `${60 + index / Math.max(1, props.data.length - 1) * 810},${215 - item[key] / max.value * 175}`).join(' ');
const ticks = computed(() => [...new Set([0, Math.ceil(max.value / 2), max.value])]);
const peak = computed(() => props.data.reduce((best, item) => item.total > best.total ? item : best, { date: '', total: 0, approved: 0 }));
const date = (value: string) => new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'short', timeZone: 'UTC' }).format(new Date(value + 'T00:00:00Z'));
</script>
<template>
    <p v-if="!hasData" class="trend-empty">ยังไม่มีคำขอสำหรับสร้างแนวโน้มในช่วงนี้</p>
    <template v-else>
        <div class="trend-legend"><span class="legend-all">คำขอทุกสถานะที่เลือก</span><span class="legend-approved">อนุมัติ</span></div>
        <div class="chart-scroll" tabindex="0" role="region" aria-label="กราฟแนวโน้ม เลื่อนแนวนอนได้"><svg class="trend-chart" viewBox="0 0 900 260" role="img" aria-labelledby="trend-chart-title trend-chart-desc">
            <title id="trend-chart-title">แนวโน้มจำนวนคำขอตามวัน</title><desc id="trend-chart-desc">เส้นประคือคำขอทุกสถานะที่เลือก เส้นทึบคืออนุมัติ ดูข้อมูลรายวันได้ด้านล่าง</desc>
            <g v-for="tick in ticks" :key="tick"><line x1="60" x2="870" :y1="215 - tick / max * 175" :y2="215 - tick / max * 175" class="grid-line" /><text x="45" :y="220 - tick / max * 175" text-anchor="end">{{ tick }}</text></g>
            <polyline :points="points('total')" class="series-all" /><polyline :points="points('approved')" class="series-approved" />
            <g v-if="data.length === 1"><circle cx="60" :cy="215 - data[0].total / max * 175" r="5" class="series-all" /><circle cx="60" :cy="215 - data[0].approved / max * 175" r="3" class="series-approved" /></g>
            <text x="60" y="250">{{ date(data[0].date) }}</text><text x="870" y="250" text-anchor="end">{{ date(data[data.length - 1].date) }}</text>
        </svg></div>
        <p class="trend-summary">วันที่มีคำขอสูงสุด {{ date(peak.date) }}: {{ peak.total }} คำขอ</p>
        <details class="trend-data"><summary>ดูข้อมูลรายวัน</summary><div class="table-wrap" tabindex="0" role="region" aria-label="ข้อมูลแนวโน้มรายวัน"><table><caption class="sr-only">จำนวนคำขอรายวันในช่วงที่เลือก</caption><thead><tr><th scope="col">วันที่</th><th scope="col">ทุกสถานะที่เลือก</th><th scope="col">อนุมัติ</th></tr></thead><tbody><tr v-for="item in data" :key="item.date"><th scope="row">{{ item.date }}</th><td>{{ item.total }}</td><td>{{ item.approved }}</td></tr></tbody></table></div></details>
    </template>
</template>
<style scoped>
.trend-empty{margin:0;padding:24px;color:var(--sim-muted);background:var(--sim-soft);border-radius:var(--sim-radius)}.chart-scroll{overflow-x:auto}.trend-chart{display:block;width:100%;height:auto;min-width:600px}.trend-chart text{fill:var(--sim-muted);font:18px sans-serif}.grid-line{stroke:var(--sim-border);stroke-width:1}.series-all,.series-approved{fill:none;stroke-width:3;stroke-linejoin:round;stroke-linecap:round;vector-effect:non-scaling-stroke}.series-all{stroke:var(--sim-muted);stroke-dasharray:6 6}.series-approved{stroke:var(--sim-blue)}.trend-legend{display:flex;flex-wrap:wrap;gap:24px;margin-bottom:16px;color:var(--sim-text);font-size:14px}.trend-legend span{display:flex;align-items:center;gap:8px}.trend-legend span:before{content:'';display:block;width:24px;border-top:3px solid var(--sim-blue)}.trend-legend .legend-all:before{border-top-style:dashed;border-color:var(--sim-muted)}.trend-summary{color:var(--sim-muted);font-size:14px;margin:16px 0}.trend-data summary{cursor:pointer;color:var(--sim-blue);min-height:44px;display:flex;align-items:center}.table-wrap{overflow:auto;max-height:320px}table{width:100%;border-collapse:collapse;font-size:14px;text-align:left}th,td{padding:12px;border-bottom:1px solid var(--sim-border)}.sr-only{position:absolute;width:1px;height:1px;padding:0;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}@media(max-width:760px){.trend-legend{gap:12px;font-size:13px}}
</style>
