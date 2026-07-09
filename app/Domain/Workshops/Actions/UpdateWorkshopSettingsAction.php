<?php

namespace App\Domain\Workshops\Actions;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateWorkshopSettingsAction
{
    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): Workshop
    {
        $this->authorizeOwner($activeWorkshopUser);

        $workshop = $activeWorkshopUser->workshop;

        $workshop->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        return $workshop;
    }

    private function authorizeOwner(WorkshopUser $activeWorkshopUser): void
    {
        if ($activeWorkshopUser->role !== WorkshopUserRole::Owner) {
            throw new AuthorizationException;
        }
    }
}
