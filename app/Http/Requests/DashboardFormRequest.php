<?php

namespace App\Http\Requests;

use App\Models\WorkshopUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base request for dashboard routes: authorizes against the active workshop
 * membership resolved by EnsureActiveWorkshop before validation runs.
 * Actions re-check workshop scoping; this is defense in depth.
 */
abstract class DashboardFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activeWorkshopUser = $this->attributes->get('activeWorkshopUser');

        if (! $activeWorkshopUser instanceof WorkshopUser) {
            return false;
        }

        foreach ($this->route()?->parameters() ?? [] as $parameter) {
            if (! $parameter instanceof Model) {
                continue;
            }

            $workshopId = $parameter->getAttribute('workshop_id');

            if ($workshopId !== null && (int) $workshopId !== (int) $activeWorkshopUser->workshop_id) {
                return false;
            }
        }

        return true;
    }

    protected function failedAuthorization(): never
    {
        // 404 instead of 403: do not reveal that a resource from another
        // workshop exists.
        throw new NotFoundHttpException;
    }
}
