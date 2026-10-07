<script setup lang="ts">
defineProps<{ label: string; value: number; helper?: string }>();
</script>
<template><article class="metric-card"><span>{{ label }}</span><strong>{{ value.toLocaleString('th-TH') }}</strong><small v-if="helper">{{ helper }}</small></article></template>
