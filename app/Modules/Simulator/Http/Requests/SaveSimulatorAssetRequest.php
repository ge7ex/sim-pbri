<?php

namespace App\Modules\Simulator\Http\Requests;

use App\Modules\Simulator\Enums\SimulatorAssetStatus;
use App\Modules\Simulator\Models\SimulatorAsset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveSimulatorAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('simulatorAsset');

        return $asset instanceof SimulatorAsset
            ? $this->user()?->can('update', $asset) === true
            : $this->user()?->can('create', SimulatorAsset::class) === true;
    }

    public function rules(): array
    {
        return [
            'simulator_type_id' => ['required', 'integer', Rule::exists('simulator_types', 'id')->where('college_id', $this->user()?->college_id)],
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_code' => ['nullable', 'string', 'max:64', Rule::unique('simulator_assets')->where('college_id', $this->user()?->college_id)->ignore($this->route('simulatorAsset')?->id)],
            'purchase_year' => ['nullable', 'required_with:purchase_price,useful_life_years', 'integer', 'min:1900', 'max:'.now()->year],
            'purchase_price' => ['nullable', 'required_with:purchase_year,useful_life_years', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'useful_life_years' => ['nullable', 'required_with:purchase_year,purchase_price', 'integer', 'min:1', 'max:100'],
            'status' => ['required', Rule::enum(SimulatorAssetStatus::class)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
