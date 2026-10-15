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
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\ServiceObjects\LeaguePricesDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore as PriceCheckCoreModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerServiceContract;
use App\Services\PriceCheckServices\PriceCheckService\PriceCheckService;
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

    private MockInterface|LeagueCheckPickerServiceContract $pickerService;

    private MockInterface|CurrencyTypeServiceContract $currencyTypeService;

    private MockInterface|PriceCheckCoreServiceContract $coreService;

    private MockInterface|PoeNinjaServiceContract $ninjaService;

    private MockInterface|CurrencyPriceRepositoryContract $priceRepository;

    private PriceCheckService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->pickerService = Mockery::mock(LeagueCheckPickerServiceContract::class);
        $this->currencyTypeService = Mockery::mock(CurrencyTypeServiceContract::class);
        $this->coreService = Mockery::mock(PriceCheckCoreServiceContract::class);
        $this->ninjaService = Mockery::mock(PoeNinjaServiceContract::class);
        $this->priceRepository = Mockery::mock(CurrencyPriceRepositoryContract::class);

        $this->service = new PriceCheckService(
            $this->currencyTypeService,
            $this->coreService,
            $this->ninjaService,
            $this->priceRepository,
            $this->pickerService,
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
    private function arrangeSuccessfulImport(?PoeLeagueDto $league = null): array
    {
        $league ??= $this->league();
        $core = $this->core();
        $exchange = $this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            [$this->line('divine', 1.0), $this->line('chaos', 0.25)],
        );
        $priced = collect([$this->typeWithPrice(1, 'divine', 1.0), $this->typeWithPrice(2, 'chaos', 0.25)]);

        $this->ninjaService->expects('getCurrencyPrices')
            ->with(PoeVersionEnum::POE_2, 'Test League')->andReturn($exchange)->ordered();
        $this->coreService->expects('prepTypes')->with($exchange)
            ->andReturn([['type_id' => 'divine']])->ordered();
        $this->coreService->expects('create')
            ->with(['types' => [['type_id' => 'divine']]], self::LEAGUE_ID)->andReturn($core)->ordered();
        $this->currencyTypeService->expects('upsert')->andReturnNull()->ordered();
        $this->currencyTypeService->expects('getAllByLeague')->with(self::LEAGUE_ID)
            ->andReturn(collect([$this->type(1, 'divine'), $this->type(2, 'chaos')]))->ordered();
        $this->priceRepository->expects('insert')->andReturnNull()->ordered();
        $this->currencyTypeService->expects('getAllByLeagueWithPriceByCore')
            ->with(self::LEAGUE_ID, self::CORE_ID)->andReturn($priced)->ordered();

        return compact('exchange', 'league', 'core', 'priced');
    }

    public function test_import_returns_league_core_and_priced_currencies(): void
    {
        $arranged = $this->arrangeSuccessfulImport();

        $result = $this->service->importLeagueLatestPrices($arranged['league']);

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

        $this->service->importLeagueLatestPrices($this->league());

        $this->addToAssertionCount(1);
    }

    public function test_import_upserts_currency_types_from_ninja_items(): void
    {
        $this->ninjaService->allows('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            [$this->line('divine', 1.0), $this->line('chaos', 0.25)],
        ));
        $this->coreService->allows('prepTypes')->andReturn([]);
        $this->coreService->allows('create')->andReturn($this->core());
        $this->currencyTypeService->allows('getAllByLeague')->andReturn(collect());
        $this->priceRepository->allows('insert');
        $this->currencyTypeService->allows('getAllByLeagueWithPriceByCore')->andReturn(collect());

        $this->currencyTypeService->expects('upsert')->with([
            ['currency_type_name' => 'Divine Orb', 'currency_trade_id' => 'divine', 'currency_ninja_details_id' => 'divine-orb'],
            ['currency_type_name' => 'Chaos Orb', 'currency_trade_id' => 'chaos', 'currency_ninja_details_id' => 'chaos-orb'],
        ], self::LEAGUE_ID);

        $this->service->importLeagueLatestPrices($this->league());
    }

    public function test_import_inserts_one_price_per_stored_type_matched_to_ninja_line_by_trade_id(): void
    {
        $this->ninjaService->allows('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb'), $this->item('chaos', 'Chaos Orb', 'chaos-orb')],
            // lines deliberately in the opposite order to the stored types
            [$this->line('chaos', 0.25), $this->line('divine', 1.0)],
        ));
        $this->coreService->allows('prepTypes')->andReturn([]);
        $this->coreService->allows('create')->andReturn($this->core());
        $this->currencyTypeService->allows('upsert');
        $this->currencyTypeService->allows('getAllByLeague')->with(self::LEAGUE_ID)
            ->andReturn(collect([$this->type(5, 'divine'), $this->type(6, 'chaos')]));
        $this->currencyTypeService->allows('getAllByLeagueWithPriceByCore')->andReturn(collect());

        $this->priceRepository->expects('insert')->with([
            ['rate' => 1.0, 'price_check_core_id' => self::CORE_ID, 'currency_type_id' => 5],
            ['rate' => 0.25, 'price_check_core_id' => self::CORE_ID, 'currency_type_id' => 6],
        ]);

        $this->service->importLeagueLatestPrices($this->league());
    }

    public function test_import_requests_prices_for_the_league_version_and_name(): void
    {
        $this->ninjaService->expects('getCurrencyPrices')
            ->with(PoeVersionEnum::POE_1, 'Test League')
            ->andReturn($this->exchange([], []));
        $this->coreService->allows('prepTypes')->andReturn([]);
        $this->coreService->allows('create')->with(['types' => []], 3)->andReturn($this->core());
        $this->currencyTypeService->allows('upsert');
        $this->currencyTypeService->allows('getAllByLeague')->andReturn(collect());
        $this->priceRepository->allows('insert')->with([]);
        $this->currencyTypeService->allows('getAllByLeagueWithPriceByCore')->with(3, self::CORE_ID)->andReturn(collect());

        $result = $this->service->importLeagueLatestPrices($this->league(3, PoeVersionEnum::POE_1));

        $this->assertSame([], $result->currencies);
    }

    public function test_successful_import_leaves_the_transaction_level_unchanged(): void
    {
        $league = $this->league();
        $this->arrangeSuccessfulImport($league);
        $levelBefore = DB::transactionLevel();

        $this->service->importLeagueLatestPrices($league);

        $this->assertSame($levelBefore, DB::transactionLevel());
    }

    public function test_failure_is_rethrown_and_later_steps_do_not_run(): void
    {
        $boom = new RuntimeException('poe.ninja is down');

        $this->ninjaService->allows('getCurrencyPrices')->andThrow($boom);
        $this->coreService->expects('create')->never();
        $this->currencyTypeService->expects('upsert')->never();
        $this->priceRepository->expects('insert')->never();

        try {
            $this->service->importLeagueLatestPrices($this->league());
            $this->fail('Expected the poe.ninja failure to propagate');
        } catch (RuntimeException $caught) {
            $this->assertSame($boom, $caught);
        }
    }

    public function test_failure_restores_the_transaction_level(): void
    {
        $this->ninjaService->allows('getCurrencyPrices')->andThrow(new RuntimeException('poe.ninja is down'));
        $levelBefore = DB::transactionLevel();

        try {
            $this->service->importLeagueLatestPrices($this->league());
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

        $this->ninjaService->allows('getCurrencyPrices')->andReturn($this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb')],
            [$this->line('divine', 1.0)],
        ));
        $this->coreService->allows('prepTypes')->andReturn([]);

        // the core really is written, as the real PriceCheckCoreService would do
        $this->coreService->allows('create')->andReturnUsing(function () use ($league) {
            $core = PriceCheckCoreModel::create(['poe_league_id' => $league->id]);

            return $this->core($core->id);
        });
        $this->currencyTypeService->allows('upsert');
        $this->currencyTypeService->allows('getAllByLeague')->andReturn(collect([$this->type(1, 'divine')]));
        $this->priceRepository->allows('insert')->andThrow(new RuntimeException('insert failed'));

        try {
            $this->service->importLeagueLatestPrices($this->league($league->id));
            $this->fail('Expected the insert failure to propagate');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseMissing('price_check_cores', ['poe_league_id' => $league->id]);
    }

    /**
     * Stubs a successful import for one league: poe.ninja prices it, the core is
     * created with the league's own id and the priced currencies come back.
     */
    private function stubLeagueImport(PoeLeagueDto $league, int $coreId): void
    {
        $exchange = $this->exchange(
            [$this->item('divine', 'Divine Orb', 'divine-orb')],
            [$this->line('divine', 1.0)],
        );

        $this->ninjaService->expects('getCurrencyPrices')
            ->with(PoeVersionEnum::from($league->poe_version), $league->league_name)->andReturn($exchange);
        $this->coreService->expects('prepTypes')->with($exchange)->andReturn([]);
        $this->coreService->expects('create')->with(['types' => []], $league->id)->andReturn($this->core($coreId));
        $this->currencyTypeService->expects('upsert')->with(Mockery::any(), $league->id);
        $this->currencyTypeService->expects('getAllByLeague')->with($league->id)
            ->andReturn(collect([$this->type($league->id, 'divine')]));
        $this->priceRepository->expects('insert')->with([
            ['rate' => 1.0, 'price_check_core_id' => $coreId, 'currency_type_id' => $league->id],
        ]);
        $this->currencyTypeService->expects('getAllByLeagueWithPriceByCore')->with($league->id, $coreId)
            ->andReturn(collect([$this->typeWithPrice($league->id, 'divine', 1.0)]));
    }

    public function test_import_picked_leagues_imports_every_picked_league(): void
    {
        $poe1 = $this->league(1, PoeVersionEnum::POE_1, 'Settlers');
        $poe2 = $this->league(2, PoeVersionEnum::POE_2, 'Dawn');

        $this->pickerService->expects('pickLeaguesForCheck')->andReturn(collect([$poe1, $poe2]));
        $this->stubLeagueImport($poe1, 11);
        $this->stubLeagueImport($poe2, 22);

        $result = $this->service->importPickedLeaguesPrices();

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(LeaguePricesDto::class, $result);
        $this->assertSame($poe1, $result[0]->poeLeague);
        $this->assertSame(11, $result[0]->core->id);
        $this->assertSame($poe2, $result[1]->poeLeague);
        $this->assertSame(22, $result[1]->core->id);
    }

    public function test_import_picked_leagues_returns_empty_collection_when_nothing_is_picked(): void
    {
        $this->pickerService->expects('pickLeaguesForCheck')->andReturn(collect());
        $this->ninjaService->expects('getCurrencyPrices')->never();

        $result = $this->service->importPickedLeaguesPrices();

        $this->assertCount(0, $result);
    }

    public function test_import_picked_leagues_skips_a_failing_league_and_imports_the_rest(): void
    {
        $broken = $this->league(1, PoeVersionEnum::POE_1, 'Broken');
        $healthy = $this->league(2, PoeVersionEnum::POE_2, 'Healthy');

        $this->pickerService->expects('pickLeaguesForCheck')->andReturn(collect([$broken, $healthy]));
        $this->ninjaService->expects('getCurrencyPrices')
            ->with(PoeVersionEnum::POE_1, 'Broken')->andThrow(new RuntimeException('poe.ninja is down'));
        $this->stubLeagueImport($healthy, 22);

        $result = $this->service->importPickedLeaguesPrices();

        $this->assertCount(1, $result);
        $this->assertSame($healthy, $result->first()->poeLeague);
    }

    public function test_import_picked_leagues_returns_empty_collection_when_every_league_fails(): void
    {
        $first = $this->league(1, PoeVersionEnum::POE_1, 'First');
        $second = $this->league(2, PoeVersionEnum::POE_2, 'Second');

        $this->pickerService->expects('pickLeaguesForCheck')->andReturn(collect([$first, $second]));
        $this->ninjaService->expects('getCurrencyPrices')->twice()->andThrow(new RuntimeException('poe.ninja is down'));

        $this->assertCount(0, $this->service->importPickedLeaguesPrices());
    }

    public function test_import_picked_leagues_does_not_throw_and_keeps_the_transaction_level(): void
    {
        $league = $this->league(1, PoeVersionEnum::POE_1, 'Broken');

        $this->pickerService->expects('pickLeaguesForCheck')->andReturn(collect([$league]));
        $this->ninjaService->expects('getCurrencyPrices')->andThrow(new RuntimeException('poe.ninja is down'));
        $levelBefore = DB::transactionLevel();

        $this->service->importPickedLeaguesPrices();

        $this->assertSame($levelBefore, DB::transactionLevel());
    }
}
