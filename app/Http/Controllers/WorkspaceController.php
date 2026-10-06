<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class WorkspaceController extends Controller
{
    public function bookingHistory(): Response
    {
        return Inertia::render('Workspace', [
            'section' => 'booking-history',
            'title' => 'ประวัติการจอง',
            'description' => 'ติดตามคำขอจองและสถานะการใช้งานทรัพยากรของคุณ',
        ]);
    }

    public function bookingCreate(): Response
    {
        return Inertia::render('Workspace', [
            'section' => 'booking-create',
            'title' => 'ส่งคำขอจอง',
            'description' => 'พื้นที่สำหรับสร้างคำขอใช้ห้องและทรัพยากร SIM',
        ]);
    }

    public function calendar(): Response
    {
        return Inertia::render('Workspace', [
            'section' => 'calendar',
            'title' => 'ปฏิทินการใช้งาน',
            'description' => 'ตรวจสอบช่วงเวลาการใช้งานและรายการที่ถูกจองแล้ว',
        ]);
    }

    public function review(): Response
    {
        return Inertia::render('Workspace', [
            'section' => 'review',
            'title' => 'ตรวจสอบคำขอ',
            'description' => 'ตรวจสอบคำขอที่รอดำเนินการ อนุมัติ หรือไม่อนุมัติ',
        ]);
    }

    public function resources(): Response
    {
        return Inertia::render('Workspace', [
            'section' => 'resources',
            'title' => 'จัดการทรัพยากร',
            'description' => 'จัดการข้อมูลห้องและทรัพยากรที่ใช้ในการให้บริการ SIM',
        ]);
    }
}
