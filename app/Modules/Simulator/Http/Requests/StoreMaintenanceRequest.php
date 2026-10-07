<?php

namespace App\Modules\Simulator\Http\Requests;

use App\Modules\Simulator\Models\SimulatorAsset;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('simulatorAsset');

        return $asset instanceof SimulatorAsset && $this->user()?->can('maintenance', $asset) === true;
    }

    public function rules(): array
    {
        return [
            'maintenance_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:2000'],
            'cost' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
