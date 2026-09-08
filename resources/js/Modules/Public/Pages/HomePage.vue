<script setup lang="ts">
const services = [
  {
    title: 'จองห้องปฏิบัติการ',
    description:
      'บุคลากรที่ได้รับสิทธิ์สามารถส่งคำขอใช้ห้องปฏิบัติการและทรัพยากรที่เกี่ยวข้องผ่านระบบหลังเข้าสู่ระบบ',
  },
  {
    title: 'จัดการทรัพยากร Simulation',
    description:
      'รองรับการจัดการข้อมูลห้อง หุ่น Simulation และอุปกรณ์เพื่อประกอบการพิจารณาคำขอใช้งาน',
  },
  {
    title: 'ติดตามสถานะคำขอ',
    description:
      'ผู้ขอใช้งานสามารถติดตามสถานะคำขอและผลการพิจารณาจากเจ้าหน้าที่ผ่านระบบ',
  },
  {
    title: 'ตรวจสอบและอนุมัติ',
    description:
      'เจ้าหน้าที่ผู้มีสิทธิ์ตรวจสอบความพร้อมของทรัพยากรและเงื่อนไขก่อนอนุมัติหรือไม่อนุมัติคำขอ',
  },
]

const resources = [
  {
    value: 'ห้อง',
    label: 'ห้องปฏิบัติการ Simulation',
  },
  {
    value: 'หุ่น',
    label: 'หุ่นและครุภัณฑ์ Simulation',
  },
  {
    value: 'อุปกรณ์',
    label: 'ทรัพยากรประกอบการฝึกปฏิบัติ',
  },
  {
    value: 'คำขอ',
    label: 'กระบวนการตรวจสอบและอนุมัติ',
  },
]

const workflow = [
  'เข้าสู่ระบบด้วยบัญชีผู้ใช้งานที่ได้รับสิทธิ์',
  'เลือกวัน เวลา ห้อง และทรัพยากรที่ต้องการใช้งาน',
  'ตรวจสอบข้อมูลและรายละเอียดคำขอให้ครบถ้วน',
  'ส่งคำขอเพื่อรอการตรวจสอบจากเจ้าหน้าที่',
  'ติดตามผลการพิจารณาและสถานะคำขอในระบบ',
]
</script>

<template>
  <section class="public-hero">
    <div class="public-hero-inner">
      <div class="public-hero-content">
        <span class="public-kicker">SIM PBRI</span>

        <h1>
          ระบบบริหารจัดการทรัพยากรและการจองห้องปฏิบัติการ Simulation
        </h1>

        <p>
          ระบบสำหรับสนับสนุนการบริหารจัดการห้องปฏิบัติการ Simulation
          ทรัพยากรที่เกี่ยวข้อง และกระบวนการส่งคำขอใช้งานภายในหน่วยงาน
          โดยผู้ใช้งานต้องได้รับสิทธิ์และเข้าสู่ระบบก่อนดำเนินการ
        </p>

        <div class="public-hero-actions">
          <a class="public-primary-link" href="#workflow">
            ดูขั้นตอนการใช้งาน
          </a>

          <a class="public-secondary-link" href="#services">
            ดูบริการของระบบ
          </a>
        </div>
      </div>

      <div class="public-hero-card">
        <div class="public-card-header">
          <span>SIM PBRI</span>
          <strong>Simulation Resource Management</strong>
        </div>

        <div class="public-status-list">
          <div>
            <span>การเข้าใช้งาน</span>
            <strong>สำหรับผู้ใช้งานที่ได้รับสิทธิ์</strong>
          </div>

          <div>
            <span>การส่งคำขอ</span>
            <strong>ดำเนินการหลังเข้าสู่ระบบ</strong>
          </div>

          <div>
            <span>การอนุมัติ</span>
            <strong>ตรวจสอบโดยเจ้าหน้าที่ผู้มีสิทธิ์</strong>
          </div>

          <div>
            <span>สถานะคำขอ</span>
            <strong>ติดตามผลผ่านระบบ</strong>
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
        สนับสนุนการส่งคำขอใช้ทรัพยากรอย่างเป็นระบบ
        และช่วยให้เจ้าหน้าที่ตรวจสอบข้อมูลก่อนพิจารณาอนุมัติ
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
          Resource Management
        </span>

        <h2>ทรัพยากรสำหรับการฝึก Simulation</h2>

        <p>
          ระบบจัดการข้อมูลทรัพยากรที่เกี่ยวข้องกับการฝึกปฏิบัติ
          เพื่อใช้ประกอบการส่งคำขอ ตรวจสอบความพร้อม และพิจารณาการใช้งาน
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
      <h2>ขั้นตอนการส่งคำขอใช้งาน</h2>
      <p>
        ผู้ใช้งานต้องเข้าสู่ระบบก่อนส่งคำขอ และคำขอจะได้รับการตรวจสอบ
        โดยเจ้าหน้าที่ผู้มีสิทธิ์ก่อนมีผลเป็นรายการที่ได้รับอนุมัติ
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

  <section id="contact" class="public-contact-section">
    <div>
      <span class="public-kicker">Contact</span>
      <h2>ติดต่อผู้ดูแลระบบ</h2>
      <p>
        หากต้องการสอบถามเกี่ยวกับสิทธิ์การเข้าใช้งาน ขั้นตอนการส่งคำขอ
        หรือการใช้ทรัพยากร Simulation โปรดติดต่อเจ้าหน้าที่ผู้รับผิดชอบของหน่วยงาน
      </p>
    </div>

    <div class="public-contact-card">
      <strong>การขอรับสิทธิ์ใช้งาน</strong>
      <span>ระบบนี้จัดไว้สำหรับบุคลากรและผู้ใช้งานที่ได้รับอนุญาต</span>
      <span>กรุณาติดต่อเจ้าหน้าที่ผู้รับผิดชอบเพื่อขอรับหรือสอบถามสิทธิ์การใช้งาน</span>
    </div>
  </section>

  <footer class="public-footer">
    <p>© 2026 SIM PBRI</p>
    <p>Simulation Resource Management and Booking System</p>
  </footer>
</template>
