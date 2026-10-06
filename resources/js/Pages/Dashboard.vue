<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
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

const page = usePage<SharedProps>();
const permissions = computed(() => new Set(page.props.auth.permissions));

const quickActions = computed(() => [
    {
        title: 'ส่งคำขอจอง',
        description: 'เริ่มสร้างคำขอใช้ห้องและทรัพยากร SIM',
        href: '/app/bookings/create',
        permission: 'booking.create',
    },
    {
        title: 'ประวัติการจอง',
        description: 'ติดตามคำขอและสถานะการดำเนินการของคุณ',
        href: '/app/bookings',
        permission: 'booking.view',
    },
    {
        title: 'ปฏิทินการใช้งาน',
        description: 'ตรวจสอบช่วงเวลาที่มีการใช้งานและถูกจองแล้ว',
        href: '/app/calendar',
        permission: 'booking.view',
    },
    {
        title: 'ตรวจสอบคำขอ',
        description: 'พิจารณาคำขอที่รอการตรวจสอบและอนุมัติ',
        href: '/app/review',
        permission: 'booking.approve',
    },
    {
        title: 'จัดการทรัพยากร',
        description: 'ดูแลข้อมูลห้องและทรัพยากรสำหรับงาน SIM',
        href: '/app/resources',
        permission: 'sim-resource.update',
    },
].filter((item) => permissions.value.has(item.permission)));
</script>

<template>
    <Head title="หน้าหลักระบบ" />

    <AppLayout
        :user="page.props.auth.user"
        :permissions="page.props.auth.permissions"
    >
        <section class="dashboard-head" aria-labelledby="dashboard-title">
            <div>
                <p class="dashboard-eyebrow">SIM PBRI</p>
                <h1 id="dashboard-title">หน้าหลักระบบ</h1>
                <p>
                    เลือกงานที่ต้องการดำเนินการ ระบบจะแสดงเฉพาะเมนูที่บัญชีนี้ได้รับสิทธิ์
                </p>
            </div>

            <div class="dashboard-identity">
                <span>กำลังใช้งานในนาม</span>
                <strong>{{ page.props.auth.user.college?.name }}</strong>
                <small>{{ page.props.auth.user.role_label }}</small>
            </div>
        </section>

        <section class="dashboard-grid" aria-label="เมนูงาน">
            <Link
                v-for="action in quickActions"
                :key="action.href"
                :href="action.href"
                class="dashboard-action"
            >
                <span class="dashboard-action-mark" aria-hidden="true"></span>
                <div>
                    <h2>{{ action.title }}</h2>
                    <p>{{ action.description }}</p>
                </div>
                <span class="dashboard-action-arrow" aria-hidden="true">→</span>
            </Link>
        </section>

        <section class="dashboard-note">
            <strong>สิทธิ์การใช้งานถูกควบคุมจากระบบ</strong>
            <p>
                เมนูที่เห็นเป็นผลจากสิทธิ์ของบทบาทที่กำหนดให้บัญชีนี้
                และทุกเส้นทางสำคัญมีการตรวจสิทธิ์ซ้ำที่ฝั่งเซิร์ฟเวอร์
            </p>
        </section>
    </AppLayout>
</template>

<style scoped>
.dashboard-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 28px;
    margin-bottom: 28px;
}

.dashboard-eyebrow {
    margin: 0 0 7px;
    color: #315b7c;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: .08em;
}

.dashboard-head h1 {
    margin: 0;
    color: #17324f;
    font-size: clamp(30px, 4vw, 44px);
    line-height: 1.15;
}

.dashboard-head > div > p:last-child {
    max-width: 620px;
    margin: 10px 0 0;
    color: #66788a;
    line-height: 1.7;
}

.dashboard-identity {
    display: grid;
    min-width: 220px;
    gap: 3px;
    border-left: 3px solid #315b7c;
    background: #fff;
    padding: 14px 18px;
}

.dashboard-identity span,
.dashboard-identity small {
    color: #718096;
    font-size: 11px;
}

.dashboard-identity strong {
    color: #17324f;
    font-size: 15px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.dashboard-action {
    display: grid;
    grid-template-columns: 12px minmax(0, 1fr) auto;
    align-items: center;
    gap: 18px;
    min-height: 132px;
    border: 1px solid #dfe6ee;
    border-radius: 16px;
    background: #fff;
    color: inherit;
    padding: 22px;
    text-decoration: none;
    transition: border-color .16s ease, transform .16s ease, box-shadow .16s ease;
}

.dashboard-action:hover {
    transform: translateY(-1px);
    border-color: #aebfce;
    box-shadow: 0 10px 24px rgba(29, 50, 72, .06);
}

.dashboard-action-mark {
    width: 8px;
    height: 38px;
    border-radius: 999px;
    background: #315b7c;
}

.dashboard-action h2 {
    margin: 0 0 6px;
    color: #17324f;
    font-size: 17px;
}

.dashboard-action p {
    margin: 0;
    color: #6a7b8c;
    font-size: 13px;
    line-height: 1.6;
}

.dashboard-action-arrow {
    color: #315b7c;
    font-size: 22px;
}

.dashboard-note {
    margin-top: 22px;
    border: 1px solid #dfe6ee;
    border-radius: 14px;
    background: #f8fafb;
    padding: 18px 20px;
}

.dashboard-note strong {
    color: #17324f;
    font-size: 14px;
}

.dashboard-note p {
    margin: 4px 0 0;
    color: #718096;
    font-size: 13px;
    line-height: 1.6;
}

@media (max-width: 760px) {
    .dashboard-head {
        align-items: stretch;
        flex-direction: column;
    }

    .dashboard-identity {
        min-width: 0;
    }

    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
