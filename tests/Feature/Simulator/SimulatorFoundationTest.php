<?php

namespace Tests\Feature\Simulator;

use App\Models\College;
use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Models\SimulatorType;
use App\Modules\Simulator\Services\StraightLineDepreciation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SimulatorFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_and_asset_belong_to_college_and_status_is_cast(): void
    {
        $college = College::factory()->create();
        $type = SimulatorType::create(['college_id' => $college->id, 'name' => 'CPR']);
        $asset = SimulatorAsset::create([
            'college_id' => $college->id, 'simulator_type_id' => $type->id,
            'asset_name' => 'CPR A', 'status' => SimulatorAssetStatus::Maintenance,
        ]);
        $this->assertTrue($type->college->is($college));
        $this->assertTrue($asset->college->is($college));
        $this->assertTrue($asset->simulatorType->is($type));
        $this->assertSame(SimulatorAssetStatus::Maintenance, $asset->fresh()->status);
    }

    public function test_depreciation_is_capped_and_uses_calendar_years_and_decimal_rounding(): void
    {
        $asset = new SimulatorAsset(['purchase_year' => 2024, 'purchase_price' => '1000.00', 'useful_life_years' => 3]);
        $service = new StraightLineDepreciation;
        $this->assertSame('0.00', $service->calculate($asset, 2023)['accumulated_amount']);
        $this->assertSame('1000.00', $service->calculate($asset, 2024)['book_value']);
        $this->assertSame('333.33', $service->calculate($asset, 2025)['annual_amount']);
        $this->assertSame('666.67', $service->calculate($asset, 2026)['accumulated_amount']);
        $this->assertSame('0.00', $service->calculate($asset, 2030)['book_value']);
        $this->assertNull($service->calculate(new SimulatorAsset, 2026));
        $asset->purchase_price = '0.00';
        $this->assertSame('0.00', $service->calculate($asset, 2026)['book_value']);
    }
}
