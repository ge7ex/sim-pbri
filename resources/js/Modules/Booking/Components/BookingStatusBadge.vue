<script setup lang="ts">
import { bookingStatusLabel } from '../../../Support/bookingStatus';
const props = defineProps<{
    status: string;
}>();

</script>

<template>
    <span class="status-badge" :data-status="props.status">
        {{ bookingStatusLabel(props.status) }}
    </span>
</template>
