<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Workshops\Actions\AddWorkshopStaffAction;
use App\Domain\Workshops\Actions\RemoveWorkshopStaffAction;
use App\Domain\Workshops\Actions\UpdateWorkshopStaffRoleAction;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\DestroyWorkshopStaffRequest;
use App\Http\Requests\Workshop\StoreWorkshopStaffRequest;
use App\Http\Requests\Workshop\UpdateWorkshopStaffRoleRequest;
use App\Models\WorkshopUser;
use Illuminate\Http\RedirectResponse;

class WorkshopStaffController extends Controller
{
    public function store(
        StoreWorkshopStaffRequest $request,
        AddWorkshopStaffAction $addWorkshopStaffAction,
    ): RedirectResponse {
        $addWorkshopStaffAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $request->validated(),
        );

        return to_route('dashboard.workshop.settings.show')
            ->with('status', 'Staff member added.');
    }

    public function update(
        UpdateWorkshopStaffRoleRequest $request,
        WorkshopUser $workshopUser,
        UpdateWorkshopStaffRoleAction $updateWorkshopStaffRoleAction,
    ): RedirectResponse {
        $updateWorkshopStaffRoleAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $workshopUser,
            WorkshopUserRole::from($request->validated('role')),
        );

        return to_route('dashboard.workshop.settings.show')
            ->with('status', 'Staff role updated.');
    }

    public function destroy(
        DestroyWorkshopStaffRequest $request,
        WorkshopUser $workshopUser,
        RemoveWorkshopStaffAction $removeWorkshopStaffAction,
    ): RedirectResponse {
        $removeWorkshopStaffAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $workshopUser,
        );

        return to_route('dashboard.workshop.settings.show')
            ->with('status', 'Staff member removed.');
    }
}
