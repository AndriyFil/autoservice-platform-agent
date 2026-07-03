<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkshopUser;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;

class ActiveWorkshopMembershipResolver
{
    public function resolve(User $user, Session $session): ?WorkshopUser
    {
        $workshopUsers = $user
            ->workshopUsers()
            ->with('workshop')
            ->orderBy('id')
            ->get();

        if ($workshopUsers->isEmpty()) {
            return null;
        }

        $activeWorkshopId = $session->get('active_workshop_id');
        $activeWorkshopUser = $workshopUsers->firstWhere('workshop_id', $activeWorkshopId);

        if (! $activeWorkshopUser) {
            $activeWorkshopUser = $workshopUsers->first();
            $session->put('active_workshop_id', $activeWorkshopUser->workshop_id);

            Log::info('Active workshop resolved to default membership.', [
                'user_id' => $user->id,
                'requested_workshop_id' => $activeWorkshopId,
                'resolved_workshop_id' => $activeWorkshopUser->workshop_id,
            ]);
        }

        return $activeWorkshopUser;
    }
}
