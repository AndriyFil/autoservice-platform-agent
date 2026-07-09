<?php

namespace App\Domain\Workshops\Queries;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\WorkshopUser;

class WorkshopStaffQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     userId: int,
     *     name: string,
     *     email: string,
     *     role: array{value: string, label: string},
     *     joinedAt: string,
     *     isCurrentUser: bool,
     *     isLastOwner: bool
     * }>
     */
    public function handle(WorkshopUser $activeWorkshopUser): array
    {
        $ownerCount = WorkshopUser::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where('role', WorkshopUserRole::Owner)
            ->count();

        return WorkshopUser::query()
            ->with('user')
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->orderByRaw('case when role = ? then 0 else 1 end', [WorkshopUserRole::Owner->value])
            ->orderBy('id')
            ->get()
            ->map(fn (WorkshopUser $membership): array => [
                'id' => $membership->id,
                'userId' => $membership->user_id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => [
                    'value' => $membership->role->value,
                    'label' => $this->roleLabel($membership->role),
                ],
                'joinedAt' => $membership->created_at->toISOString(),
                'isCurrentUser' => (int) $membership->user_id === (int) $activeWorkshopUser->user_id,
                'isLastOwner' => $membership->role === WorkshopUserRole::Owner && $ownerCount === 1,
            ])
            ->all();
    }

    private function roleLabel(WorkshopUserRole $role): string
    {
        return match ($role) {
            WorkshopUserRole::Owner => 'Owner',
            WorkshopUserRole::Staff => 'Staff',
        };
    }
}
