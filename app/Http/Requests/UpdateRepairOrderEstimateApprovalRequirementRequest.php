<?php

namespace App\Http\Requests;

class UpdateRepairOrderEstimateApprovalRequirementRequest extends DashboardFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'requires_estimate_approval' => ['required', 'boolean'],
        ];
    }
}
