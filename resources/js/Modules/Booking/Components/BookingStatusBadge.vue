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
