<?php

namespace App\Http\Requests\Workshop;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use Illuminate\Validation\Rule;

class UpdateWorkshopStaffRoleRequest extends WorkshopOwnerRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(WorkshopUserRole::class)],
        ];
    }
}
