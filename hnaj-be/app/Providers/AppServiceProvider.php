<?php

namespace App\Providers;

use App\Services\PlaceAiClassifier;
use App\Services\PlaceClassifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlaceClassifier::class, PlaceAiClassifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
