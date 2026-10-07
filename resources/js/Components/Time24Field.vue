<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ modelValue: string; label: string }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const hour = computed(() => props.modelValue.split(':')[0] ?? '');
const minute = computed(() => props.modelValue ? props.modelValue.split(':')[1] : '');
const hours = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
const minutes = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));
function changeHour(event: Event): void {
    const value = (event.target as HTMLSelectElement).value;
    emit('update:modelValue', value ? `${value}:${minute.value || '00'}` : '');
}
function changeMinute(event: Event): void {
    emit('update:modelValue', `${hour.value}:${(event.target as HTMLSelectElement).value}`);
}
</script>
<template>
    <div class="time-24-field" role="group" :aria-label="`${label} แบบ 24 ชั่วโมง`">
        <select :aria-label="`${label} ชั่วโมง`" :value="hour" required @change="changeHour"><option value="" disabled>ชั่วโมง</option><option v-for="value in hours" :key="value" :value="value">{{ value }}</option></select>
        <span aria-hidden="true">:</span>
        <select :aria-label="`${label} นาที`" :value="minute" :disabled="!hour" required @change="changeMinute"><option value="" disabled>นาที</option><option v-for="value in minutes" :key="value" :value="value">{{ value }}</option></select>
    </div>
</template>
<style scoped>
.time-24-field{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);gap:8px;align-items:center}.time-24-field select{width:100%;font-variant-numeric:tabular-nums}.time-24-field>span{color:var(--sim-muted);font-weight:800}
</style>
