<?php

namespace App\Domain\Workshops\Actions;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Domain\Workshops\Exceptions\StaffAlreadyBelongsToWorkshop;
use App\Models\User;
use App\Models\WorkshopUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AddWorkshopStaffAction
{
    /**
     * @param  array{name: string, email: string, password: string, role: string}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): WorkshopUser
    {
        $this->authorizeOwner($activeWorkshopUser);

        return DB::transaction(function () use ($activeWorkshopUser, $data): WorkshopUser {
            $user = User::query()
                ->where('email', $data['email'])
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);
            }

            $existingMembership = WorkshopUser::query()
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->where('user_id', $user->id)
                ->exists();

            if ($existingMembership) {
                throw StaffAlreadyBelongsToWorkshop::forEmailField();
            }

            return WorkshopUser::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'user_id' => $user->id,
                'role' => WorkshopUserRole::from($data['role']),
            ]);
        });
    }

    private function authorizeOwner(WorkshopUser $activeWorkshopUser): void
    {
        if ($activeWorkshopUser->role !== WorkshopUserRole::Owner) {
            throw new AuthorizationException;
        }
    }
}
