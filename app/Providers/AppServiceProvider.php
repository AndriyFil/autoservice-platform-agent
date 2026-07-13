<?php

namespace App\Providers;

use App\Domain\CustomerPortal\Contracts\OtpProvider;
use App\Domain\CustomerPortal\Providers\LogOtpProvider;
use App\Domain\Shared\ValueObjects\Phone;
use App\Support\Intake\IntakeExtractorInterface;
use App\Support\Intake\LlmIntakeExtractor;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use App\Support\Intake\OpenAiIntakeExtractionResultMapper;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OtpProvider::class, function (Application $app): OtpProvider {
            if (! $app->environment('local')) {
                throw new LogicException('A Customer Portal OTP provider is not configured for this environment.');
            }

            return $app->make(LogOtpProvider::class);
        });

        $this->app->bind(IntakeExtractorInterface::class, function (Application $app) {
            $apiKey = config('services.openai.api_key');

            if (! is_string($apiKey) || $apiKey === '') {
                return $app->make(ManualFallbackIntakeExtractor::class);
            }

            return new LlmIntakeExtractor(
                resultMapper: $app->make(OpenAiIntakeExtractionResultMapper::class),
                fallbackExtractor: $app->make(ManualFallbackIntakeExtractor::class),
                apiKey: $apiKey,
                model: config('services.openai.intake_model'),
                baseUrl: config('services.openai.base_url'),
                timeoutSeconds: config('services.openai.timeout'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('customer-portal-code-request', function (Request $request): array {
            $phone = $request->input('phone');
            $phoneLimiterKey = is_string($phone)
                ? hash('sha256', (new Phone($phone))->normalize())
                : 'invalid-input';

            return [
                Limit::perMinute(5)->by('phone:'.$phoneLimiterKey),
                Limit::perHour(20)->by('ip:'.hash('sha256', (string) $request->ip())),
            ];
        });

        RateLimiter::for('customer-portal-code-verification', function (Request $request): Limit {
            $challengeId = (string) $request->session()->get('customer_portal.pending_challenge_id', 'missing');
            $key = $challengeId.'|'.(string) $request->ip();

            return Limit::perMinute(10)->by('challenge-ip:'.hash('sha256', $key));
        });
    }
}
