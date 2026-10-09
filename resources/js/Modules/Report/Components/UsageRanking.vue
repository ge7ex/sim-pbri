<script setup lang="ts">
import SectionHeader from '../../../Components/SectionHeader.vue';
interface Usage { id: number; name: string; bookings: number; hours?: number; participants?: number; average_participants?: number | null; capacity?: number | null; average_capacity_percent?: number | null; last_used_at?: string | null; type_name?: string; status?: string; quantity?: number; course_name?: string }
defineProps<{ title: string; description: string; kind: 'rooms' | 'simulators' | 'equipment' | 'courses' | 'scenarios'; items: Usage[] }>();
const number = (value: number) => Number(value).toLocaleString('th-TH', { maximumFractionDigits: 2 });
const date = (value: string) => value.slice(0, 16).replace('T', ' ');
const status = (value: string) => ({ active: 'พร้อมใช้งาน', maintenance: 'อยู่ระหว่างบำรุงรักษา', disabled: 'ปิดการใช้งาน' }[value] ?? value);
</script>
<template>
    <section class="panel usage-panel" :aria-labelledby="`usage-title-${kind}`">
        <SectionHeader :id="`usage-title-${kind}`" :title="title" :description="description" />
        <p v-if="!items.length" class="usage-empty">ยังไม่มีคำขอที่อนุมัติสำหรับข้อมูลนี้ในช่วงที่เลือก</p>
        <ol v-else class="usage-list">
            <li v-for="(item, index) in items" :key="item.id">
                <div class="usage-rank" aria-hidden="true">{{ index + 1 }}</div>
                <div class="usage-content"><h3>{{ item.name }}</h3><p v-if="item.type_name || item.course_name" class="usage-context">{{ item.type_name ?? item.course_name }}</p>
                    <dl class="usage-values"><div><dt>คำขออนุมัติ</dt><dd>{{ number(item.bookings) }} ครั้ง</dd></div><div v-if="item.hours !== undefined"><dt>ชั่วโมงในช่วง</dt><dd>{{ number(item.hours) }} ชม.</dd></div><div v-if="item.quantity !== undefined"><dt>จำนวนรวม</dt><dd>{{ number(item.quantity) }} ชิ้น</dd></div><div v-if="item.participants !== undefined"><dt>ผู้เข้าร่วมรวม</dt><dd>{{ number(item.participants) }} คน</dd></div></dl>
                    <p v-if="kind === 'rooms'" class="capacity-context">เฉลี่ย {{ item.average_participants === null ? 'ยังไม่มีข้อมูล' : number(item.average_participants ?? 0) + ' คน' }} / ความจุปัจจุบัน {{ item.capacity === null ? 'ไม่ระบุ' : number(item.capacity ?? 0) + ' คน' }}<span v-if="item.average_capacity_percent !== null && item.average_capacity_percent !== undefined"> ({{ number(item.average_capacity_percent) }}%)</span></p>
                    <p v-if="item.last_used_at" class="usage-context">ล่าสุด {{ date(item.last_used_at) }}<span v-if="item.status"> / {{ status(item.status) }}</span></p>
                </div>
            </li>
        </ol>
        <p v-if="kind === 'rooms' && items.length" class="usage-context capacity-note">เปอร์เซ็นต์คือผู้เข้าร่วมเฉลี่ย / ความจุปัจจุบันของห้อง ไม่ใช่อัตราการใช้พื้นที่ตามเวลา</p>
    </section>
</template>
<style scoped>
.usage-panel{min-width:0}.usage-empty{color:var(--sim-muted);font-size:14px;line-height:1.7;margin:0;padding:16px 0}.usage-list{list-style:none;padding:0;margin:0}.usage-list>li{display:grid;grid-template-columns:24px minmax(0,1fr);gap:12px;padding:18px 0;border-bottom:1px solid var(--sim-border)}.usage-list>li:first-child{padding-top:0}.usage-list>li:last-child{border-bottom:0;padding-bottom:0}.usage-rank{color:var(--sim-muted);font-size:14px;font-variant-numeric:tabular-nums}.usage-content h3{margin:0;color:var(--sim-navy);font-size:16px;font-weight:650;overflow-wrap:anywhere}.usage-context,.capacity-context{color:var(--sim-muted);font-size:13px;line-height:1.6;margin:8px 0 0}.usage-values{display:flex;flex-wrap:wrap;gap:16px;margin:12px 0 0}.usage-values dt{font-size:12px;color:var(--sim-muted)}.usage-values dd{margin:4px 0 0;color:var(--sim-text);font-size:15px;font-weight:600;font-variant-numeric:tabular-nums}.capacity-note{margin-top:20px}
</style>
