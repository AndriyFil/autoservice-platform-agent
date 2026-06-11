<?php

namespace App\Support;

use App\Models\User;
use App\Models\WorkshopUser;
use Illuminate\Session\Store;

class ActiveWorkshopMembershipResolver
{
    public function resolve(User $user, Store $session): ?WorkshopUser
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
        }

        return $activeWorkshopUser;
    }
}
