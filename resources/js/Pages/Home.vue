<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PublicLayout from '../Layouts/PublicLayout.vue';
import HomePage from '../Modules/Public/Pages/HomePage.vue';

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
        } | null;
    };
}

const page = usePage<SharedProps>();
const authenticated = computed(() => page.props.auth.user !== null);

function openHome(): void {
    router.visit('/');
}

function openLogin(): void {
    router.visit('/login');
}

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <PublicLayout
        :authenticated="authenticated"
        @open-home="openHome"
        @open-login="openLogin"
        @logout="logout"
    >
        <HomePage />
    </PublicLayout>
</template>
