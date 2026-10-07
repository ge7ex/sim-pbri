<?php

namespace Tests\Feature\Scenario;

use App\Models\College;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScenarioFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_and_scenario_are_scoped_through_college(): void
    {
        $college = College::factory()->create();

        $course = Course::query()->create([
            'college_id' => $college->id,
            'code' => 'NUR-101',
            'name' => 'การพยาบาลมารดาและทารก',
        ]);

        $scenario = Scenario::query()->create([
            'course_id' => $course->id,
            'name' => 'การทำคลอดปกติ',
            'description' => 'สถานการณ์จำลองการทำคลอด',
        ]);

        $this->assertTrue($course->college->is($college));
        $this->assertTrue($scenario->course->is($course));
        $this->assertTrue($scenario->is_active);
    }

    public function test_scenario_can_store_recommended_equipment_quantities(): void
    {
        $college = College::factory()->create();

        $course = Course::query()->create([
            'college_id' => $college->id,
            'name' => 'การพยาบาลพื้นฐาน',
        ]);

        $scenario = Scenario::query()->create([
            'course_id' => $course->id,
            'name' => 'การประเมินสัญญาณชีพ',
        ]);

        $equipment = SimResource::query()->create([
            'college_id' => $college->id,
            'name' => 'Patient Monitor',
            'kind' => SimResourceKind::Equipment,
            'status' => SimResourceStatus::Ready,
            'quantity_total' => 5,
            'is_exclusive' => false,
        ]);

        $scenario->recommendedResources()->attach(
            $equipment->id,
            ['quantity' => 2],
        );

        $recommended = $scenario->recommendedResources()->sole();

        $this->assertTrue($recommended->is($equipment));
        $this->assertSame(2, $recommended->pivot->quantity);
    }
}
