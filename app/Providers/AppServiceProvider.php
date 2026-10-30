<?php

namespace App\Providers;

use App\HTTPClients\NinjaClient;
use App\HTTPClients\NinjaClientContract;
use App\Repositories\Currency\Compact\CurrencyCompactRepository;
use App\Repositories\Currency\Compact\CurrencyCompactRepositoryContract;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepository;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepository;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepositoryContract;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepository;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Repositories\PoeLeague\LeagueRuleRepository;
use App\Repositories\PoeLeague\LeagueRuleRepositoryContract;
use App\Repositories\PoeLeague\PoeLeagueRepository;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\DataServices\Currency\CurrencyCompactService\CurrencyCompactService;
use App\Services\DataServices\Currency\CurrencyCompactService\CurrencyCompactServiceContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeService;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreService;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeague\PoeLeagueService;
use App\Services\DataServices\PoeLeague\PoeLeagueServiceContract;
use App\Services\PoeNinja\PoeNinjaService;
use App\Services\PoeNinja\PoeNinjaServiceContract;
use App\Services\PriceCheck\PriceCheckService;
use App\Services\PriceCheck\PriceCheckServiceContract;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\PricingServiceContract;
use App\Services\TelegramBot\TelegramBotContract;
use App\Services\TelegramBot\TelegramBotService;
use App\Utility\MapperService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/** @psalm-suppress UnusedClass */
final class AppServiceProvider extends ServiceProvider
{
    public array $singletons = [
        NinjaClientContract::class => NinjaClient::class,
        PoeNinjaServiceContract::class => PoeNinjaService::class,
        TelegramBotContract::class => TelegramBotService::class,
        CurrencyTypeRepositoryContract::class => CurrencyTypeRepository::class,
        CurrencyCompactRepositoryContract::class => CurrencyCompactRepository::class,
        MapperService::class => MapperService::class,
        CurrencyTypeServiceContract::class => CurrencyTypeService::class,
        CurrencyPriceRepositoryContract::class => CurrencyPriceRepository::class,
        PoeLeagueRepositoryContract::class => PoeLeagueRepository::class,
        PoeLeagueServiceContract::class => PoeLeagueService::class,
        PriceCheckCoreRepositoryContract::class => PriceCheckCoreRepository::class,
        PriceCheckCoreServiceContract::class => PriceCheckCoreService::class,
        PriceCheckServiceContract::class => PriceCheckService::class,
        LeagueRuleRepositoryContract::class => LeagueRuleRepository::class,
        PricingServiceContract::class => PricingService::class,
        CurrencyCompactServiceContract::class => CurrencyCompactService::class,
    ];

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
