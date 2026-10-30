<?php

namespace Tests\Feature\Pricing;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreTypeDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeagueLatestPricesDto;
use App\Enums\PoeVersionEnum;
use App\Models\User;
use App\Services\DataServices\PoeLeague\PoeLeagueServiceContract;
use App\Services\Pricing\PricingServiceContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class PricingControllerTest extends TestCase
{
    private MockInterface|PoeLeagueServiceContract $leagueService;

    private MockInterface|PricingServiceContract $pricingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leagueService = $this->mock(PoeLeagueServiceContract::class);
        $this->pricingService = $this->mock(PricingServiceContract::class);
    }

    private function authenticated(): self
    {
        $this->actingAs(new User, 'api');

        return $this;
    }

    private function league(int $id, string $name, PoeVersionEnum $version): PoeLeagueDto
    {
        return new PoeLeagueDto(
            $id,
            Carbon::now()->toDateTimeString(),
            Carbon::now()->toDateTimeString(),
            $name,
            $version->value,
            true,
        );
    }

    private function latestPrices(int $coreId): LeagueLatestPricesDto
    {
        $now = Carbon::now()->toDateTimeString();

        $core = new PriceCheckCoreDto(
            $coreId,
            $now,
            $now,
            [
                new PriceCheckCoreTypeDto(1, $now, $now, true, 1.0, 'chaos-orb', 'Chaos', $coreId),
                new PriceCheckCoreTypeDto(2, $now, $now, false, 0.002, 'divine-orb', 'Divine', $coreId),
            ],
        );

        return new LeagueLatestPricesDto($core, [
            new CurrencyTypeWithPricesDto(
                1,
                'Divine Orb',
                $now,
                $now,
                [new CurrencyPriceDto(1, 150.0)],
                'divine',
                'divine-orb',
            ),
        ]);
    }

    public function test_latest_requires_authentication(): void
    {
        $this->pricingService->expects('getLeagueLatestPrices')->never();

        $this->getJson('/api/v1/pricing/latest?league_name=Standard&poe_version=poe2')
            ->assertUnauthorized();
    }

    public function test_latest_returns_formatted_prices_for_the_requested_league(): void
    {
        $league = $this->league(7, 'Standard', PoeVersionEnum::POE_2);
        $prices = $this->latestPrices(3);

        $this->leagueService
            ->expects('findActiveByNameVersion')
            ->once()
            ->with('Standard', PoeVersionEnum::POE_2)
            ->andReturns($league);

        $this->pricingService
            ->expects('getLeagueLatestPrices')
            ->once()
            ->with(7)
            ->andReturns($prices);

        $this->authenticated()
            ->getJson('/api/v1/pricing/latest?league_name=Standard&poe_version=poe2')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'priced_at' => $prices->core->created_at,
                    'priced_currency' => [
                        [
                            'currency_type_name' => 'Divine Orb',
                            'currency_trade_id' => 'divine',
                            'currency_ninja_details_id' => 'divine-orb',
                            'prices' => [
                                [
                                    'is_primary' => true,
                                    'currency_name' => 'Chaos',
                                    'currency_trade_id' => 'chaos-orb',
                                    'integral_value' => 150,
                                    'value' => 150.0,
                                ],
                                [
                                    'is_primary' => false,
                                    'currency_name' => 'Divine',
                                    'currency_trade_id' => 'divine-orb',
                                    'integral_value' => 1,
                                    'value' => 0.3,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_latest_returns_empty_currency_list_when_nothing_is_priced(): void
    {
        $this->leagueService
            ->expects('findActiveByNameVersion')
            ->andReturns($this->league(7, 'Standard', PoeVersionEnum::POE_1));

        $this->pricingService
            ->expects('getLeagueLatestPrices')
            ->andReturns(
                new LeagueLatestPricesDto(
                    new PriceCheckCoreDto(1, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), []),
                    [],
                )
            );

        $this->authenticated()
            ->getJson('/api/v1/pricing/latest?league_name=Standard&poe_version=poe1')
            ->assertOk()
            ->assertJsonPath('data.priced_currency', []);
    }

    public function test_latest_requires_a_league_name(): void
    {
        $this->leagueService->expects('findActiveByNameVersion')->never();

        $this->authenticated()
            ->getJson('/api/v1/pricing/latest?poe_version=poe2')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['league_name'], 'data.errors');
    }

    public function test_latest_requires_a_valid_poe_version(): void
    {
        $this->leagueService->expects('findActiveByNameVersion')->never();

        $this->authenticated()
            ->getJson('/api/v1/pricing/latest?league_name=Standard&poe_version=poe3')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['poe_version'], 'data.errors');
    }

    public function test_latest_returns_not_found_when_the_league_does_not_exist(): void
    {
        $this->leagueService
            ->expects('findActiveByNameVersion')
            ->andReturns($this->league(7, 'Standard', PoeVersionEnum::POE_2));

        $this->pricingService
            ->expects('getLeagueLatestPrices')
            ->andThrow(new ModelNotFoundException);

        $this->authenticated()
            ->getJson('/api/v1/pricing/latest?league_name=Standard&poe_version=poe2')
            ->assertNotFound();
    }
}
