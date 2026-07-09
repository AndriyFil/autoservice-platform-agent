<?php

namespace Tests\Feature;

use App\Domain\Workshops\Actions\AddWorkshopStaffAction;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Domain\Workshops\Queries\WorkshopStaffQuery;
use App\Models\Customer;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkshopAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_domain_classes_resolve_from_domain_namespace(): void
    {
        $this->assertSame('owner', WorkshopUserRole::Owner->value);
        $this->assertTrue(class_exists(AddWorkshopStaffAction::class));
        $this->assertTrue(class_exists(WorkshopStaffQuery::class));
    }

    public function test_owner_can_view_workshop_settings(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner, [
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.workshop.settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Workshop/Settings')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('workshop.name', 'Main Auto')
                ->where('workshop.slug', 'main-auto')
                ->where('workshop.publicIntakePath', '/w/main-auto')
                ->has('staffMembers', 1)
                ->where('staffMembers.0.role.value', 'owner')
                ->where('staffMembers.0.isCurrentUser', true));
    }

    public function test_staff_cannot_view_workshop_settings(): void
    {
        [$staff, $workshop] = $this->createMember(WorkshopUserRole::Staff);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.workshop.settings.show'))
            ->assertForbidden();
    }

    public function test_owner_can_update_workshop_name_and_slug(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner, [
            'name' => 'Old Auto',
            'slug' => 'old-auto',
        ]);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.settings.update'), [
                'name' => 'New Auto',
                'slug' => 'new-auto',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHas('status', 'Workshop settings updated.');

        $this->assertDatabaseHas('workshops', [
            'id' => $workshop->id,
            'name' => 'New Auto',
            'slug' => 'new-auto',
        ]);

        $this
            ->get(route('public-intake.create', ['workshop' => 'new-auto']))
            ->assertOk();
    }

    public function test_workshop_slug_must_be_unique_except_current_workshop(): void
    {
        Workshop::factory()->create(['slug' => 'taken-auto']);
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner, [
            'slug' => 'current-auto',
        ]);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.workshop.settings.show'))
            ->patch(route('dashboard.workshop.settings.update'), [
                'name' => 'Current Auto',
                'slug' => 'taken-auto',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHasErrors('slug');

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.settings.update'), [
                'name' => 'Current Auto',
                'slug' => 'current-auto',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionDoesntHaveErrors();
    }

    public function test_workshop_slug_uses_onboarding_format_rule(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.workshop.settings.show'))
            ->patch(route('dashboard.workshop.settings.update'), [
                'name' => 'Main Auto',
                'slug' => 'Main Auto',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHasErrors('slug');
    }

    public function test_staff_cannot_update_workshop_settings(): void
    {
        [$staff, $workshop] = $this->createMember(WorkshopUserRole::Staff, [
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.settings.update'), [
                'name' => 'Changed Auto',
                'slug' => 'changed-auto',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('workshops', [
            'id' => $workshop->id,
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
    }

    public function test_staff_cannot_manage_staff_memberships(): void
    {
        [$staff, $workshop] = $this->createMember(WorkshopUserRole::Staff);
        $owner = User::factory()->create();
        $ownerMembership = $this->attachMember($owner, $workshop, WorkshopUserRole::Owner);
        $newUser = User::factory()->create(['email' => 'new-staff@example.com']);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'New Staff',
                'email' => 'new-staff@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'staff',
            ])
            ->assertForbidden();

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.staff.update', $ownerMembership), [
                'role' => 'staff',
            ])
            ->assertForbidden();

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->delete(route('dashboard.workshop.staff.destroy', $ownerMembership))
            ->assertForbidden();

        $this->assertDatabaseMissing('workshop_users', [
            'workshop_id' => $workshop->id,
            'user_id' => $newUser->id,
        ]);
        $this->assertDatabaseHas('workshop_users', [
            'id' => $ownerMembership->id,
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_create_new_staff_user(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Jane Staff',
                'email' => 'jane@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHas('status', 'Staff member added.');

        $createdUser = User::query()->where('email', 'jane@example.com')->firstOrFail();

        $this->assertSame('Jane Staff', $createdUser->name);
        $this->assertTrue(Hash::check('secure-password', $createdUser->password));
        $this->assertNotSame('secure-password', $createdUser->password);
        $this->assertDatabaseHas('workshop_users', [
            'workshop_id' => $workshop->id,
            'user_id' => $createdUser->id,
            'role' => 'staff',
        ]);
    }

    public function test_owner_can_create_new_staff_user_with_owner_role(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Second Owner',
                'email' => 'second-owner@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'owner',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'));

        $createdUser = User::query()->where('email', 'second-owner@example.com')->firstOrFail();

        $this->assertDatabaseHas('workshop_users', [
            'workshop_id' => $workshop->id,
            'user_id' => $createdUser->id,
            'role' => 'owner',
        ]);
    }

    public function test_staff_creation_ignores_request_workshop_id(): void
    {
        [$owner, $activeWorkshop] = $this->createMember(WorkshopUserRole::Owner);
        $otherWorkshop = Workshop::factory()->create();

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Scoped Staff',
                'email' => 'scoped-staff@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'staff',
                'workshop_id' => $otherWorkshop->id,
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'));

        $createdUser = User::query()->where('email', 'scoped-staff@example.com')->firstOrFail();

        $this->assertDatabaseHas('workshop_users', [
            'workshop_id' => $activeWorkshop->id,
            'user_id' => $createdUser->id,
            'role' => 'staff',
        ]);
        $this->assertDatabaseMissing('workshop_users', [
            'workshop_id' => $otherWorkshop->id,
            'user_id' => $createdUser->id,
        ]);
    }

    public function test_owner_can_attach_existing_user_as_staff_without_changing_password_or_name(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);
        $originalPassword = Hash::make('original-password');
        $user = User::factory()->create([
            'name' => 'Jane Staff',
            'email' => 'jane@example.com',
            'password' => $originalPassword,
        ]);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Changed Name',
                'email' => 'jane@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHas('status', 'Staff member added.');

        $this->assertDatabaseHas('workshop_users', [
            'workshop_id' => $workshop->id,
            'user_id' => $user->id,
            'role' => 'staff',
        ]);

        $user->refresh();

        $this->assertSame('Jane Staff', $user->name);
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue(Hash::check('original-password', $user->password));
        $this->assertFalse(Hash::check('new-password', $user->password));
    }

    public function test_owner_cannot_add_same_user_twice_to_workshop(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $this->attachMember($user, $workshop, WorkshopUserRole::Staff);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.workshop.settings.show'))
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Jane Staff',
                'email' => 'jane@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHasErrors('email');

        $this->assertSame(2, WorkshopUser::query()->where('workshop_id', $workshop->id)->count());
    }

    public function test_owner_can_change_staff_role_to_owner(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);
        $staff = User::factory()->create();
        $staffMembership = $this->attachMember($staff, $workshop, WorkshopUserRole::Staff);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.staff.update', $staffMembership), [
                'role' => 'owner',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'));

        $this->assertDatabaseHas('workshop_users', [
            'id' => $staffMembership->id,
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_demote_owner_only_when_another_owner_remains(): void
    {
        [$owner, $workshop, $ownerMembership] = $this->createMember(WorkshopUserRole::Owner);
        $secondOwner = User::factory()->create();
        $secondOwnerMembership = $this->attachMember($secondOwner, $workshop, WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.workshop.staff.update', $secondOwnerMembership), [
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'));

        $this->assertDatabaseHas('workshop_users', [
            'id' => $secondOwnerMembership->id,
            'role' => 'staff',
        ]);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.workshop.settings.show'))
            ->patch(route('dashboard.workshop.staff.update', $ownerMembership), [
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('workshop_users', [
            'id' => $ownerMembership->id,
            'role' => 'owner',
        ]);
    }

    public function test_owner_cannot_remove_last_owner(): void
    {
        [$owner, $workshop, $ownerMembership] = $this->createMember(WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.workshop.settings.show'))
            ->delete(route('dashboard.workshop.staff.destroy', $ownerMembership))
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHasErrors('membership');

        $this->assertDatabaseHas('workshop_users', [
            'id' => $ownerMembership->id,
            'role' => 'owner',
        ]);
    }

    public function test_owner_can_remove_staff_membership_without_deleting_user(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);
        $staff = User::factory()->create();
        $staffMembership = $this->attachMember($staff, $workshop, WorkshopUserRole::Staff);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->delete(route('dashboard.workshop.staff.destroy', $staffMembership))
            ->assertRedirect(route('dashboard.workshop.settings.show'))
            ->assertSessionHas('status', 'Staff member removed.');

        $this->assertDatabaseMissing('workshop_users', [
            'id' => $staffMembership->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
        ]);
    }

    public function test_cross_workshop_staff_update_and_removal_are_forbidden(): void
    {
        [$owner, $activeWorkshop] = $this->createMember(WorkshopUserRole::Owner);
        $otherWorkshop = Workshop::factory()->create();
        $otherUser = User::factory()->create();
        $otherMembership = $this->attachMember($otherUser, $otherWorkshop, WorkshopUserRole::Staff);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('dashboard.workshop.staff.update', $otherMembership), [
                'role' => 'owner',
            ])
            ->assertNotFound();

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->delete(route('dashboard.workshop.staff.destroy', $otherMembership))
            ->assertNotFound();
    }

    public function test_staff_cross_workshop_membership_guessing_is_hidden(): void
    {
        [$staff, $activeWorkshop] = $this->createMember(WorkshopUserRole::Staff);
        $otherWorkshop = Workshop::factory()->create();
        $otherUser = User::factory()->create();
        $otherMembership = $this->attachMember($otherUser, $otherWorkshop, WorkshopUserRole::Owner);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('dashboard.workshop.staff.update', $otherMembership), [
                'role' => 'staff',
            ])
            ->assertNotFound();

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->delete(route('dashboard.workshop.staff.destroy', $otherMembership))
            ->assertNotFound();
    }

    public function test_staff_management_does_not_create_customers(): void
    {
        [$owner, $workshop] = $this->createMember(WorkshopUserRole::Owner);

        $this
            ->actingAs($owner)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.workshop.staff.store'), [
                'name' => 'Jane Staff',
                'email' => 'new-staff@example.com',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
                'role' => 'staff',
            ])
            ->assertRedirect(route('dashboard.workshop.settings.show'));

        $this->assertDatabaseHas('users', ['email' => 'new-staff@example.com']);
        $this->assertSame(0, Customer::query()->count());
    }

    /**
     * @param  array<string, mixed>  $workshopAttributes
     * @return array{0: User, 1: Workshop, 2: WorkshopUser}
     */
    private function createMember(WorkshopUserRole $role, array $workshopAttributes = []): array
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create($workshopAttributes);
        $membership = $this->attachMember($user, $workshop, $role);

        return [$user, $workshop, $membership];
    }

    private function attachMember(User $user, Workshop $workshop, WorkshopUserRole $role): WorkshopUser
    {
        return WorkshopUser::create([
            'workshop_id' => $workshop->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }
}
