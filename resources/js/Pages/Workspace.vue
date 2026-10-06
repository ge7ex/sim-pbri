<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            role: string | null;
            role_label: string | null;
            college: { id: number; name: string } | null;
            has_access_profile: boolean;
        };
        permissions: string[];
    };
}

defineProps<{
    section: string;
    title: string;
    description: string;
}>();

const page = usePage<SharedProps>();
</script>

<template>
    <Head :title="title" />

    <AppLayout
        :user="page.props.auth.user"
        :permissions="page.props.auth.permissions"
    >
        <section class="workspace-card" :data-section="section">
            <p class="workspace-eyebrow">SIM PBRI Workspace</p>
            <h1>{{ title }}</h1>
            <p class="workspace-description">{{ description }}</p>

            <div class="workspace-notice" role="status">
                <strong>โครงสร้างสิทธิ์พร้อมใช้งานแล้ว</strong>
                <span>ฟังก์ชันของโมดูลนี้จะเชื่อมกับข้อมูลจริงในขั้นพัฒนา Booking และ Resource ต่อไป</span>
            </div>
        </section>
    </AppLayout>
</template>

<style scoped>
.workspace-card {
    max-width: 900px;
    border: 1px solid #dfe6ee;
    border-radius: 20px;
    background: #fff;
    padding: clamp(28px, 4vw, 48px);
}

.workspace-eyebrow {
    margin: 0 0 8px;
    color: #315b7c;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: .08em;
    text-transform: uppercase;
}

h1 {
    margin: 0;
    color: #16324f;
    font-size: clamp(30px, 4vw, 44px);
    line-height: 1.15;
}

.workspace-description {
    max-width: 680px;
    margin: 14px 0 0;
    color: #66788a;
    font-size: 16px;
    line-height: 1.75;
}

.workspace-notice {
    display: grid;
    gap: 4px;
    margin-top: 32px;
    border-left: 3px solid #315b7c;
    background: #f4f7fa;
    padding: 18px 20px;
}

.workspace-notice strong {
    color: #16324f;
}

.workspace-notice span {
    color: #66788a;
    line-height: 1.65;
}
</style>
