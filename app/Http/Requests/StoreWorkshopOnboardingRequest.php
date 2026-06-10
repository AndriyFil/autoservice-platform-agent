<?php

namespace App\Http\Requests;

use App\Models\WorkshopUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkshopOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && !$this->user()->workshopUsers()->exists();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('workshops', 'slug'),
            ],
        ];
    }
}
