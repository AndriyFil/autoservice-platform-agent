<?php

namespace App\Actions\Workshops;

use App\Enums\WorkshopUserRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Support\Facades\DB;

class CreateInitialWorkshopAction
{
    /**
     * @param  array{name: string, slug: string}  $data
     */
    public function handle(User $user, array $data): Workshop
    {
        return DB::transaction(function () use ($user, $data): Workshop {
            $workshop = Workshop::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
            ]);

            WorkshopUser::create([
                'workshop_id' => $workshop->id,
                'user_id' => $user->id,
                'role' => WorkshopUserRole::Owner,
            ]);

            return $workshop;
        });
    }
}
