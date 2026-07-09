<?php

namespace Tests\Feature;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureActiveWorkshopMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_routes_redirect_to_onboarding_without_active_workshop_membership(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('workshop-onboarding.create'));
    }

    public function test_dashboard_routes_receive_active_workshop_membership_when_available(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        WorkshopUser::create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'role' => WorkshopUserRole::Owner,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activeWorkshop.id', $workshop->id)
                ->where('activeWorkshop.name', 'Main Auto')
                ->where('activeWorkshop.slug', 'main-auto')
                ->where('auth.activeWorkshopUser.role', 'owner')
                ->where('auth.activeWorkshopUser.workshopId', $workshop->id));
    }

    public function test_workshop_onboarding_route_does_not_require_active_workshop_membership(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('workshop-onboarding.create'))
            ->assertOk();
    }
}
