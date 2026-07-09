<?php

namespace App\Http\Requests\Workshop;

use Illuminate\Validation\Rule;

class UpdateWorkshopSettingsRequest extends WorkshopOwnerRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $activeWorkshopUser = $this->attributes->get('activeWorkshopUser');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('workshops', 'slug')->ignore($activeWorkshopUser->workshop_id),
            ],
        ];
    }
}
