<?php

namespace App\Domain\Workshops\Queries;

use App\Models\WorkshopUser;
use App\Support\Urls\AppUrl;

class WorkshopSettingsQuery
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     publicIntakePath: string,
     *     publicIntakeUrl: string,
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
            'publicIntakePath' => route('public-intake.create', $workshop, false),
            'publicIntakeUrl' => AppUrl::publicPath(route('public-intake.create', $workshop, false)),
            'createdAt' => $workshop->created_at->toISOString(),
        ];
    }
}
