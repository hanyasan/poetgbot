<?php

namespace App\Providers;

use App\Repositories\CurrencyPrice\CurrencyPriceRepository;
use App\Repositories\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Repositories\CurrencyType\CurrencyTypeRepository;
use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\DataServices\CurrencyPriceService\CurrencyPriceService;
use App\Services\DataServices\CurrencyPriceService\CurrencyPriceServiceContract;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeService;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\Mapper\MapperService;
use App\Services\PoeNinjaService\PoeNinjaService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\TelegramBotService\TelegramBotContract;
use App\Services\TelegramBotService\TelegramBotService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public array $singletons = [
        PoeNinjaServiceContract::class => PoeNinjaService::class,
        TelegramBotContract::class => TelegramBotService::class,
        CurrencyTypeRepositoryContract::class => CurrencyTypeRepository::class,
        MapperService::class => MapperService::class,
        CurrencyTypeServiceContract::class => CurrencyTypeService::class,
        CurrencyPriceRepositoryContract::class => CurrencyPriceRepository::class,
        CurrencyPriceServiceContract::class => CurrencyPriceService::class,
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
        Response::macro('okResponse', function (string $value) {
            return [
                'success' => true,
                'data' => $value,
            ];
        });
    }
}
