<?php

namespace Tests\Feature;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicAdminDomainRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('PUBLIC_APP_URL=http://autoservice.test:8080');
        putenv('ADMIN_APP_URL=http://admin.autoservice.test:8080');

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('PUBLIC_APP_URL');
        putenv('ADMIN_APP_URL');
    }

    public function test_public_homepage_works_on_public_host_with_admin_links(): void
    {
        $this->get('http://autoservice.test:8080/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('adminLoginUrl', 'http://admin.autoservice.test:8080/login')
                ->where('adminRegisterUrl', 'http://admin.autoservice.test:8080/register'));
    }

    public function test_public_workshop_intake_page_works_on_public_host(): void
    {
        Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $this->get('http://autoservice.test:8080/w/main-auto')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicIntake')
                ->where('workshop.name', 'Main Auto')
                ->where('workshop.slug', 'main-auto'));
    }

    public function test_public_intake_post_stays_public_on_public_host(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->post('http://autoservice.test:8080/w/main-auto/intake', [
            'message' => 'Opel Insignia, check engine light came on.',
            'phone' => '+1 (555) 123-4567',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('http://autoservice.test:8080/w/main-auto');

        $this->assertGuest();
        $this->assertDatabaseHas('booking_requests', [
            'customer_phone' => '+1 (555) 123-4567',
        ]);
    }

    public function test_admin_login_and_register_work_on_admin_host(): void
    {
        $this->get('http://admin.autoservice.test:8080/login')->assertOk();
        $this->get('http://admin.autoservice.test:8080/register')->assertOk();
    }

    public function test_admin_dashboard_requires_auth_on_admin_host(): void
    {
        $this->get('http://admin.autoservice.test:8080/dashboard')
            ->assertRedirect('http://admin.autoservice.test:8080/login');
    }

    public function test_successful_login_redirects_to_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('http://admin.autoservice.test:8080/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect('http://admin.autoservice.test:8080/dashboard');
    }

    public function test_successful_registration_redirects_to_admin_dashboard(): void
    {
        $this->post('http://admin.autoservice.test:8080/register', [
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect('http://admin.autoservice.test:8080/dashboard');
    }

    public function test_dashboard_is_not_available_on_public_host_when_hosts_are_split(): void
    {
        $this->get('http://autoservice.test:8080/dashboard')->assertNotFound();
    }

    public function test_workshop_settings_public_intake_link_uses_public_app_url(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        WorkshopUser::factory()->create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'role' => WorkshopUserRole::Owner,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('http://admin.autoservice.test:8080/dashboard/workshop/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Workshop/Settings')
                ->where('workshop.publicIntakePath', '/w/main-auto')
                ->where('workshop.publicIntakeUrl', 'http://autoservice.test:8080/w/main-auto'));
    }
}
