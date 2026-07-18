<?php

namespace App\Domain\Workshops\Queries;

use App\Models\WorkshopUser;

class WorkshopSettingsQuery
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     createdAt: string
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser): array
    {
        $workshop = $activeWorkshopUser->workshop;

        return [
            'id' => $workshop->id,
            'name' => $workshop->name,
            'slug' => $workshop->slug,
            'createdAt' => $workshop->created_at->toISOString(),
        ];
    }
}
