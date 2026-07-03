<?php

namespace App\Providers;

use App\Support\Intake\IntakeExtractorInterface;
use App\Support\Intake\LlmIntakeExtractor;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use App\Support\Intake\OpenAiIntakeExtractionResultMapper;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
        //
    }
}
