<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchDashboardCustomersRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->trimmedString($this->query('q')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    public function queryText(): string
    {
        return $this->validated('q');
    }

    private function trimmedString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }
}
