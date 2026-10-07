<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
const props = defineProps<{ modelValue: File | null; currentUrl?: string | null; remove?: boolean; error?: string; disabled?: boolean }>();
const emit = defineEmits<{ 'update:modelValue': [File | null]; 'update:remove': [boolean] }>();
const preview = ref<string | null>(null);
const input = ref<HTMLInputElement | null>(null);
watch(() => props.modelValue, file => {
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = file ? URL.createObjectURL(file) : null;
    if (!file && input.value) input.value.value = '';
}, { immediate: true });
onUnmounted(() => { if (preview.value) URL.revokeObjectURL(preview.value); });
const url = computed(() => preview.value ?? (props.remove ? null : props.currentUrl));
function choose(event: Event): void {
    emit('update:modelValue', (event.target as HTMLInputElement).files?.[0] ?? null);
    emit('update:remove', false);
}
function clear(): void { emit('update:modelValue', null); emit('update:remove', !props.modelValue && Boolean(props.currentUrl)); }
</script>
<template>
    <div class="room-image-field">
        <label>รูปห้อง<input ref="input" type="file" accept="image/jpeg,image/png,image/webp" :disabled="disabled" @change="choose"></label>
        <small>JPEG, PNG หรือ WebP ไม่เกิน 5 MB และ 3000 × 3000 พิกเซล</small>
        <img v-if="url" :src="url" alt="ตัวอย่างรูปห้อง">
        <button v-if="url" type="button" class="button-secondary" :disabled="disabled" @click="clear">{{ modelValue ? 'ยกเลิกรูปที่เลือก' : 'ลบรูปห้อง' }}</button>
        <button v-if="remove && currentUrl" type="button" class="button-secondary" :disabled="disabled" @click="emit('update:remove', false)">คืนรูปเดิม</button>
        <small v-if="remove">รูปเดิมจะถูกลบเมื่อบันทึก</small>
        <small v-if="error" class="error" role="alert">{{ error }}</small>
    </div>
</template>
<style scoped>
.room-image-field{display:grid;gap:8px;grid-column:1/-1;min-width:0}.room-image-field label{display:grid;gap:8px;font-weight:700}.room-image-field input{width:100%;min-width:0}.room-image-field img{width:100%;max-width:360px;height:200px;object-fit:cover;border-radius:12px}.room-image-field button{justify-self:start}.room-image-field small{color:var(--sim-muted)}.room-image-field .error{color:var(--sim-danger)}
</style>
