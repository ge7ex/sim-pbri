<script setup lang="ts">
const services = [
  {
    title: 'จองห้องปฏิบัติการ',
    description:
      'ผู้ใช้งานที่ได้รับสิทธิ์สามารถส่งคำขอใช้งานห้อง SIM Lab, ห้อง Debriefing และพื้นที่ฝึกปฏิบัติการหลังเข้าสู่ระบบ',
  },
  {
    title: 'จัดการหุ่น Simulation',
    description:
      'รองรับการเลือกประเภทหุ่นและรายการหุ่นที่พร้อมใช้งานตาม Scenario การฝึกปฏิบัติการ',
  },
  {
    title: 'ขอใช้อุปกรณ์เพิ่มเติม',
    description:
      'ระบบดึงอุปกรณ์แนะนำจาก Scenario และให้ผู้ขอจองเลือกอุปกรณ์เพิ่มเติมจากรายการที่มีในระบบ',
  },
  {
    title: 'ตรวจสอบและอนุมัติ',
    description:
      'Staff/Admin ตรวจสอบความพร้อมของห้อง หุ่น อุปกรณ์ และเงื่อนไขก่อนอนุมัติคำขอ',
  },
]

const resources = [
  {
    value: '10',
    label: 'ห้องปฏิบัติการ',
  },
  {
    value: '26',
    label: 'หุ่น Simulation',
  },
  {
    value: '80+',
    label: 'รายการอุปกรณ์',
  },
  {
    value: '18',
    label: 'Scenario',
  },
]

const workflow = [
  'เข้าสู่ระบบด้วยบัญชีผู้ใช้งานที่ได้รับสิทธิ์',
  'เลือกวัน เวลา ห้อง และทรัพยากรที่ต้องการใช้งาน',
  'เลือก Scenario และตรวจสอบรายการอุปกรณ์',
  'ส่งคำขอเป็นสถานะรอตรวจสอบ',
  'Staff/Admin ตรวจสอบและแจ้งผลการอนุมัติ',
]

const announcements = [
  {
    title: 'Prototype Frontend Preview',
    date: 'มิถุนายน 2569',
    description:
      'เวอร์ชันนี้ใช้สำหรับนำเสนอภาพรวมหน้าระบบและ workflow เบื้องต้น ยังไม่เชื่อมต่อฐานข้อมูลจริง',
  },
  {
    title: 'Booking Request Flow',
    date: 'Mock Flow',
    description:
      'ตัวอย่าง workflow การส่งคำขอจองห้อง หุ่น Simulation Scenario และอุปกรณ์เพิ่มเติมหลังเข้าสู่ระบบ',
  },
  {
    title: 'Admin Review Concept',
    date: 'Planned Module',
    description:
      'คำขอทุกครั้งจะต้องผ่านการตรวจสอบจาก Staff/Admin ก่อนถือว่าได้รับอนุมัติ',
  },
]
</script>

