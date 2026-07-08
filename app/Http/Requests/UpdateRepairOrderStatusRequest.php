<?php

namespace App\Http\Requests;

use App\Enums\RepairOrderStatus;
use Illuminate\Validation\Rule;

class UpdateRepairOrderStatusRequest extends DashboardFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RepairOrderStatus::class)],
        ];
    }

    public function status(): RepairOrderStatus
    {
        return RepairOrderStatus::from($this->validated('status'));
    }
}
