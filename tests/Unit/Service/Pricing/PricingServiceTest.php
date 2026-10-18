<?php

namespace Tests\Unit\Service\Pricing;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeagueLatestPricesDto;
use App\Enums\PoeVersionEnum;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\Pricing\PricingService;
use App\Services\Pricing\PricingServiceContract;
use Carbon\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    const int LEAGUE_ID = 1;
    const int CORE_ID = 1;

    private PricingServiceContract $pricingService;
    private MockInterface|PoeLeagueServiceContract $poeLeagueService;
    private MockInterface|PriceCheckCoreServiceContract $priceCheckCoreService;
    private MockInterface|CurrencyTypeServiceContract $currencyTypeService;

    public function setUp(): void
    {
        parent::setUp();

        $this->poeLeagueService = $this->mock(PoeLeagueServiceContract::class);
        $this->priceCheckCoreService = $this->mock(PriceCheckCoreServiceContract::class);
        $this->currencyTypeService = $this->mock(CurrencyTypeServiceContract::class);

        $this->pricingService = new PricingService(
            $this->poeLeagueService,
            $this->currencyTypeService,
            $this->priceCheckCoreService,
        );
    }

    private function league(int $id = self::LEAGUE_ID, PoeVersionEnum $version = PoeVersionEnum::POE_2, string $name = 'Test League'): PoeLeagueDto
    {
        return new PoeLeagueDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), $name, $version->value, true);
    }

    private function core(int $id = self::CORE_ID): PriceCheckCoreDto
    {
        return new PriceCheckCoreDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), []);
    }

    private function typeWithPrices(int $id, float|null $rate): CurrencyTypeWithPricesDto
    {
        return new CurrencyTypeWithPricesDto(
            $id,
            'Type ' . $id,
            '2024-01-01T00:00:00.000000Z',
            '2024-01-01T00:00:00.000000Z',
            is_null($rate) ? [] : [new CurrencyPriceDto($id * 100, $rate)],
            'trade-' . $id,
            'detail-' . $id,
        );
    }

    public function test_get_league_latest_prices(): void
    {
        $this->poeLeagueService->expects('find')->andReturn($this->league());
        $this->priceCheckCoreService->expects('getLatestByLeague')->andReturn($this->core());
        $this->currencyTypeService->expects('getAllByLeagueWithPriceByCore')->andReturn(collect([
            $this->typeWithPrices(1, 10),
            $this->typeWithPrices(2, 10),
        ]));

        $result = $this->pricingService->getLeagueLatestPrices($this->league()->id);

        $this->assertSame(LeagueLatestPricesDto::class, $result::class);
    }

    public function test_get_league_latest_prices_filters_types_without_prices(): void
    {
        $this->poeLeagueService->expects('find')->andReturn($this->league());
        $this->priceCheckCoreService->expects('getLatestByLeague')->andReturn($this->core());
        $this->currencyTypeService->expects('getAllByLeagueWithPriceByCore')->andReturn(collect([
            $this->typeWithPrices(1, 10),
            $this->typeWithPrices(1, null),
        ]));

        $result = $this->pricingService->getLeagueLatestPrices($this->league()->id);

        $this->assertSame(1, count($result->prices));
    }
}
