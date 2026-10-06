<?php

namespace App\Modules\SimResource\Http\Requests;

use App\Core\Enums\AppPermission;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSimResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess(AppPermission::ResourceCreate) === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::enum(SimResourceKind::class)],
            'status' => ['required', Rule::enum(SimResourceStatus::class)],
            'quantity_total' => ['required', 'integer', 'min:1', 'max:100000'],
            'is_exclusive' => ['required', 'boolean'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
