<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
const props = withDefaults(defineProps<{ open: boolean; title: string; description: string; confirmLabel?: string; busy?: boolean }>(), { confirmLabel: 'ยืนยัน', busy: false });
const emit = defineEmits<{ confirm: []; close: []; closed: [] }>();
const dialog = ref<HTMLDialogElement | null>(null);
let trigger: HTMLElement | null = null;
watch(() => props.open, async (open) => {
    await nextTick();
    if (open && !dialog.value?.open) {
        trigger = document.activeElement as HTMLElement;
        dialog.value?.showModal();
    } else if (!open && dialog.value?.open) {
        dialog.value.close();
        if (trigger?.isConnected) trigger.focus();
        emit('closed');
    }
}, { immediate: true });
function containFocus(event: KeyboardEvent): void {
    const buttons = Array.from(dialog.value?.querySelectorAll<HTMLButtonElement>('button:not(:disabled)') ?? []);
    if (!buttons.length) { event.preventDefault(); dialog.value?.focus(); return; }
    const first = buttons[0];
    const last = buttons[buttons.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}
watch(() => props.busy, async (busy) => { if (busy) { await nextTick(); dialog.value?.focus(); } });
onBeforeUnmount(() => dialog.value?.close());
</script>

<template>
    <Teleport to="body">
        <dialog ref="dialog" tabindex="-1" class="confirm-dialog" @keydown.tab="containFocus" aria-labelledby="confirm-title" aria-describedby="confirm-description" :aria-busy="busy" @cancel.prevent="!busy && emit('close')">
            <h2 id="confirm-title">{{ title }}</h2>
            <p id="confirm-description">{{ description }}</p>
            <div class="confirm-actions">
                <button type="button" class="secondary" :disabled="busy" autofocus @click="emit('close')">ย้อนกลับ</button>
                <button type="button" :disabled="busy" @click="emit('confirm')">{{ busy ? 'กำลังบันทึก…' : confirmLabel }}</button>
            </div>
        </dialog>
    </Teleport>
</template>

<style scoped>
.confirm-dialog{margin:auto;max-height:calc(100dvh - 32px);overflow:auto;width:min(480px,calc(100% - 32px));box-sizing:border-box;border:1px solid var(--sim-border);border-radius:16px;padding:24px;color:var(--sim-text);background:var(--sim-surface,#fff);box-shadow:0 20px 60px #142d4540}.confirm-dialog::backdrop{background:#142d4580}.confirm-dialog h2{font-size:20px;margin:0 0 12px}.confirm-dialog p{line-height:1.7;white-space:pre-line;overflow-wrap:anywhere;margin:0 0 24px}.confirm-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px}.confirm-actions button{min-height:44px;padding:10px 18px;border:1px solid var(--sim-navy);border-radius:9px;background:var(--sim-navy);color:#fff;font:inherit;font-weight:700;cursor:pointer}.confirm-actions .secondary{background:#fff;color:var(--sim-navy)}button:focus-visible{outline:3px solid #507fa7;outline-offset:3px}button:disabled{opacity:.55;cursor:wait}
</style>
