<?php

namespace App\Http\Requests\Workshop;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Http\Requests\DashboardFormRequest;
use App\Models\WorkshopUser;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class WorkshopOwnerRequest extends DashboardFormRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $activeWorkshopUser = $this->attributes->get('activeWorkshopUser');

        return $activeWorkshopUser instanceof WorkshopUser
            && $activeWorkshopUser->role === WorkshopUserRole::Owner;
    }

    protected function failedAuthorization(): never
    {
        $activeWorkshopUser = $this->attributes->get('activeWorkshopUser');

        if (! $activeWorkshopUser instanceof WorkshopUser || $this->hasCrossWorkshopRouteModel($activeWorkshopUser)) {
            throw new NotFoundHttpException;
        }

        throw new HttpException(403);
    }

    private function hasCrossWorkshopRouteModel(WorkshopUser $activeWorkshopUser): bool
    {
        foreach ($this->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $workshopId = $parameter->getAttribute('workshop_id');

            if ($workshopId !== null && (int) $workshopId !== (int) $activeWorkshopUser->workshop_id) {
                return true;
            }
        }

        return false;
    }
}
