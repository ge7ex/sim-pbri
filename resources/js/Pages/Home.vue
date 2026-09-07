<script setup lang="ts">
defineProps<{
    laravelVersion: string;
}>();
</script>

<template>
    <main>
        <h1>SIM PBRI</h1>

        <p>
            Laravel + Inertia + Vue integration is working.
        </p>

        <p>
            Laravel version: {{ laravelVersion }}
        </p>
    </main>
</template>