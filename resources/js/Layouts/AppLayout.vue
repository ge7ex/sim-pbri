<script setup lang="ts">
import { router } from '@inertiajs/vue3';

interface UserSummary {
    name: string;
    email: string;
    role_label: string | null;
    college: {
        id: number;
        name: string;
    } | null;
}

defineProps<{
    user: UserSummary;
}>();

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <div class="app-shell">
        <header class="app-header">
            <a class="app-brand" href="/app">
                <span class="app-brand-mark">SIM</span>
                <span>
                    <strong>SIM PBRI</strong>
                    <small>Simulation Center Booking System</small>
                </span>
            </a>

            <div class="app-user">
                <div class="app-user-copy">
                    <strong>{{ user.name }}</strong>
                    <span>
                        {{ user.role_label }}
                        <template v-if="user.college"> · {{ user.college.name }}</template>
                    </span>
                </div>

                <button type="button" class="app-logout" @click="logout">
                    ออกจากระบบ
                </button>
            </div>
        </header>

        <main class="app-main">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.app-shell {
    min-height: 100vh;
    background: #f7f9fc;
    color: #172033;
}

.app-header {
    display: flex;
    min-height: 76px;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    padding: 0 max(20px, calc((100vw - 1180px) / 2));
}

.app-brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: #0f2742;
    text-decoration: none;
}

.app-brand-mark {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    border-radius: 14px;
    background: #0f2742;
    color: #ffffff;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.08em;
}

.app-brand strong,
.app-brand small {
    display: block;
}

.app-brand strong {
    font-size: 17px;
}

.app-brand small {
    margin-top: 2px;
    color: #64748b;
    font-size: 12px;
}

.app-user {
    display: flex;
    align-items: center;
    gap: 18px;
}

.app-user-copy {
    display: grid;
    gap: 2px;
    text-align: right;
}

.app-user-copy strong {
    color: #0f2742;
    font-size: 14px;
}

.app-user-copy span {
    color: #64748b;
    font-size: 12px;
}

.app-logout {
    min-height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 999px;
    background: #ffffff;
    color: #0f2742;
    padding: 0 16px;
    font-weight: 800;
    cursor: pointer;
}

.app-main {
    width: min(1180px, calc(100% - 40px));
    margin: 0 auto;
    padding: 48px 0;
}

@media (max-width: 720px) {
    .app-header {
        align-items: flex-start;
        padding: 14px;
    }

    .app-brand small,
    .app-user-copy {
        display: none;
    }

    .app-main {
        width: min(100% - 28px, 1180px);
        padding: 32px 0;
    }
}
</style>
