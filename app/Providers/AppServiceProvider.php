<?php

namespace App\Providers;

use Anthropic\Client;
use App\Ai\BriefingDrafter;
use App\Ai\ClaudeBriefingDrafter;
use App\Ai\FakeBriefingDrafter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BriefingDrafter::class, function () {
            if (config('services.ai.driver') === 'claude' && config('services.anthropic.key')) {
                return new ClaudeBriefingDrafter(
                    new Client(apiKey: config('services.anthropic.key')),
                    config('services.anthropic.model'),
                );
            }

            return new FakeBriefingDrafter;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
