<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

interface UserSummary {
    name: string;
    email: string;
    role_label: string | null;
    college: { id: number; name: string } | null;
}

const props = defineProps<{ user: UserSummary; permissions: string[] }>();
const page = usePage();

const items = computed(() => {
    const granted = new Set(props.permissions);

    return [
        { label: 'หน้าหลัก', href: '/app', permission: null },
        { label: 'ประวัติการจอง', href: '/app/bookings', permission: 'booking.view' },
        { label: 'ส่งคำขอจอง', href: '/app/bookings/create', permission: 'booking.create' },
        { label: 'ปฏิทินการใช้งาน', href: '/app/calendar', permission: 'booking.view' },
        { label: 'ตรวจสอบคำขอ', href: '/app/review', permission: 'booking.approve' },
        { label: 'ทรัพยากร SIM', href: '/app/resources', permission: 'sim-resource.view' },
        { label: 'รายวิชาและสถานการณ์', href: '/app/scenarios', permission: 'scenario.view' },
    ].filter((item) => item.permission === null || granted.has(item.permission));
});

function isActive(href: string): boolean {
    if (href === '/app') return page.url === '/app';
    return page.url.startsWith(href);
}

function logout(): void {
    router.post('/logout');
}
</script>

<template>
    <div class="app-shell">
        <header class="app-header">
            <Link class="app-brand" href="/app">
                <span class="app-brand-mark">SIM</span>
                <span class="app-brand-copy">
                    <strong>SIM PBRI</strong>
                    <small>Simulation Center Booking System</small>
                </span>
            </Link>
            <div class="app-user">
                <div class="app-user-copy">
                    <strong>{{ user.name }}</strong>
                    <span>{{ user.role_label }}<template v-if="user.college"> · {{ user.college.name }}</template></span>
                </div>
                <button type="button" class="app-logout" @click="logout">ออกจากระบบ</button>
            </div>
        </header>
        <div class="app-frame">
            <aside class="app-sidebar" aria-label="เมนูระบบ">
                <nav class="app-nav">
                    <Link v-for="item in items" :key="item.href" :href="item.href" class="app-nav-link" :class="{ 'is-active': isActive(item.href) }">
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="app-context">
                    <span>หน่วยงาน</span>
                    <strong>{{ user.college?.name ?? 'ยังไม่ได้กำหนด' }}</strong>
                    <small>{{ user.role_label }}</small>
                </div>
            </aside>
            <main class="app-main"><slot /></main>
        </div>
    </div>
</template>

<style scoped>
.app-shell{min-height:100vh;background:#f4f6f8;color:#172033}.app-header{position:sticky;top:0;z-index:20;display:flex;min-height:72px;align-items:center;justify-content:space-between;gap:24px;border-bottom:1px solid #dfe6ee;background:rgba(255,255,255,.96);padding:0 clamp(20px,3vw,42px);backdrop-filter:blur(10px)}.app-brand{display:inline-flex;align-items:center;gap:12px;color:#17324f;text-decoration:none}.app-brand-mark{display:grid;width:42px;height:42px;place-items:center;border-radius:12px;background:#17324f;color:#fff;font-size:12px;font-weight:900;letter-spacing:.08em}.app-brand-copy strong,.app-brand-copy small{display:block}.app-brand-copy strong{font-size:16px}.app-brand-copy small{margin-top:2px;color:#718096;font-size:11px}.app-user{display:flex;align-items:center;gap:16px}.app-user-copy{display:grid;gap:2px;text-align:right}.app-user-copy strong{color:#17324f;font-size:14px}.app-user-copy span{color:#718096;font-size:12px}.app-logout{min-height:38px;border:1px solid #cbd5df;border-radius:10px;background:#fff;color:#17324f;padding:0 14px;font-weight:700;cursor:pointer}.app-frame{display:grid;grid-template-columns:244px minmax(0,1fr);min-height:calc(100vh - 73px)}.app-sidebar{display:flex;flex-direction:column;justify-content:space-between;border-right:1px solid #dfe6ee;background:#fff;padding:28px 18px 22px}.app-nav{display:grid;gap:6px}.app-nav-link{display:flex;min-height:44px;align-items:center;border-radius:10px;color:#506274;padding:0 14px;font-size:14px;font-weight:700;text-decoration:none}.app-nav-link:hover{background:#f4f7fa;color:#17324f}.app-nav-link.is-active{background:#eaf0f5;color:#17324f}.app-context{display:grid;gap:4px;border-top:1px solid #e5ebf0;padding:18px 12px 0}.app-context span,.app-context small{color:#7a8a99;font-size:11px}.app-context strong{color:#17324f;font-size:13px}.app-main{width:min(1180px,calc(100% - 48px));margin:0 auto;padding:42px 0 56px}@media(max-width:860px){.app-frame{display:block}.app-sidebar{position:sticky;top:73px;z-index:15;display:block;overflow-x:auto;border-right:0;border-bottom:1px solid #dfe6ee;padding:10px 14px}.app-nav{display:flex;width:max-content;gap:6px}.app-context{display:none}.app-main{width:min(100% - 28px,1180px);padding-top:28px}}@media(max-width:600px){.app-header{padding:0 14px}.app-brand-copy small,.app-user-copy{display:none}.app-logout{padding:0 11px}}
</style>
