<?php

use App\Models\College;
use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Models\SimResource;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = realpath((string) config('database.connections.sqlite.database'));
$root = realpath((string) getenv('TEMP')).DIRECTORY_SEPARATOR.'sim-pbri-phase-i-demo'.DIRECTORY_SEPARATOR.'sim-pbri-phase-i-demo.sqlite';
if (config('database.default') !== 'sqlite' || $database === false || strcasecmp($database, $root) !== 0) {
    fwrite(STDERR, "Refusing to seed outside the dedicated Phase I demo SQLite database.\n");
    exit(2);
}

$college = College::create(['name' => 'Fictional Senior Demo College']);
$credentials = [];
$users = [];
foreach ([
    'admin' => 'ผู้ดูแลระบบตัวอย่าง',
    'staff' => 'เจ้าหน้าที่ตัวอย่าง',
    'lecturer' => 'อาจารย์ตัวอย่าง',
] as $role => $name) {
    $email = $role.'@example.test';
    $password = Str::password(28, true, true, true, false);
    $users[$role] = User::query()->forceCreate([
        'college_id' => $college->id,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'password' => $password,
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $credentials[] = strtoupper($role)."\nEmail: $email\nPassword: $password\n";
}

$room = SimResource::create([
    'college_id' => $college->id,
    'name' => 'ห้องฝึกทักษะการพยาบาล',
    'kind' => 'room',
    'status' => 'ready',
    'quantity_total' => 1,
    'is_exclusive' => true,
    'building' => 'อาคารฝึกปฏิบัติ (ข้อมูลสมมติ)',
    'floor' => '2',
    'capacity' => 24,
    'location' => 'ชั้น 2 ห้องตัวอย่าง',
    'responsible_staff_user_id' => $users['staff']->id,
]);
$equipment = SimResource::create([
    'college_id' => $college->id,
    'name' => 'เครื่องติดตามสัญญาณชีพตัวอย่าง',
    'kind' => 'equipment',
    'status' => 'ready',
    'quantity_total' => 4,
    'is_exclusive' => false,
]);
$type = SimulatorType::create([
    'college_id' => $college->id,
    'name' => 'หุ่นผู้ใหญ่ฝึกทักษะ (ตัวอย่าง)',
    'description' => 'ข้อมูลสมมติสำหรับ Senior walkthrough',
]);
$asset = SimulatorAsset::create([
    'college_id' => $college->id,
    'simulator_type_id' => $type->id,
    'asset_name' => 'หุ่นฝึก A (ตัวอย่าง)',
    'asset_code' => 'DEMO-SIM-001',
    'status' => 'active',
    'location' => 'ห้องเก็บอุปกรณ์ตัวอย่าง',
]);
$course = Course::create([
    'college_id' => $college->id,
    'code' => 'DEMO-101',
    'name' => 'การประเมินผู้ป่วยเบื้องต้น (ตัวอย่าง)',
]);
$scenario = Scenario::create([
    'course_id' => $course->id,
    'name' => 'สถานการณ์ประเมินผู้ป่วย (ตัวอย่าง)',
    'description' => 'ใช้เพื่อสาธิตเท่านั้น ไม่มีข้อมูลผู้ป่วยจริง',
    'is_active' => true,
]);
$scenario->recommendedResources()->attach([$equipment->id => ['quantity' => 2]]);
$scenario->recommendedSimulatorTypes()->attach($type->id);

$now = Carbon::now();
$bangkokNow = Carbon::now('Asia/Bangkok');
$approved = Booking::create([
    'college_id' => $college->id,
    'requested_by_user_id' => $users['lecturer']->id,
    'course_id' => $course->id,
    'scenario_id' => $scenario->id,
    'simulator_asset_id' => $asset->id,
    'requester_name' => 'ผู้ประสานงานสมมติ',
    'requester_phone' => '0800000000',
    'starts_at' => $bangkokNow->copy()->addDays(2)->setTime(9, 0)->utc(),
    'ends_at' => $bangkokNow->copy()->addDays(2)->setTime(11, 0)->utc(),
    'participant_count' => 18,
    'status' => BookingStatus::Approved,
    'reviewed_by_user_id' => $users['staff']->id,
    'reviewed_at' => $now,
    'review_reason' => 'อนุมัติในข้อมูลสังเคราะห์',
]);
$approved->resources()->attach([
    $room->id => ['quantity' => 1, 'is_auto_recommended' => false, 'resource_name_snapshot' => $room->name, 'resource_location_snapshot' => $room->location, 'room_building_snapshot' => $room->building, 'room_floor_snapshot' => $room->floor, 'room_capacity_snapshot' => $room->capacity],
    $equipment->id => ['quantity' => 2, 'is_auto_recommended' => true, 'resource_name_snapshot' => $equipment->name, 'resource_location_snapshot' => $equipment->location, 'room_building_snapshot' => null, 'room_floor_snapshot' => null, 'room_capacity_snapshot' => null],
]);
$approved->statusTransitions()->create(['from_status' => null, 'to_status' => BookingStatus::Pending, 'actor_user_id' => $users['lecturer']->id]);
$approved->statusTransitions()->create(['from_status' => BookingStatus::Pending, 'to_status' => BookingStatus::Approved, 'actor_user_id' => $users['staff']->id, 'reason' => 'อนุมัติในข้อมูลสังเคราะห์']);

$pending = Booking::create([
    'college_id' => $college->id,
    'requested_by_user_id' => $users['lecturer']->id,
    'course_id' => $course->id,
    'scenario_id' => $scenario->id,
    'requester_name' => 'ผู้ประสานงานสมมติ',
    'requester_phone' => '0800000000',
    'starts_at' => $bangkokNow->copy()->addDays(3)->setTime(13, 0)->utc(),
    'ends_at' => $bangkokNow->copy()->addDays(3)->setTime(15, 0)->utc(),
    'participant_count' => 18,
    'status' => BookingStatus::Pending,
]);
$pending->resources()->attach([
    $room->id => ['quantity' => 1, 'is_auto_recommended' => false, 'resource_name_snapshot' => $room->name, 'resource_location_snapshot' => $room->location, 'room_building_snapshot' => $room->building, 'room_floor_snapshot' => $room->floor, 'room_capacity_snapshot' => $room->capacity],
    $equipment->id => ['quantity' => 2, 'is_auto_recommended' => true, 'resource_name_snapshot' => $equipment->name, 'resource_location_snapshot' => $equipment->location, 'room_building_snapshot' => null, 'room_floor_snapshot' => null, 'room_capacity_snapshot' => null],
]);
$pending->statusTransitions()->create(['from_status' => null, 'to_status' => BookingStatus::Pending, 'actor_user_id' => $users['lecturer']->id]);

file_put_contents(dirname($database).DIRECTORY_SEPARATOR.'credentials.txt', implode("\n", $credentials), LOCK_EX);
echo "Seeded fictional College, three roles, resources, simulator, course, scenario, and approved/pending bookings.\n";
