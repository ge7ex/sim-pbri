<?php

namespace App\Modules\SimResource\Http\Requests;

use App\Core\Enums\AppPermission;
use App\Core\Enums\UserRole;
use App\Modules\SimResource\Enums\SimResourceKind;
use App\Modules\SimResource\Enums\SimResourceStatus;
use App\Modules\SimResource\Models\SimResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSimResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('simResource');

        return $this->user()?->canAccess(AppPermission::ResourceUpdate) === true
            && $resource instanceof SimResource
            && $this->user()?->can('update', $resource) === true;
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
            'building' => ['exclude_unless:kind,room', 'nullable', 'string', 'max:120'],
            'floor' => ['exclude_unless:kind,room', 'nullable', 'string', 'max:64'],
            'capacity' => ['exclude_unless:kind,room', 'nullable', 'integer', 'min:1', 'max:10000'],
            'responsible_staff_user_id' => [
                'exclude_unless:kind,room', 'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('college_id', $this->user()?->college_id)
                    ->where('role', UserRole::Staff->value)),
            ],
        ];
    }
}
