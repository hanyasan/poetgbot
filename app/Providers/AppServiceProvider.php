<?php

namespace App\Providers;

use App\Services\PoeNinjaService\PoeNinjaService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\TelegramBotService\TelegramBotContract;
use App\Services\TelegramBotService\TelegramBotService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public array $singletons = [
        PoeNinjaServiceContract::class => PoeNinjaService::class,
        TelegramBotContract::class => TelegramBotService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
