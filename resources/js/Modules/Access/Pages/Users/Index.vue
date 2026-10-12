<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../../../Layouts/AppLayout.vue';
import PageHeader from '../../../../Components/PageHeader.vue';
import SectionHeader from '../../../../Components/SectionHeader.vue';

interface RoleOption { value: string; label: string }
interface ManagedUser {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string | null;
    is_active: boolean;
    must_change_password: boolean;
}
interface Credential { userId: number; name: string; email: string; password: string }
interface UserPageProps {
    users: { data: ManagedUser[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; total: number };
    roles: RoleOption[];
    collegeName: string;
    filters: { q: string };
}
interface JsonResult { temporary_password?: string; user_id?: number; errors?: Record<string, string[]>; message?: string }

const props = defineProps<UserPageProps>();
const page = usePage<{ auth: { user: ManagedUser; permissions: string[] }; flash: { success?: string } }>();
const createForm = useForm({ name: '', email: '', role: 'lecturer' });
const editForm = useForm({ name: '', role: 'lecturer', is_active: true });
const resetForm = useForm({ current_password: '' });
const searchForm = useForm({ q: props.filters.q });
const creating = ref(false);
const resetting = ref(false);
const editingId = ref<number | null>(null);
const resettingId = ref<number | null>(null);
const credential = ref<Credential | null>(null);
const createErrors = ref<Record<string, string[]>>({});
const resetErrors = ref<Record<string, string[]>>({});
const requestError = ref('');
const notice = ref('');

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url: string, data: Record<string, string>): Promise<JsonResult | null> {
    requestError.value = '';
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        cache: 'no-store',
        referrerPolicy: 'no-referrer',
        body: JSON.stringify(data),
    });

    let result: JsonResult;
    try {
        result = await response.json() as JsonResult;
    } catch {
        requestError.value = 'ระบบตอบกลับไม่ครบ กรุณาตรวจสอบสถานะบัญชีก่อนลองอีกครั้ง';
        return null;
    }

    if (!response.ok) {
        if (!result.errors) requestError.value = result.message ?? 'ดำเนินการไม่สำเร็จ กรุณาตรวจสอบข้อมูลแล้วลองอีกครั้ง';
        return result;
    }

    return result;
}

async function createUser(): Promise<void> {
    creating.value = true;
    createErrors.value = {};
    resetErrors.value = {};
    credential.value = null;
    notice.value = '';
    try {
        const result = await postJson('/app/users', {
            name: createForm.name,
            email: createForm.email,
            role: createForm.role,
        });
        if (result?.errors) createErrors.value = result.errors;
        if (result?.temporary_password) {
            credential.value = {
                userId: Number(result.user_id),
                name: createForm.name,
                email: createForm.email,
                password: result.temporary_password,
            };
            notice.value = 'สร้างบัญชีแล้ว รหัสผ่านชั่วคราวแสดงครั้งเดียวและต้องเปลี่ยนหลังเข้าสู่ระบบ';
            createForm.reset();
            router.reload({ only: ['users'], preserveState: true });
        }
    } catch {
        requestError.value = 'เชื่อมต่อระบบไม่ได้ กรุณาลองอีกครั้ง';
    } finally {
        creating.value = false;
    }
}

function editUser(user: ManagedUser): void {
    editingId.value = user.id;
    resettingId.value = null;
    editForm.defaults({ name: user.name, role: user.role, is_active: user.is_active });
    editForm.reset();
    editForm.clearErrors();
    requestError.value = '';
}

