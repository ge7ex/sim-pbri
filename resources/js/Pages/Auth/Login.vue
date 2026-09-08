<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit(): void {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="เข้าสู่ระบบ" />

    <main class="login-page">
        <section class="login-shell" aria-labelledby="login-title">
            <div class="login-intro">
                <span class="login-kicker">SIM PBRI</span>
                <h1 id="login-title">เข้าสู่ระบบ SIM PBRI</h1>
                <p>
                    สำหรับบุคลากร อาจารย์ และเจ้าหน้าที่ที่ได้รับสิทธิ์
                    เพื่อเข้าใช้งานระบบบริหารจัดการทรัพยากรและการจองห้องปฏิบัติการ Simulation
                </p>

                <div class="login-role-grid" aria-label="ขอบเขตการใช้งาน">
                    <div>
                        <strong>ผู้ขอใช้งาน</strong>
                        <span>ส่งคำขอและติดตามสถานะการจองตามสิทธิ์ที่ได้รับ</span>
                    </div>
                    <div>
                        <strong>Staff / Admin</strong>
                        <span>ตรวจสอบคำขอและจัดการทรัพยากรตามสิทธิ์ของผู้ใช้งาน</span>
                    </div>
                </div>
            </div>

            <form class="login-card" novalidate @submit.prevent="submit">
                <div class="login-card-header">
                    <span>บัญชีผู้ใช้งาน</span>
                    <h2>เข้าสู่ระบบ</h2>
                    <p>กรอกอีเมลและรหัสผ่านที่ได้รับจากผู้ดูแลระบบ</p>
                </div>

                <label class="login-field">
                    <span>อีเมล</span>
                    <input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="username"
                        inputmode="email"
                        maxlength="255"
                        required
                        autofocus
                        :aria-invalid="Boolean(form.errors.email)"
                        :aria-describedby="form.errors.email ? 'email-error' : undefined"
                    >
                    <small v-if="form.errors.email" id="email-error" class="login-error">
                        {{ form.errors.email }}
                    </small>
                </label>

                <label class="login-field">
                    <span>รหัสผ่าน</span>
                    <input
                        v-model="form.password"
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        :aria-invalid="Boolean(form.errors.password)"
                        :aria-describedby="form.errors.password ? 'password-error' : undefined"
                    >
                    <small v-if="form.errors.password" id="password-error" class="login-error">
                        {{ form.errors.password }}
                    </small>
                </label>

                <button class="login-submit-button" type="submit" :disabled="form.processing">
                    {{ form.processing ? 'กำลังเข้าสู่ระบบ…' : 'เข้าสู่ระบบ' }}
                </button>

                <Link class="login-back-link" href="/">
                    กลับหน้าเว็บไซต์หลัก
                </Link>
            </form>
        </section>
    </main>
</template>

<style scoped>
.login-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 48px 20px;
    background:
        radial-gradient(circle at 12% 18%, rgba(31, 79, 122, 0.14), transparent 28rem),
        linear-gradient(180deg, #ffffff 0%, #edf4fb 100%);
    color: #172033;
}

.login-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(320px, 430px);
    gap: 56px;
    align-items: center;
    width: min(1080px, 100%);
}

.login-intro {
    display: grid;
    gap: 20px;
}

.login-kicker {
    width: fit-content;
    padding: 8px 12px;
    border-radius: 999px;
    background: #e0ecf8;
    color: #0f2742;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.06em;
}

.login-intro h1 {
    margin: 0;
    color: #0f2742;
    font-size: clamp(38px, 5vw, 62px);
    line-height: 1.05;
    letter-spacing: -0.04em;
}

.login-intro > p {
    max-width: 650px;
    margin: 0;
    color: #475569;
    font-size: 17px;
    line-height: 1.8;
}

.login-role-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-top: 8px;
}

.login-role-grid div {
    display: grid;
    gap: 6px;
    padding: 18px;
    border: 1px solid #dbeafe;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.72);
}

.login-role-grid strong {
    color: #0f2742;
}

.login-role-grid span {
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.login-card {
    display: grid;
    gap: 20px;
    padding: 30px;
    border: 1px solid #dbeafe;
    border-radius: 28px;
    background: #ffffff;
    box-shadow: 0 28px 80px rgba(15, 39, 66, 0.14);
}

.login-card-header span {
    color: #1f4f7a;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.login-card-header h2 {
    margin: 6px 0 4px;
    color: #0f2742;
    font-size: 30px;
}

.login-card-header p {
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}

.login-field {
    display: grid;
    gap: 8px;
    color: #334155;
    font-size: 14px;
    font-weight: 800;
}

.login-field input {
    min-height: 48px;
    width: 100%;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    color: #172033;
    padding: 0 14px;
    font: inherit;
    font-weight: 500;
    outline: none;
}

.login-field input:focus {
    border-color: #1f4f7a;
    box-shadow: 0 0 0 3px rgba(31, 79, 122, 0.12);
}

.login-field input[aria-invalid="true"] {
    border-color: #b91c1c;
}

.login-error {
    color: #b91c1c;
    font-size: 13px;
    font-weight: 700;
}

.login-submit-button {
    min-height: 50px;
    border: 0;
    border-radius: 999px;
    background: #0f2742;
    color: #ffffff;
    font-size: 15px;
    font-weight: 900;
    cursor: pointer;
}

.login-submit-button:disabled {
    cursor: wait;
    opacity: 0.65;
}

.login-back-link {
    display: inline-flex;
    justify-content: center;
    color: #1f4f7a;
    font-size: 14px;
    font-weight: 800;
    text-decoration: none;
}

.login-back-link:hover {
    text-decoration: underline;
}

@media (max-width: 860px) {
    .login-shell {
        grid-template-columns: 1fr;
        gap: 34px;
    }
}

@media (max-width: 560px) {
    .login-page {
        align-items: start;
        padding: 30px 14px;
    }

    .login-role-grid {
        grid-template-columns: 1fr;
    }

    .login-card {
        padding: 24px 20px;
        border-radius: 22px;
    }
}
</style>
