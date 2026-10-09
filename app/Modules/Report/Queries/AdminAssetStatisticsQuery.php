<?php

namespace App\Modules\Report\Queries;

use App\Modules\Simulator\Models\SimulatorAsset;
use App\Modules\Simulator\Services\StraightLineDepreciation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class AdminAssetStatisticsQuery
{
    public function __construct(private StraightLineDepreciation $depreciation) {}

    public function forCollege(int $college, array $filters): array
    {
        $counts = DB::table('simulator_assets')->where('college_id', $college)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($count) => (int) $count)->all();
        $maintenance = DB::table('simulator_maintenance_records as m')->join('simulator_assets as a', 'a.id', '=', 'm.simulator_asset_id')->where('a.college_id', $college)->whereBetween('m.maintenance_date', [$filters['date_from'], $filters['date_to']])->selectRaw('COUNT(*) AS events, COALESCE(SUM(m.cost),0) AS cost')->first();
        $purchase = 0;
        $current = 0;
        $unknown = 0;
        $year = CarbonImmutable::now(config('app.timezone'))->year;
        SimulatorAsset::query()->where('college_id', $college)->select(['id', 'purchase_year', 'purchase_price', 'useful_life_years'])->chunkById(200, function ($assets) use (&$purchase, &$current, &$unknown, $year): void {
            foreach ($assets as $asset) {
                $purchase += $this->cents($asset->purchase_price ?? '0.00');
                $value = $this->depreciation->calculate($asset, $year);
                if ($value === null) {
                    $unknown++;
                } else {
                    $current += $this->cents($value['book_value']);
                }
            }
        });

        return ['total' => array_sum($counts), 'status_counts' => $counts, 'maintenance_events' => (int) $maintenance->events, 'maintenance_cost' => number_format((float) $maintenance->cost, 2, '.', ''), 'purchase_value' => $this->money($purchase), 'current_value' => $this->money($current), 'valuation_year' => $year, 'unvalued_count' => $unknown];
    }

    private function cents(string $value): int
    {
        [$whole, $fraction] = explode('.', $value);

        return (int) $whole * 100 + (int) $fraction;
    }

    private function money(int $value): string
    {
        return intdiv($value, 100).'.'.str_pad((string) ($value % 100), 2, '0', STR_PAD_LEFT);
    }
}
