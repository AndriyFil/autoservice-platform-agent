<?php

namespace Tests\Feature;

use App\Domain\CustomerPortal\Contracts\OtpProvider;
use App\Models\Customer;
use App\Models\CustomerPhoneVerification;
use App\Models\Workshop;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Fakes\FakeOtpProvider;
use Tests\TestCase;

class CustomerPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private FakeOtpProvider $otpProvider;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-07-13 12:00:00');

        $this->otpProvider = new FakeOtpProvider;
        $this->app->instance(OtpProvider::class, $this->otpProvider);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_access_page_uses_the_customer_portal_phone_entry_component(): void
    {
        $this->get('/my-requests/access')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/RequestAccess')
                ->where('sessionExpired', false));
    }

    public function test_verification_page_exposes_only_a_masked_pending_phone(): void
    {
        $this->withSession([
            'customer_portal.pending_challenge_id' => 42,
            'customer_portal.pending_phone' => '+15551000015',
        ])->get('/my-requests/verify')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/VerifyCode')
                ->where('maskedPhone', '••••••••0015')
                ->missing('phone'));
    }

    public function test_verified_customer_uses_the_empty_customer_portal_index_component(): void
    {
        $this->withSession($this->activeVerifiedSession())
            ->get('/my-requests')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/Index')
                ->has('recentRequests', 0)
                ->where('hasMoreRequests', false)
                ->has('requests.data', 0));
    }

    public function test_access_page_receives_expired_session_state(): void
    {
        $this->withSession(['customer_portal.session_expired' => true])
            ->get('/my-requests/access')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/RequestAccess')
                ->where('sessionExpired', true));
    }

    public function test_requesting_a_code_normalizes_the_phone_and_never_stores_the_raw_otp(): void
    {
        $usersBefore = $this->databaseCount('users');
        $customersBefore = $this->databaseCount('customers');

        $this->post('/my-requests/access', [
            'phone' => '+38 (050) 111-22-33',
        ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('customer_portal.pending_phone', '+380501112233')
            ->assertRedirect('/my-requests/verify');

        $challenge = CustomerPhoneVerification::sole();
        $rawCode = $this->otpProvider->latestCodeFor('+380501112233');

        $this->assertSame('+380501112233', $challenge->phone_normalized);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $rawCode);
        $this->assertTrue(Hash::check($rawCode, $challenge->code_hash));
        $this->assertNotContains($rawCode, $challenge->getAttributes(), true);
        $this->assertSame($usersBefore, $this->databaseCount('users'));
        $this->assertSame($customersBefore, $this->databaseCount('customers'));
    }

    public function test_a_valid_code_creates_a_short_lived_verified_phone_session(): void
    {
        $usersBefore = $this->databaseCount('users');
        $customersBefore = $this->databaseCount('customers');
        $challenge = $this->requestCode('068 562 00 40');
        $code = $this->otpProvider->latestCodeFor('+380685620040');
        $sessionIdBeforeVerification = session()->getId();

        $this->post('/my-requests/verify', ['code' => $code])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('customer_portal.verified_phone', '+380685620040')
            ->assertSessionHas('customer_portal.verified_until', now()->addMinutes(30)->timestamp)
            ->assertSessionMissing('customer_portal.pending_challenge_id')
            ->assertSessionMissing('customer_portal.pending_phone')
            ->assertRedirect('/my-requests');

        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertNotSame($sessionIdBeforeVerification, session()->getId());
        $this->get('/my-requests')->assertOk();
        $this->assertSame($usersBefore, $this->databaseCount('users'));
        $this->assertSame($customersBefore, $this->databaseCount('customers'));
    }

    public function test_code_issuance_takes_a_transaction_scoped_phone_advisory_lock(): void
    {
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->requestCode('+1 (555) 100-0010');

        $this->assertTrue(
            collect($queries)->contains(
                fn (string $query): bool => str_contains($query, 'pg_advisory_xact_lock'),
            ),
            'OTP issuance must serialize each normalized phone with pg_advisory_xact_lock.',
        );
    }

    public function test_provider_failure_rolls_back_new_challenge_and_preserves_previous_live_challenge(): void
    {
        $existingChallenge = $this->requestCode('+1 (555) 100-0011');

        $this->app->instance(OtpProvider::class, new class implements OtpProvider
        {
            public function send(string $normalizedPhone, string $code): void
            {
                throw new RuntimeException('Provider failed.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->post('/my-requests/access', ['phone' => '+1 (555) 100-0011']);
            $this->fail('The provider failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Provider failed.', $exception->getMessage());
        }

        $this->assertDatabaseCount('customer_phone_verifications', 1);
        $this->assertNull($existingChallenge->fresh()->invalidated_at);
    }

    public function test_an_invalid_code_is_rejected_and_increments_attempts(): void
    {
        $challenge = $this->requestCode('+1 (555) 100-0001');
        $invalidCode = $this->invalidCodeFor('+15551000001');

        $this->from('/my-requests/verify')
            ->post('/my-requests/verify', ['code' => $invalidCode])
            ->assertSessionHasErrors('code')
            ->assertRedirect('/my-requests/verify');

        $this->assertSame(1, $challenge->fresh()->attempts);
        $this->assertNull(session('customer_portal.verified_phone'));
    }

    public function test_an_expired_code_is_rejected(): void
    {
        $challenge = $this->requestCode('+1 (555) 100-0002');
        $code = $this->otpProvider->latestCodeFor('+15551000002');

        $challenge->forceFill(['expires_at' => now()->subSecond()])->save();

        $this->post('/my-requests/verify', ['code' => $code])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('customer_portal.verified_phone'));
    }

    public function test_a_code_is_invalidated_after_the_maximum_number_of_attempts(): void
    {
        $challenge = $this->requestCode('+1 (555) 100-0003');
        $invalidCode = $this->invalidCodeFor('+15551000003');

        foreach (range(1, 5) as $attempt) {
            $this->post('/my-requests/verify', ['code' => $invalidCode])
                ->assertSessionHasErrors('code');
        }

        $challenge->refresh();

        $this->assertSame(5, $challenge->attempts);
        $this->assertNotNull($challenge->invalidated_at);

        $validCode = $this->otpProvider->latestCodeFor('+15551000003');

        $this->post('/my-requests/verify', ['code' => $validCode])
            ->assertSessionHasErrors('code');
    }

    public function test_a_consumed_code_cannot_be_reused(): void
    {
        $challenge = $this->requestCode('+1 (555) 100-0004');
        $code = $this->otpProvider->latestCodeFor('+15551000004');

        $this->post('/my-requests/verify', ['code' => $code])
            ->assertRedirect('/my-requests');

        $this->withSession([
            'customer_portal.pending_challenge_id' => $challenge->id,
            'customer_portal.pending_phone' => '+15551000004',
        ])->post('/my-requests/verify', ['code' => $code])
            ->assertSessionHasErrors('code');
    }

    public function test_requesting_another_code_invalidates_the_previous_live_challenge(): void
    {
        $firstChallenge = $this->requestCode('+1 (555) 100-0005');
        $firstCode = $this->otpProvider->latestCodeFor('+15551000005');
        $secondChallenge = $this->requestCode('+1 (555) 100-0005');

        $this->assertNotNull($firstChallenge->fresh()->invalidated_at);
        $this->assertNull($secondChallenge->invalidated_at);

        $this->withSession([
            'customer_portal.pending_challenge_id' => $firstChallenge->id,
            'customer_portal.pending_phone' => '+15551000005',
        ])->post('/my-requests/verify', ['code' => $firstCode])
            ->assertSessionHasErrors('code');
    }

    public function test_code_request_route_is_rate_limited_per_normalized_phone(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/my-requests/access', [
                'phone' => '+38 (067) 200-00-01',
            ])->assertRedirect('/my-requests/verify');
        }

        $this->post('/my-requests/access', [
            'phone' => '0672000001',
        ])->assertTooManyRequests();
    }

    public function test_array_shaped_phone_input_is_rejected_by_validation_without_creating_a_challenge(): void
    {
        $this->post('/my-requests/access', [
            'phone' => ['+1 (555) 100-0020'],
        ])
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('customer_phone_verifications', 0);
    }

    public function test_code_request_route_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 20) as $attempt) {
            $phone = '+1555'.str_pad((string) (2000000 + $attempt), 7, '0', STR_PAD_LEFT);

            $this->post('/my-requests/access', ['phone' => $phone])
                ->assertRedirect('/my-requests/verify');
        }

        $this->post('/my-requests/access', [
            'phone' => '+15553000001',
        ])->assertTooManyRequests();
    }

    public function test_code_verification_route_is_rate_limited_per_challenge_and_ip(): void
    {
        $this->requestCode('+1 (555) 100-0006');
        $invalidCode = $this->invalidCodeFor('+15551000006');

        foreach (range(1, 10) as $attempt) {
            $this->post('/my-requests/verify', ['code' => $invalidCode])
                ->assertSessionHasErrors('code');
        }

        $this->post('/my-requests/verify', ['code' => $invalidCode])
            ->assertTooManyRequests();
    }

    public function test_an_unverified_visitor_cannot_open_the_placeholder(): void
    {
        $this->get('/my-requests')
            ->assertRedirect('/my-requests/access');
    }

    public function test_an_active_verified_session_cannot_open_the_access_page(): void
    {
        $this->withSession($this->activeVerifiedSession())
            ->get('/my-requests/access')
            ->assertRedirect('/my-requests');
    }

    public function test_an_active_verified_session_cannot_request_another_code_directly(): void
    {
        $this->withSession($this->activeVerifiedSession())
            ->post('/my-requests/access', ['phone' => '+1 (555) 100-0012'])
            ->assertRedirect('/my-requests');

        $this->assertDatabaseCount('customer_phone_verifications', 0);
    }

    public function test_code_request_behavior_does_not_reveal_whether_a_customer_phone_exists(): void
    {
        $workshop = Workshop::factory()->create();
        Customer::factory()->create([
            'workshop_id' => $workshop->id,
            'phone' => '+15551000013',
            'phone_normalized' => '+15551000013',
            'normalized_phone' => '15551000013',
        ]);
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $existingResponse = $this->post('/my-requests/access', [
            'phone' => '+1 (555) 100-0013',
        ]);
        $absentResponse = $this->post('/my-requests/access', [
            'phone' => '+1 (555) 100-0014',
        ]);

        $existingResponse->assertRedirect('/my-requests/verify');
        $absentResponse->assertRedirect('/my-requests/verify');
        $this->assertSame($existingResponse->getStatusCode(), $absentResponse->getStatusCode());
        $this->assertSame($existingResponse->headers->get('Location'), $absentResponse->headers->get('Location'));
        $this->assertFalse(
            collect($queries)->contains(
                fn (string $query): bool => str_contains(strtolower($query), 'customers'),
            ),
            'OTP requests must not query Customer records.',
        );
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_an_expired_verified_session_is_cleared_and_redirected_to_access(): void
    {
        $this->withSession([
            'customer_portal.verified_phone' => '+15551000007',
            'customer_portal.verified_until' => now()->subSecond()->timestamp,
        ])->get('/my-requests')
            ->assertSessionMissing('customer_portal.verified_phone')
            ->assertSessionMissing('customer_portal.verified_until')
            ->assertSessionHas('customer_portal.session_expired', true)
            ->assertRedirect('/my-requests/access');
    }

    private function requestCode(string $phone): CustomerPhoneVerification
    {
        $this->post('/my-requests/access', ['phone' => $phone])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/my-requests/verify');

        $challengeId = session('customer_portal.pending_challenge_id');

        $this->assertIsInt($challengeId);

        return CustomerPhoneVerification::query()->findOrFail($challengeId);
    }

    private function databaseCount(string $table): int
    {
        return (int) $this->getConnection()->table($table)->count();
    }

    private function invalidCodeFor(string $normalizedPhone): string
    {
        return $this->otpProvider->latestCodeFor($normalizedPhone) === '000000'
            ? '000001'
            : '000000';
    }

    /** @return array<string, int|string> */
    private function activeVerifiedSession(): array
    {
        return [
            'customer_portal.verified_phone' => '+15551000012',
            'customer_portal.verified_until' => now()->addMinutes(10)->timestamp,
        ];
    }
}
