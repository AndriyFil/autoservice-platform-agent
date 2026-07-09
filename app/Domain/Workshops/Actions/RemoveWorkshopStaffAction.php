<?php

namespace App\Domain\Workshops\Actions;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Domain\Workshops\Exceptions\LastOwnerCannotBeRemoved;
use App\Domain\Workshops\Exceptions\WorkshopStaffNotFound;
use App\Models\WorkshopUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class RemoveWorkshopStaffAction
{
    public function handle(WorkshopUser $activeWorkshopUser, WorkshopUser $membership): void
    {
        $this->authorizeOwner($activeWorkshopUser);

        DB::transaction(function () use ($activeWorkshopUser, $membership): void {
            $membership = WorkshopUser::query()
                ->whereKey($membership->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $membership->workshop_id !== (int) $activeWorkshopUser->workshop_id) {
                throw new WorkshopStaffNotFound;
            }

            if ($membership->role === WorkshopUserRole::Owner) {
                $this->lockOwners($activeWorkshopUser);

                if ($membership->isLastOwner()) {
                    throw LastOwnerCannotBeRemoved::forMembershipField();
                }
            }

            $membership->delete();
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
