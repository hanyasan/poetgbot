<?php

namespace App\Providers;

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
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeService;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreService;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeagueService\PoeLeagueService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\HTTPClientServices\NinjaClient;
use App\Services\HTTPClientServices\NinjaClientContract;
use App\Services\Mapper\MapperService;
use App\Services\PoeNinjaService\PoeNinjaService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerService;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerServiceContract;
use App\Services\PriceCheckServices\PriceCheckService\PriceCheckService;
use App\Services\PriceCheckServices\PriceCheckService\PriceCheckServiceContract;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\PricingServiceContract;
use App\Services\TelegramBotService\TelegramBotContract;
use App\Services\TelegramBotService\TelegramBotService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
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
        LeagueCheckPickerServiceContract::class => LeagueCheckPickerService::class,
        LeagueRuleRepositoryContract::class => LeagueRuleRepository::class,
        PricingServiceContract::class => PricingService::class,
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
