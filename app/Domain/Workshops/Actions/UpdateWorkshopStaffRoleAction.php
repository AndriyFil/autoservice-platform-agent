<?php

namespace App\Domain\Workshops\Actions;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Domain\Workshops\Exceptions\LastOwnerCannotBeDemoted;
use App\Domain\Workshops\Exceptions\WorkshopStaffNotFound;
use App\Models\WorkshopUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateWorkshopStaffRoleAction
{
    public function handle(WorkshopUser $activeWorkshopUser, WorkshopUser $membership, WorkshopUserRole $role): WorkshopUser
    {
        $this->authorizeOwner($activeWorkshopUser);

        return DB::transaction(function () use ($activeWorkshopUser, $membership, $role): WorkshopUser {
            $membership = WorkshopUser::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $membership->workshop_id !== (int) $activeWorkshopUser->workshop_id) {
                throw new WorkshopStaffNotFound;
            }

            if ($membership->role === WorkshopUserRole::Owner && $role === WorkshopUserRole::Staff) {
                $this->lockOwners($activeWorkshopUser);

                if ($membership->isLastOwner()) {
                    throw LastOwnerCannotBeDemoted::forRoleField();
                }
            }

            $membership->update([
                'role' => $role,
            ]);

            return $membership;
        });
    }

    private function lockOwners(WorkshopUser $activeWorkshopUser): void
    {
        WorkshopUser::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where('role', WorkshopUserRole::Owner)
            ->lockForUpdate()
            ->get();
    }

    private function authorizeOwner(WorkshopUser $activeWorkshopUser): void
    {
        if ($activeWorkshopUser->role !== WorkshopUserRole::Owner) {
            throw new AuthorizationException;
        }
    }
}
