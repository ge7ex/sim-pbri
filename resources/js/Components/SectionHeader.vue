<script setup lang="ts">
withDefaults(defineProps<{ title: string; description?: string; eyebrow?: string; id?: string; level?: 2 | 3 }>(), { level: 2 });
</script>
<template>
    <header class="section-header" :class="{ 'section-header-local': level === 3 }">
        <div class="section-header-copy">
            <p v-if="eyebrow" class="section-eyebrow">{{ eyebrow }}</p>
            <component :is="level === 3 ? 'h3' : 'h2'" :id="id">{{ title }}</component>
            <p v-if="description" class="section-description">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="section-header-actions"><slot name="actions" /></div>
    </header>
</template>
