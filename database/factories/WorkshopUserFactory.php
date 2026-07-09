<?php

namespace Database\Factories;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkshopUser>
 */
class WorkshopUserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'user_id' => User::factory(),
            'role' => WorkshopUserRole::Staff,
        ];
    }

    public function owner(): static
    {
        return $this->state(['role' => WorkshopUserRole::Owner]);
    }
}
