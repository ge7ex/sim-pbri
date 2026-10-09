<?php

namespace Tests\Feature\Scenario;

use App\Models\College;
use App\Models\User;
use App\Modules\Scenario\Models\Course;
use App\Modules\Scenario\Models\Scenario;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScenarioRelationPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_payload_excludes_foreign_resources_and_non_equipment_without_deleting_pivots(): void
    {
        $college = College::factory()->create();
        $foreign = College::factory()->create();
        $course = Course::create(['college_id' => $college->id, 'name' => 'Own course']);
        $scenario = Scenario::create(['course_id' => $course->id, 'name' => 'Own scenario']);
        $equipment = $this->resource($college, 'equipment', 'OWN EQUIPMENT');
        $foreignEquipment = $this->resource($foreign, 'equipment', 'FOREIGN SECRET EQUIPMENT');
        $room = $this->resource($college, 'room', 'MALFORMED ROOM RECOMMENDATION');
        $scenario->recommendedResources()->attach([$equipment->id => ['quantity' => 2], $foreignEquipment->id => ['quantity' => 3], $room->id => ['quantity' => 1]]);
        foreach (['staff', 'admin'] as $role) {
            $actor = User::factory()->create(['college_id' => $college->id, 'role' => $role]);
            $response = $this->actingAs($actor)->get('/app/scenarios')->assertOk();
            $data = $response->inertiaProps('courses');
            $this->assertCount(1, $data[0]['scenarios'][0]['recommended_resources']);
            $this->assertSame($equipment->id, $data[0]['scenarios'][0]['recommended_resources'][0]['id']);
            $this->assertSame(2, $data[0]['scenarios'][0]['recommended_resources'][0]['pivot']['quantity']);
            $this->assertStringNotContainsString('FOREIGN SECRET', json_encode($response->inertiaProps()));
            $this->assertStringNotContainsString('MALFORMED ROOM RECOMMENDATION', json_encode($response->inertiaProps()));
        }
        $this->assertSame(3, $scenario->recommendedResources()->count());
    }

    private function resource(College $college, string $kind, string $name): SimResource
    {
        return SimResource::create(['college_id' => $college->id, 'name' => $name, 'kind' => $kind, 'status' => 'ready', 'quantity_total' => 10, 'is_exclusive' => false]);
    }
}
