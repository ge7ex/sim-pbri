<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../../Layouts/AppLayout.vue';
import PageHeader from '../../../../Components/PageHeader.vue';
import SectionHeader from '../../../../Components/SectionHeader.vue';

const page = usePage<{ auth: { user: any; permissions: string[] }; flash: { success?: string } }>();
const form = useForm({ current_password: '', password: '', password_confirmation: '' });

function submit(): void {
    form.put('/app/account/password', { onFinish: () => form.reset('current_password', 'password', 'password_confirmation') });
}
</script>

<template>
    <Head title="เปลี่ยนรหัสผ่าน" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader title="เปลี่ยนรหัสผ่าน" description="ใช้รหัสผ่านใหม่ที่ไม่ซ้ำกับรหัสผ่านเดิมและเก็บรักษาไว้เป็นความลับ" />
        <section class="panel password-panel" aria-labelledby="password-title">
            <SectionHeader id="password-title" title="ตั้งรหัสผ่านใหม่" :description="page.props.auth.user.must_change_password ? 'ต้องเปลี่ยนรหัสผ่านชั่วคราวก่อนเข้าใช้งานระบบ' : 'ยืนยันรหัสผ่านปัจจุบันก่อนบันทึก'" />
            <form class="password-form" @submit.prevent="submit">
                <label for="current-password">รหัสผ่านปัจจุบัน<input id="current-password" v-model="form.current_password" required type="password" autocomplete="current-password" :aria-invalid="Boolean(form.errors.current_password)" :aria-describedby="form.errors.current_password ? 'current-password-error' : undefined"><small v-if="form.errors.current_password" id="current-password-error" class="error">{{ form.errors.current_password }}</small></label>
                <label for="new-password">รหัสผ่านใหม่<input id="new-password" v-model="form.password" required type="password" autocomplete="new-password" minlength="12" :aria-invalid="Boolean(form.errors.password)" :aria-describedby="form.errors.password ? 'new-password-error' : 'password-help'"><small id="password-help">อย่างน้อย 12 ตัวอักษร มีตัวพิมพ์เล็ก ตัวพิมพ์ใหญ่ และตัวเลข</small><small v-if="form.errors.password" id="new-password-error" class="error">{{ form.errors.password }}</small></label>
                <label for="password-confirmation">ยืนยันรหัสผ่านใหม่<input id="password-confirmation" v-model="form.password_confirmation" required type="password" autocomplete="new-password" :aria-invalid="Boolean(form.errors.password_confirmation)" :aria-describedby="form.errors.password_confirmation ? 'confirmation-error' : undefined"><small v-if="form.errors.password_confirmation" id="confirmation-error" class="error">{{ form.errors.password_confirmation }}</small></label>
                <p v-if="form.hasErrors" class="error" role="alert">ตรวจสอบช่องที่มีข้อความผิดพลาดก่อนบันทึก</p>
                <button class="primary" type="submit" :disabled="form.processing">{{ form.processing ? 'กำลังบันทึก…' : 'บันทึกรหัสผ่านใหม่' }}</button>
            </form>
        </section>
    </AppLayout>
</template>

<style scoped>
.password-panel{max-width:720px}.password-form{display:grid;gap:16px}.password-form label{display:grid;gap:6px;font-weight:700}.password-form input{width:100%;min-height:46px}.password-form small{color:var(--sim-muted);font-size:13px;font-weight:400}.password-form .error{color:var(--sim-danger)}.password-form button{justify-self:start;min-height:44px}.password-form :focus-visible{outline:3px solid #527da3;outline-offset:2px}
</style>
