<script setup lang="ts">
interface Emits {
  openHome: []
  openLogin: []
}

defineEmits<Emits>()
</script>

<template>
  <div class="public-site">
    <header class="public-header">
      <div class="public-header-top">
        <div class="public-header-top-inner">
          <span>ระบบบริหารจัดการทรัพยากรและการจองห้องปฏิบัติการ Simulation</span>
          <span>สำหรับบุคลากรและผู้ใช้งานที่ได้รับสิทธิ์</span>
        </div>
      </div>

      <div class="public-header-main">
        <div class="public-header-inner">
          <button
            class="public-brand"
            type="button"
            @click="$emit('openHome')"
          >
            <span class="public-brand-mark">SIM</span>
            <span>
              <strong>SIM PBRI</strong>
              <small>Simulation Center Booking System</small>
            </span>
          </button>

          <nav class="public-nav" aria-label="Public navigation">
            <a href="#services">บริการ</a>
            <a href="#workflow">ขั้นตอนการจอง</a>
            <a href="#resources">ทรัพยากร</a>
            <a href="#contact">ติดต่อ</a>
          </nav>

          <button
            class="public-login-button"
            type="button"
            @click="$emit('openLogin')"
          >
            เข้าสู่ระบบ
          </button>
        </div>
      </div>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>
