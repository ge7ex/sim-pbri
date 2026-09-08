<script setup lang="ts">
import PublicLayout from '../Layouts/PublicLayout.vue';
import HomePage from '../Modules/Public/Pages/HomePage.vue';

function openHome(): void {
    window.location.href = '/';
}

function openLogin(): void {
    window.location.href = '/login';
}
</script>

<template>
    <PublicLayout
        @open-home="openHome"
        @open-login="openLogin"
    >
        <HomePage />
    </PublicLayout>
</template>