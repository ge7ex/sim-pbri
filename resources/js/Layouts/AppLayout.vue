<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

interface UserSummary {
    name: string;
    email: string;
    role_label: string | null;
    college: { id: number; name: string } | null;
}

const props = defineProps<{ user: UserSummary; permissions: string[] }>();
const page = usePage();
const menuOpen = ref(false);
const menuToggle = ref<HTMLButtonElement | null>(null);
function closeMenu(): void { menuOpen.value = false; menuToggle.value?.focus(); }
watch(() => page.url, () => { menuOpen.value = false; });

const items = computed(() => {
    const granted = new Set(props.permissions);

    return [
        { label: 'หน้าหลัก', href: '/app', permission: null },
        { label: 'ประวัติการจอง', href: '/app/bookings', permission: 'booking.view' },
        { label: 'ส่งคำขอจอง', href: '/app/bookings/create', permission: 'booking.create' },
        { label: 'ปฏิทินการใช้งาน', href: '/app/calendar', permission: 'booking.view' },
        { label: 'ตรวจสอบคำขอ', href: '/app/review', permission: 'booking.approve' },
        { label: 'หุ่นจำลองและทรัพย์สิน', href: '/app/simulators', permission: 'simulator.view' },
        { label: 'ทรัพยากร SIM', href: '/app/resources', permission: 'sim-resource.view' },
        { label: 'รายวิชาและสถานการณ์', href: '/app/scenarios', permission: 'scenario.view' },
    ].filter((item) => item.permission === null || granted.has(item.permission));
});

function isActive(href: string): boolean {
    const path = page.url.split('?')[0].replace(/\/$/, '');
    if (href === '/app') return path === '/app';
    if (href === '/app/bookings') return path === href || /^\/app\/bookings\/\d+$/.test(path);
    return path === href || path.startsWith(href + '/');
}

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <div class="app-shell" lang="th">
        <a class="app-skip-link" href="#app-content">ข้ามไปเนื้อหา</a>
        <aside class="app-sidebar" aria-label="เมนูระบบ">
            <div class="app-mobile-row">
                <Link class="app-brand" href="/app">
                    <span class="app-brand-mark">SIM</span>
                    <span class="app-brand-copy"><strong>SIM PBRI</strong><small>ระบบจองศูนย์ Simulation</small></span>
                </Link>
                <button ref="menuToggle" class="app-menu-toggle" type="button" :aria-expanded="menuOpen" aria-controls="app-navigation" @click="menuOpen = !menuOpen">{{ menuOpen ? 'ปิดเมนู' : 'เมนู' }}</button>
            </div>
            <nav id="app-navigation" class="app-nav" :class="{ 'is-open': menuOpen }" aria-label="งานในระบบ" @keydown.esc="closeMenu">
                <Link v-for="item in items" :key="item.href" :href="item.href" class="app-nav-link" :class="{ 'is-active': isActive(item.href) }" :aria-current="isActive(item.href) ? 'page' : undefined">{{ item.label }}</Link>
            </nav>
            <div class="app-context" :class="{ 'is-open': menuOpen }">
                <span>{{ user.role_label }}</span><strong>{{ user.name }}</strong><p>{{ user.college?.name ?? 'ยังไม่ได้กำหนดหน่วยงาน' }}</p>
            </div>
            <button type="button" class="app-logout" :class="{ 'is-open': menuOpen }" @click="logout">ออกจากระบบ</button>
        </aside>
        <main id="app-content" class="app-main" tabindex="-1"><slot /></main>
    </div>
</template>
