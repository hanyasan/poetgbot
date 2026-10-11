<?php

namespace Tests\Unit\Service\PriceCheckService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeCore;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeLine;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeSparkline;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore as PriceCheckCoreModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\PriceCheckServices\PriceCheckService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class PriceCheckServiceTest extends TestCase
{
    private const LEAGUE_ID = 10;

    private const CORE_ID = 77;

    private MockInterface|PoeLeagueServiceContract $leagueService;

    private MockInterface|CurrencyTypeServiceContract $currencyTypeService;

    private MockInterface|PriceCheckCoreServiceContract $coreService;

    private MockInterface|PoeNinjaServiceContract $ninjaService;

    private MockInterface|CurrencyPriceRepositoryContract $priceRepository;

    private PriceCheckService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->leagueService = Mockery::mock(PoeLeagueServiceContract::class);
        $this->currencyTypeService = Mockery::mock(CurrencyTypeServiceContract::class);
        $this->coreService = Mockery::mock(PriceCheckCoreServiceContract::class);
        $this->ninjaService = Mockery::mock(PoeNinjaServiceContract::class);
        $this->priceRepository = Mockery::mock(CurrencyPriceRepositoryContract::class);

        $this->service = new PriceCheckService(
            $this->leagueService,
            $this->currencyTypeService,
            $this->coreService,
            $this->ninjaService,
            $this->priceRepository,
        );
    }

    private function league(int $id = self::LEAGUE_ID, PoeVersionEnum $version = PoeVersionEnum::POE_2): PoeLeagueDto
    {
        return new PoeLeagueDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), 'Test League', $version->value, true);
    }

    private function core(int $id = self::CORE_ID): PriceCheckCoreDto
    {
        return new PriceCheckCoreDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), []);
    }

    private function item(string $id, string $name, string $detailsId): PoeNinjaExchangeItem
    {
        return new PoeNinjaExchangeItem($id, $name, 'image.png', 'Currency', $detailsId);
    }

    private function line(string $id, float $primaryValue): PoeNinjaExchangeLine
    {
        return new PoeNinjaExchangeLine($id, $primaryValue, 0.0, 'divine', 0.0, new PoeNinjaExchangeSparkline(null, []));
    }

    /**
     * @param PoeNinjaExchangeItem[] $items
     * @param PoeNinjaExchangeLine[] $lines
     */
    private function exchange(array $items, array $lines): PoeNinjaExchangeResponse
    {
        return new PoeNinjaExchangeResponse(
            new PoeNinjaExchangeCore($items, ['chaos' => 0.25], 'divine', 'chaos'),
            $lines,
            $items,
        );
    }

    private function type(int $id, string $tradeId): CurrencyTypeDto
    {
        return new CurrencyTypeDto($id, 'Type ' . $id, '2024-01-01T00:00:00.000000Z', '2024-01-01T00:00:00.000000Z', $tradeId, $tradeId . '-details');
    }

    private function typeWithPrice(int $id, string $tradeId, float $rate): CurrencyTypeWithPricesDto
    {
        return new CurrencyTypeWithPricesDto(
            $id,
            'Type ' . $id,
            '2024-01-01T00:00:00.000000Z',
            '2024-01-01T00:00:00.000000Z',
            [new CurrencyPriceDto($id * 100, $rate)],
            $tradeId,
            $tradeId . '-details',
        );
    }

    /**
     * Wires every collaborator for a successful import of two currencies and
     * returns the DTOs the service is expected to hand back.
     *
     * @return array{exchange: PoeNinjaExchangeResponse, league: PoeLeagueDto, core: PriceCheckCoreDto, priced: \Illuminate\Support\Collection}
     */
    private function arrangeSuccessfulImport(): array
    {
        $league = $this->league();
        $core = $this->core();
        $exchange = $this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            [$this->line('divine', 1.0), $this->line('chaos', 0.25)],
        );
        $priced = collect([$this->typeWithPrice(1, 'divine', 1.0), $this->typeWithPrice(2, 'chaos', 0.25)]);

        $this->leagueService->shouldReceive('find')->once()->with(self::LEAGUE_ID)->andReturn($league)->ordered();
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()
            ->with(PoeVersionEnum::POE_2, 'Test League')->andReturn($exchange)->ordered();
        $this->coreService->shouldReceive('prepTypes')->once()->with($exchange)
            ->andReturn([['type_id' => 'divine']])->ordered();
        $this->coreService->shouldReceive('create')->once()
            ->with(['types' => [['type_id' => 'divine']]], self::LEAGUE_ID)->andReturn($core)->ordered();
        $this->currencyTypeService->shouldReceive('upsert')->once()->andReturnNull()->ordered();
        $this->currencyTypeService->shouldReceive('getAllByLeague')->once()->with(self::LEAGUE_ID)
            ->andReturn(collect([$this->type(1, 'divine'), $this->type(2, 'chaos')]))->ordered();
        $this->priceRepository->shouldReceive('insert')->once()->andReturnNull()->ordered();
        $this->currencyTypeService->shouldReceive('getAllByLeagueWithPriceByCore')->once()
            ->with(self::LEAGUE_ID, self::CORE_ID)->andReturn($priced)->ordered();

        return compact('exchange', 'league', 'core', 'priced');
    }

    public function test_import_returns_league_core_and_priced_currencies(): void
    {
        $arranged = $this->arrangeSuccessfulImport();

        $result = $this->service->importLeagueLatestPrices(self::LEAGUE_ID);

        $this->assertInstanceOf(LeaguePricesDto::class, $result);
        $this->assertSame($arranged['league'], $result->poeLeague);
        $this->assertSame($arranged['core'], $result->core);
        $this->assertSame($arranged['priced']->all(), $result->currencies);
    }

    public function test_import_runs_the_pipeline_in_order(): void
    {
        // every expectation in arrangeSuccessfulImport() is ->ordered(): a reordered
        // pipeline (e.g. inserting prices before the core exists) fails here
        $this->arrangeSuccessfulImport();

        $this->service->importLeagueLatestPrices(self::LEAGUE_ID);

        $this->addToAssertionCount(1);
    }

    public function test_import_upserts_currency_types_from_ninja_items(): void
    {
        $this->leagueService->shouldReceive('find')->andReturn($this->league());
        $this->ninjaService->shouldReceive('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            [$this->line('divine', 1.0), $this->line('chaos', 0.25)],
        ));
        $this->coreService->shouldReceive('prepTypes')->andReturn([]);
        $this->coreService->shouldReceive('create')->andReturn($this->core());
        $this->currencyTypeService->shouldReceive('getAllByLeague')->andReturn(collect());
        $this->priceRepository->shouldReceive('insert');
        $this->currencyTypeService->shouldReceive('getAllByLeagueWithPriceByCore')->andReturn(collect());

        $this->currencyTypeService->shouldReceive('upsert')->once()->with([
            ['currency_type_name' => 'Divine Orb', 'currency_trade_id' => 'divine', 'currency_ninja_details_id' => 'divine-orb'],
            ['currency_type_name' => 'Chaos Orb', 'currency_trade_id' => 'chaos', 'currency_ninja_details_id' => 'chaos-orb'],
        ], self::LEAGUE_ID);

        $this->service->importLeagueLatestPrices(self::LEAGUE_ID);
    }

    public function test_import_inserts_one_price_per_stored_type_matched_to_ninja_line_by_trade_id(): void
    {
        $this->leagueService->shouldReceive('find')->andReturn($this->league());
        $this->ninjaService->shouldReceive('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            // lines deliberately in the opposite order to the stored types
            [$this->line('chaos', 0.25), $this->line('divine', 1.0)],
        ));
        $this->coreService->shouldReceive('prepTypes')->andReturn([]);
        $this->coreService->shouldReceive('create')->andReturn($this->core());
        $this->currencyTypeService->shouldReceive('upsert');
        $this->currencyTypeService->shouldReceive('getAllByLeague')->with(self::LEAGUE_ID)
            ->andReturn(collect([$this->type(5, 'divine'), $this->type(6, 'chaos')]));
        $this->currencyTypeService->shouldReceive('getAllByLeagueWithPriceByCore')->andReturn(collect());

        $this->priceRepository->shouldReceive('insert')->once()->with([
            ['rate' => 1.0, 'price_check_core_id' => self::CORE_ID, 'currency_type_id' => 5],
            ['rate' => 0.25, 'price_check_core_id' => self::CORE_ID, 'currency_type_id' => 6],
        ]);

        $this->service->importLeagueLatestPrices(self::LEAGUE_ID);
    }

    public function test_import_requests_prices_for_the_league_version_and_name(): void
    {
        $this->leagueService->shouldReceive('find')->once()->with(3)
            ->andReturn($this->league(3, PoeVersionEnum::POE_1));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()
            ->with(PoeVersionEnum::POE_1, 'Test League')
            ->andReturn($this->exchange([], []));
        $this->coreService->shouldReceive('prepTypes')->andReturn([]);
        $this->coreService->shouldReceive('create')->with(['types' => []], 3)->andReturn($this->core());
        $this->currencyTypeService->shouldReceive('upsert');
        $this->currencyTypeService->shouldReceive('getAllByLeague')->andReturn(collect());
        $this->priceRepository->shouldReceive('insert')->with([]);
        $this->currencyTypeService->shouldReceive('getAllByLeagueWithPriceByCore')->with(3, self::CORE_ID)->andReturn(collect());

        $result = $this->service->importLeagueLatestPrices(3);

        $this->assertSame([], $result->currencies);
    }

    public function test_successful_import_leaves_the_transaction_level_unchanged(): void
    {
        $this->arrangeSuccessfulImport();
        $levelBefore = DB::transactionLevel();

        $this->service->importLeagueLatestPrices(self::LEAGUE_ID);

        $this->assertSame($levelBefore, DB::transactionLevel());
    }

    public function test_failure_is_rethrown_and_later_steps_do_not_run(): void
    {
        $boom = new RuntimeException('poe.ninja is down');

        $this->leagueService->shouldReceive('find')->andReturn($this->league());
        $this->ninjaService->shouldReceive('getCurrencyPrices')->andThrow($boom);
        $this->coreService->shouldNotReceive('create');
        $this->currencyTypeService->shouldNotReceive('upsert');
        $this->priceRepository->shouldNotReceive('insert');

        try {
            $this->service->importLeagueLatestPrices(self::LEAGUE_ID);
            $this->fail('Expected the poe.ninja failure to propagate');
        } catch (RuntimeException $caught) {
            $this->assertSame($boom, $caught);
        }
    }

    public function test_failure_restores_the_transaction_level(): void
    {
        $this->leagueService->shouldReceive('find')->andThrow(new RuntimeException('no such league'));
        $levelBefore = DB::transactionLevel();

        try {
            $this->service->importLeagueLatestPrices(self::LEAGUE_ID);
        } catch (RuntimeException) {
        }

        $this->assertSame($levelBefore, DB::transactionLevel());
    }

    public function test_failure_after_the_core_was_created_rolls_the_core_back(): void
    {
        $league = PoeLeagueModel::create([
            'league_name' => 'Rollback Test League',
            'poe_version' => PoeVersionEnum::POE_2->value,
        ]);

        $this->leagueService->shouldReceive('find')->andReturn($this->league($league->id));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb')],
            [$this->line('divine', 1.0)],
        ));
        $this->coreService->shouldReceive('prepTypes')->andReturn([]);

        // the core really is written, as the real PriceCheckCoreService would do
        $this->coreService->shouldReceive('create')->andReturnUsing(function () use ($league) {
            $core = PriceCheckCoreModel::create(['poe_league_id' => $league->id]);

            return $this->core($core->id);
        });
        $this->currencyTypeService->shouldReceive('upsert');
        $this->currencyTypeService->shouldReceive('getAllByLeague')->andReturn(collect([$this->type(1, 'divine')]));
        $this->priceRepository->shouldReceive('insert')->andThrow(new RuntimeException('insert failed'));

        try {
            $this->service->importLeagueLatestPrices($league->id);
            $this->fail('Expected the insert failure to propagate');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseMissing('price_check_cores', ['poe_league_id' => $league->id]);
    }

    public function test_currency_type_without_a_ninja_line_aborts_the_import_before_any_price_is_stored(): void
    {
        $this->leagueService->shouldReceive('find')->andReturn($this->league());
        $this->ninjaService->shouldReceive('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb')],
            [$this->line('divine', 1.0)],
        ));
        $this->coreService->shouldReceive('prepTypes')->andReturn([]);
        $this->coreService->shouldReceive('create')->andReturn($this->core());
        $this->currencyTypeService->shouldReceive('upsert');
        // "mirror" was stored by an earlier import but poe.ninja no longer prices it
        $this->currencyTypeService->shouldReceive('getAllByLeague')
            ->andReturn(collect([$this->type(1, 'divine'), $this->type(2, 'mirror')]));
        $this->priceRepository->shouldNotReceive('insert');

        $levelBefore = DB::transactionLevel();

        try {
            $this->service->importLeagueLatestPrices(self::LEAGUE_ID);
            $this->fail('Expected the import to abort');
        } catch (\Throwable) {
        }

        $this->assertSame($levelBefore, DB::transactionLevel());
    }
}
