<?php

namespace App\Http\Requests;

use App\Enums\BookingRequestStatus;
use Illuminate\Validation\Rule;

class UpdateDashboardBookingRequestStatusRequest extends DashboardFormRequest
{
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
