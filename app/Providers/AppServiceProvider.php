<?php

namespace App\Providers;

use App\Support\Intake\IntakeExtractorInterface;
use App\Support\Intake\ManualFallbackIntakeExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IntakeExtractorInterface::class, ManualFallbackIntakeExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