<template>
  <section class="public-hero">
    <div class="public-hero-inner">
      <div class="public-hero-content">
        <span class="public-kicker">Prototype Preview · Mock Data</span>

        <h1>
          ระบบบริหารจัดการการจองห้องปฏิบัติการ Simulation
        </h1>

        <p>
          ระบบตัวอย่างสำหรับสนับสนุนการจัดการเรียนการสอน การฝึกปฏิบัติการจำลอง
          การบริหารทรัพยากรห้อง หุ่น Simulation อุปกรณ์ และ Scenario
          ภายในศูนย์ Simulation
        </p>

        <div class="public-hero-actions">
          <a class="public-primary-link" href="#workflow">
            ดูขั้นตอนการจอง
          </a>

          <a class="public-secondary-link" href="#services">
            ดูบริการของระบบ
          </a>
        </div>
      </div>

      <div class="public-hero-card">
        <div class="public-card-header">
          <span>SIM PBRI</span>
          <strong>Booking Preview</strong>
        </div>

        <div class="public-status-list">
          <div>
            <span>สถานะระบบ</span>
            <strong>Frontend Prototype</strong>
          </div>

          <div>
            <span>ข้อมูล</span>
            <strong>Mock Data Only</strong>
          </div>

          <div>
            <span>การจองจริง</span>
            <strong>ยังไม่เปิดใช้งาน</strong>
          </div>

          <div>
            <span>การใช้งานระบบ</span>
            <strong>ต้องเข้าสู่ระบบก่อน</strong>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="services" class="public-section">
    <div class="public-section-heading">
      <span>Services</span>
      <h2>บริการหลักของระบบ</h2>
      <p>
        ออกแบบเพื่อให้ผู้ใช้งานส่งคำขอจองได้เป็นระบบ
        และให้เจ้าหน้าที่ตรวจสอบทรัพยากรก่อนอนุมัติ
      </p>
    </div>

    <div class="public-service-grid">
      <article
        v-for="service in services"
        :key="service.title"
        class="public-service-card"
      >
        <div class="public-service-icon">+</div>
        <h3>{{ service.title }}</h3>
        <p>{{ service.description }}</p>
      </article>
    </div>
  </section>

  <section id="resources" class="public-stats-section">
    <div class="public-stats-inner">
      <div>
        <span class="public-kicker public-kicker-light">
          Resource Overview
        </span>

        <h2>ภาพรวมทรัพยากรสำหรับการฝึก Simulation</h2>

        <p>
          ข้อมูลตัวอย่างสำหรับนำเสนอภาพรวมระบบ
          โดยระบบจริงจะเชื่อมต่อฐานข้อมูลและตรวจสอบความพร้อมใช้งานแบบเป็นปัจจุบัน
        </p>
      </div>

      <div class="public-resource-grid">
        <div
          v-for="resource in resources"
          :key="resource.label"
          class="public-resource-card"
        >
          <strong>{{ resource.value }}</strong>
          <span>{{ resource.label }}</span>
        </div>
      </div>
    </div>
  </section>

  <section id="workflow" class="public-section">
    <div class="public-section-heading">
      <span>Workflow</span>
      <h2>ขั้นตอนการจองใช้งาน</h2>
      <p>
        ผู้ใช้งานต้องเข้าสู่ระบบก่อนส่งคำขอ และทุกคำขอจะยังไม่ถือว่าอนุมัติ
        จนกว่า Staff/Admin ตรวจสอบและยืนยันในระบบ
      </p>
    </div>

    <ol class="public-workflow">
      <li
        v-for="step in workflow"
        :key="step"
      >
        {{ step }}
      </li>
    </ol>
  </section>

  <section class="public-news-section">
    <div class="public-section-heading">
      <span>Announcements</span>
      <h2>ประกาศและความคืบหน้า</h2>
      <p>
        ส่วนนี้เป็นตัวอย่างพื้นที่ประกาศข่าวสารหรือความคืบหน้าของระบบ
      </p>
    </div>

    <div class="public-news-grid">
      <article
        v-for="announcement in announcements"
        :key="announcement.title"
        class="public-news-card"
      >
        <span>{{ announcement.date }}</span>
        <h3>{{ announcement.title }}</h3>
        <p>{{ announcement.description }}</p>
      </article>
    </div>
  </section>

  <section id="contact" class="public-contact-section">
    <div>
      <span class="public-kicker">Contact</span>
      <h2>ติดต่อศูนย์ Simulation</h2>
      <p>
        สำหรับสอบถามการใช้งานห้องปฏิบัติการ หุ่น Simulation อุปกรณ์
        หรือขั้นตอนการส่งคำขอจอง
      </p>
    </div>

    <div class="public-contact-card">
      <strong>SIM PBRI Center</strong>
      <span>วิทยาลัย / ศูนย์ฝึกปฏิบัติการจำลอง</span>
      <span>โทร. 0xx-xxx-xxxx</span>
      <span>อีเมล sim-center@example.ac.th</span>
    </div>
  </section>

  <footer class="public-footer">
    <p>© 2026 SIM PBRI Prototype Preview</p>
    <p>Frontend demo only · No real booking · No backend connection</p>
  </footer>
</template>