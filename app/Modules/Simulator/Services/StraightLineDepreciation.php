<?php

namespace App\Modules\Simulator\Services;

use App\Modules\Simulator\Models\SimulatorAsset;

final class StraightLineDepreciation
{
    /** Calendar-year estimate: zero residual value, no charge in purchase year.
     * Integer cents avoid floating-point rounding. This is not a booked accounting entry.
     * @return array{year: int, annual_amount: string, accumulated_amount: string, book_value: string}|null
     */
    public function calculate(SimulatorAsset $asset, int $year): ?array
    {
        if ($asset->purchase_year === null || $asset->purchase_price === null || ! $asset->useful_life_years) {
            return null;
        }

        [$whole, $fraction] = explode('.', $asset->purchase_price);
        $price = (int) $whole * 100 + (int) $fraction;
        $life = $asset->useful_life_years;
        $elapsed = min($life, max(0, $year - $asset->purchase_year));
        $accumulated = intdiv($price * $elapsed * 2 + $life, $life * 2);

        return [
            'year' => $year,
            'annual_amount' => $this->money(intdiv($price * 2 + $life, $life * 2)),
            'accumulated_amount' => $this->money($accumulated),
            'book_value' => $this->money($price - $accumulated),
        ];
    }

    private function money(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
