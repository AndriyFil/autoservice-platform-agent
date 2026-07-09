<?php

namespace App\Http\Requests\Workshop;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use Illuminate\Validation\Rule;

class StoreWorkshopStaffRequest extends WorkshopOwnerRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::enum(WorkshopUserRole::class)],
        ];
    }
}