function saveUser(user: ManagedUser): void {
    editForm.put(`/app/users/${user.id}`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}

function searchUsers(): void {
    router.get('/app/users', { q: searchForm.q }, { preserveState: true, preserveScroll: true, replace: true });
}

function startPasswordReset(user: ManagedUser): void {
    editingId.value = null;
    resettingId.value = user.id;
    resetForm.reset();
    resetForm.clearErrors();
    resetErrors.value = {};
    requestError.value = '';
}

async function resetPassword(user: ManagedUser): Promise<void> {
    resetting.value = true;
    resetErrors.value = {};
    credential.value = null;
    notice.value = '';
    try {
        const result = await postJson(`/app/users/${user.id}/password`, {
            current_password: resetForm.current_password,
        });
        if (result?.errors) resetErrors.value = result.errors;
        if (result?.temporary_password) {
            credential.value = {
                userId: user.id,
                name: user.name,
                email: user.email,
                password: result.temporary_password,
            };
            notice.value = 'ตั้งรหัสผ่านชั่วคราวแล้ว รหัสผ่านแสดงครั้งเดียวและผู้ใช้ต้องเปลี่ยนหลังเข้าสู่ระบบ';
            resetForm.reset();
            resettingId.value = null;
        }
    } catch {
        requestError.value = 'เชื่อมต่อระบบไม่ได้ กรุณาลองอีกครั้ง';
    } finally {
        resetting.value = false;
    }
}

async function copyCredential(): Promise<void> {
    if (!credential.value) return;
    try {
        await navigator.clipboard.writeText(credential.value.password);
        notice.value = 'คัดลอกรหัสผ่านชั่วคราวแล้ว';
    } catch {
        requestError.value = 'คัดลอกอัตโนมัติไม่ได้ กรุณาเลือกและคัดลอกรหัสผ่านด้วยตนเอง';
    }
}

function clearCredential(): void {
    credential.value = null;
    notice.value = 'ซ่อนรหัสผ่านชั่วคราวแล้ว';
}

function clearOnPageHide(): void { credential.value = null; }
onMounted(() => window.addEventListener('pagehide', clearOnPageHide));
onBeforeUnmount(() => window.removeEventListener('pagehide', clearOnPageHide));
</script>

<template>
    <Head title="จัดการบุคลากร" />
    <AppLayout :user="page.props.auth.user" :permissions="page.props.auth.permissions">
        <PageHeader title="จัดการบุคลากร" :description="`บัญชีผู้ใช้ใน ${collegeName} เท่านั้น`" />

        <p v-if="page.props.flash.success" class="notice success" role="status">{{ page.props.flash.success }}</p>
        <p v-if="requestError" class="notice error" role="alert">{{ requestError }}</p>
        <p v-if="notice" class="notice" role="status" aria-live="polite">{{ notice }}</p>

        <section v-if="credential" class="panel credential-panel" aria-labelledby="credential-title">
            <SectionHeader id="credential-title" title="รหัสผ่านชั่วคราว" description="แสดงเฉพาะคำตอบนี้ ไม่เก็บในประวัติหน้าเว็บหรือส่งทางอีเมล" />
            <p><strong>{{ credential.name }}</strong> · {{ credential.email }}</p>
            <label for="temporary-password">รหัสผ่านชั่วคราว</label>
            <input id="temporary-password" :value="credential.password" type="text" readonly autocomplete="off" spellcheck="false">
            <div class="actions">
                <button class="primary" type="button" @click="copyCredential">คัดลอกรหัสผ่าน</button>
                <button class="secondary" type="button" @click="clearCredential">ซ่อนรหัสผ่าน</button>
            </div>
            <p class="section-help">ส่งให้เจ้าของบัญชีผ่านช่องทางส่วนตัวที่หน่วยงานอนุมัติ ผู้ใช้ต้องเปลี่ยนรหัสผ่านหลังเข้าสู่ระบบครั้งแรก</p>
        </section>

        <section class="panel section" aria-labelledby="create-user-title">
            <SectionHeader id="create-user-title" title="สร้างบัญชีบุคลากร" description="กำหนดสิทธิ์เฉพาะในหน่วยงานปัจจุบัน ระบบสร้างรหัสผ่านชั่วคราวและบังคับให้เปลี่ยนก่อนใช้งาน" />
            <form class="form-grid" @submit.prevent="createUser">
                <label>ชื่อบุคลากร<input v-model="createForm.name" required maxlength="255" autocomplete="name" :aria-invalid="Boolean(createErrors.name?.length)" :aria-describedby="createErrors.name?.length ? 'create-name-error' : undefined"><small v-if="createErrors.name?.[0]" id="create-name-error" class="error">{{ createErrors.name[0] }}</small></label>
                <label>อีเมลสำหรับเข้าสู่ระบบ<input v-model="createForm.email" required type="email" maxlength="255" autocomplete="email" :aria-invalid="Boolean(createErrors.email?.length)" :aria-describedby="createErrors.email?.length ? 'create-email-error' : undefined"><small v-if="createErrors.email?.[0]" id="create-email-error" class="error">{{ createErrors.email[0] }}</small></label>
                <label>บทบาท<select v-model="createForm.role" required :aria-invalid="Boolean(createErrors.role?.length)" :aria-describedby="createErrors.role?.length ? 'create-role-error' : undefined"><option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option></select><small v-if="createErrors.role?.[0]" id="create-role-error" class="error">{{ createErrors.role[0] }}</small></label>
                <p v-if="createErrors.college_id?.[0]" class="error">{{ createErrors.college_id[0] }}</p>
                <div class="form-actions"><button class="primary" type="submit" :disabled="creating">{{ creating ? 'กำลังสร้างบัญชี…' : 'สร้างบัญชี' }}</button></div>
            </form>
        </section>

        <section class="section" aria-labelledby="users-title">
            <SectionHeader id="users-title" title="บัญชีในหน่วยงาน" :description="`ทั้งหมด ${users.total} บัญชี · หน้าที่ ${users.current_page} จาก ${users.last_page}`" />
            <form class="search-form" role="search" @submit.prevent="searchUsers"><label for="user-search">ค้นหาชื่อหรืออีเมล</label><input id="user-search" v-model="searchForm.q" type="search" maxlength="100" autocomplete="off"><button class="secondary" type="submit">ค้นหา</button><button v-if="searchForm.q" class="secondary" type="button" @click="searchForm.q = ''; searchUsers()">ล้างคำค้น</button></form>
            <div v-if="users.data.length" class="account-list">
                <article v-for="user in users.data" :key="user.id" class="panel account-card">
                    <div class="account-summary">
                        <div class="account-name"><h3>{{ user.name }}</h3><p>{{ user.email }}</p></div>
                        <span class="status-badge" :data-status="user.is_active ? 'approved' : 'rejected'">{{ user.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}</span>
                    </div>
                    <dl class="account-meta"><div><dt>บทบาท</dt><dd>{{ user.role_label ?? 'ไม่ทราบบทบาท' }}</dd></div><div><dt>รหัสผ่าน</dt><dd>{{ user.must_change_password ? 'รอเปลี่ยนรหัสผ่าน' : 'ตั้งค่าแล้ว' }}</dd></div></dl>

                    <form v-if="editingId === user.id" class="edit-form" @submit.prevent="saveUser(user)">
                        <label :for="`name-${user.id}`">ชื่อบุคลากร<input :id="`name-${user.id}`" v-model="editForm.name" required maxlength="255" :aria-invalid="Boolean(editForm.errors.name)" :aria-describedby="editForm.errors.name ? `name-error-${user.id}` : undefined"><small v-if="editForm.errors.name" :id="`name-error-${user.id}`" class="error">{{ editForm.errors.name }}</small></label>
                        <label :for="`role-${user.id}`">บทบาท<select :id="`role-${user.id}`" v-model="editForm.role" :disabled="user.id === page.props.auth.user.id" :aria-invalid="Boolean(editForm.errors.role)" :aria-describedby="editForm.errors.role ? `role-error-${user.id}` : undefined"><option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option></select><small v-if="editForm.errors.role" :id="`role-error-${user.id}`" class="error">{{ editForm.errors.role }}</small></label>
                        <label class="checkbox" :for="`active-${user.id}`"><input :id="`active-${user.id}`" v-model="editForm.is_active" type="checkbox" :disabled="user.id === page.props.auth.user.id"> เปิดใช้งานบัญชี</label>
                        <div class="actions"><button class="primary" type="submit" :disabled="editForm.processing">{{ editForm.processing ? 'กำลังบันทึก…' : 'บันทึกบัญชี' }}</button><button class="secondary" type="button" :disabled="editForm.processing" @click="editingId = null">ยกเลิก</button></div>
                    </form>

                    <form v-else-if="resettingId === user.id" class="edit-form" @submit.prevent="resetPassword(user)">
                        <p>ยืนยันรหัสผ่านปัจจุบันของผู้ดูแลระบบเพื่อออก credential ใหม่</p>
                        <label :for="`current-password-${user.id}`">รหัสผ่าน Admin ปัจจุบัน<input :id="`current-password-${user.id}`" v-model="resetForm.current_password" type="password" required autocomplete="current-password" :aria-invalid="Boolean(resetErrors.current_password?.length)" :aria-describedby="resetErrors.current_password?.length ? `reset-error-${user.id}` : undefined"><small v-if="resetErrors.current_password?.[0]" :id="`reset-error-${user.id}`" class="error">{{ resetErrors.current_password[0] }}</small></label>
                        <div class="actions"><button class="primary" type="submit" :disabled="resetting">{{ resetting ? 'กำลังตั้งรหัสผ่าน…' : 'ตั้งรหัสผ่านชั่วคราว' }}</button><button class="secondary" type="button" :disabled="resetting" @click="resettingId = null; resetForm.reset()">ยกเลิก</button></div>
                    </form>

                    <div v-else class="actions"><button class="secondary" type="button" @click="editUser(user)">แก้ไขบัญชี</button><button v-if="user.id !== page.props.auth.user.id" class="secondary" type="button" @click="startPasswordReset(user)">ตั้งรหัสผ่านใหม่</button></div>
                </article>
            </div>
            <p v-else class="panel empty">ยังไม่มีบัญชีในหน่วยงานนี้</p>
            <nav v-if="users.last_page > 1" class="pagination" aria-label="หน้ารายการบัญชี"><Link v-if="users.prev_page_url" :href="users.prev_page_url" rel="prev">หน้าก่อน</Link><Link v-if="users.next_page_url" :href="users.next_page_url" rel="next">หน้าถัดไป</Link></nav>
        </section>
    </AppLayout>
</template>

<style scoped>
.section{margin-top:24px}.notice{margin:0 0 16px;padding:12px 16px;border:1px solid var(--sim-border);border-radius:10px;background:#fff}.success{color:var(--sim-success);background:var(--sim-success-bg)}.error{display:block;color:var(--sim-danger);font-size:13px}.credential-panel{display:grid;gap:10px;margin-bottom:20px;border-color:#c6d9ec;background:#f9fcff}.credential-panel p{margin:0}.credential-panel input{width:100%;font-family:ui-monospace,monospace}.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form-grid>label,.edit-form>label{display:grid;gap:6px;font-weight:700}.form-actions{grid-column:1/-1}.search-form{display:flex;align-items:end;flex-wrap:wrap;gap:8px;margin:0 0 14px}.search-form label{flex-basis:100%;font-weight:700}.search-form input{flex:1 1 240px;min-width:0;min-height:44px}.search-form button{min-height:44px}.account-list{display:grid;gap:12px}.account-card{display:grid;gap:14px}.account-summary{display:flex;align-items:center;justify-content:space-between;gap:12px}.account-name{min-width:0}.account-name h3{margin:0;font-size:17px;overflow-wrap:anywhere}.account-name p{margin:3px 0 0;color:var(--sim-muted);overflow-wrap:anywhere}.account-meta{display:flex;flex-wrap:wrap;gap:20px;margin:0}.account-meta div{display:grid;gap:3px}.account-meta dt{color:var(--sim-muted);font-size:12px}.account-meta dd{margin:0;font-weight:700}.edit-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;border-top:1px solid var(--sim-border);padding-top:16px}.edit-form>p,.edit-form .actions{grid-column:1/-1}.edit-form input:not([type=checkbox]),.edit-form select{width:100%;min-height:44px}.checkbox{display:flex;align-items:center;gap:8px;min-height:44px}.actions{display:flex;flex-wrap:wrap;gap:8px}.actions button{min-height:44px}.pagination{display:flex;justify-content:space-between;gap:12px;margin-top:16px}.pagination a{min-height:44px;display:inline-flex;align-items:center;padding:8px 12px;color:var(--sim-blue)}.pagination a:focus-visible{outline:3px solid #527da3;outline-offset:2px}.empty{text-align:center}.form-grid :focus-visible,.edit-form :focus-visible,.actions :focus-visible,.search-form :focus-visible{outline:3px solid #527da3;outline-offset:2px}
@media(max-width:760px){.form-grid,.edit-form{grid-template-columns:1fr}.form-actions,.edit-form>p,.edit-form .actions{grid-column:1}.account-summary{align-items:flex-start;flex-direction:column}.actions button{flex:1 1 auto}}
</style>
