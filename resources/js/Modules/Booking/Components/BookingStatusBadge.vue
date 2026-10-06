<script setup lang="ts">
const props = defineProps<{
    status: string;
}>();

const labels: Record<string, string> = {
    pending: 'รอตรวจสอบ',
    approved: 'อนุมัติแล้ว',
    rejected: 'ไม่อนุมัติ',
    cancelled: 'ยกเลิก',
};
</script>

<template>
    <span class="status-badge" :data-status="props.status">
        {{ labels[props.status] ?? props.status }}
    </span>
</template>

<style scoped>
.status-badge { display: inline-flex; border-radius: 999px; background: #eef2f6; color: #45586b; padding: 5px 9px; font-size: 12px; font-weight: 800; }
.status-badge[data-status="approved"] { background: #e8f6ee; color: #25623d; }
.status-badge[data-status="rejected"],.status-badge[data-status="cancelled"] { background: #f7ecec; color: #8a3a3a; }
.status-badge[data-status="pending"] { background: #fff4dd; color: #78581c; }
</style>
