<?php

namespace App\Http\Controllers\Dashboard;

use App\Domain\Workshops\Actions\UpdateWorkshopSettingsAction;
use App\Domain\Workshops\Queries\WorkshopSettingsQuery;
use App\Domain\Workshops\Queries\WorkshopStaffQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Workshop\ShowWorkshopSettingsRequest;
use App\Http\Requests\Workshop\UpdateWorkshopSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkshopSettingsController extends Controller
{
    public function show(
        ShowWorkshopSettingsRequest $request,
        WorkshopSettingsQuery $workshopSettingsQuery,
        WorkshopStaffQuery $workshopStaffQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/Workshop/Settings', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'workshop' => $workshopSettingsQuery->handle($activeWorkshopUser),
            'staffMembers' => $workshopStaffQuery->handle($activeWorkshopUser),
            'roleOptions' => [
                ['value' => 'owner', 'label' => 'Owner'],
                ['value' => 'staff', 'label' => 'Staff'],
            ],
        ]);
    }

    public function update(
        UpdateWorkshopSettingsRequest $request,
        UpdateWorkshopSettingsAction $updateWorkshopSettingsAction,
    ): RedirectResponse {
        $updateWorkshopSettingsAction->handle(
            $request->attributes->get('activeWorkshopUser'),
            $request->validated(),
        );

        return to_route('dashboard.workshop.settings.show')
            ->with('status', 'Workshop settings updated.');
    }
}
