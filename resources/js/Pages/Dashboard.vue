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
            college: {
                id: number;
                name: string;
            } | null;
            has_access_profile: boolean;
        };
    };
}

const page = usePage<SharedProps>();
</script>

<template>
    <Head title="หน้าหลักระบบ" />

    <AppLayout :user="page.props.auth.user">
        <section class="dashboard-intro" aria-labelledby="dashboard-title">
            <span>Authenticated workspace</span>
            <h1 id="dashboard-title">ระบบบริหารจัดการ SIM PBRI</h1>
            <p>
                บัญชีนี้ได้รับการกำหนดบทบาทและวิทยาลัยสำหรับเข้าใช้งานระบบแล้ว
                เมนูและความสามารถของแต่ละโมดูลจะเปิดตามสิทธิ์ที่กำหนดในขั้นถัดไป
            </p>
        </section>
    </AppLayout>
</template>

<style scoped>
.dashboard-intro {
    max-width: 760px;
    padding: 34px;
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05);
}

.dashboard-intro span {
    color: #1f4f7a;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.dashboard-intro h1 {
    margin: 10px 0 12px;
    color: #0f2742;
    font-size: clamp(30px, 4vw, 46px);
    line-height: 1.12;
    letter-spacing: -0.035em;
}

.dashboard-intro p {
    margin: 0;
    color: #64748b;
    font-size: 16px;
    line-height: 1.8;
}
</style>
