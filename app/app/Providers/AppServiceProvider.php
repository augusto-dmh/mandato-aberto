<?php

namespace App\Providers;

use App\Contract\JsonFiles;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(JsonFiles::class, fn () => new JsonFiles((string) config('mandato.schema_dir')));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
