<?php

namespace App\Http\Requests;

use App\Enums\BookingRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDashboardBookingRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingRequestStatus::class)],
        ];
    }

    public function status(): BookingRequestStatus
    {
        return BookingRequestStatus::from($this->validated('status'));
    }
}
