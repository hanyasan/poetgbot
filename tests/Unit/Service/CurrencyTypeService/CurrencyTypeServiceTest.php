<?php

namespace Tests\Unit\Service\CurrencyTypeService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeCore;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

class CurrencyTypeServiceTest extends TestCase
{
    private MockInterface|CurrencyTypeRepositoryContract $repository;

    private MockInterface|PoeNinjaServiceContract $ninjaService;

    private MockInterface|PoeLeagueServiceContract $leagueService;

    private CurrencyTypeService $service;

    public function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->repository = Mockery::mock(CurrencyTypeRepositoryContract::class);
        $this->ninjaService = Mockery::mock(PoeNinjaServiceContract::class);
        $this->leagueService = Mockery::mock(PoeLeagueServiceContract::class);

        $this->service = new CurrencyTypeService($this->repository, $this->ninjaService, $this->leagueService);
    }

    private function league(int $id, string $name, PoeVersionEnum $version): PoeLeagueDto
    {
        return new PoeLeagueDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), $name, $version->value, true);
    }

    private function typeWithLeague(int $id, PoeLeagueDto $league, string $detailId): CurrencyTypeWithLeagueDto
    {
        return new CurrencyTypeWithLeagueDto($id, 'Type ' . $id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), $league, 'trade-' . $id, $detailId);
    }

    private function type(int $id): CurrencyTypeDto
    {
        return new CurrencyTypeDto($id, 'Type ' . $id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), 'trade-' . $id, 'detail-' . $id);
    }

    private function typeWithPrices(int $id, float $rate): CurrencyTypeWithPricesDto
    {
        return new CurrencyTypeWithPricesDto(
            $id,
            'Type ' . $id,
            '2024-01-01T00:00:00.000000Z',
            '2024-01-01T00:00:00.000000Z',
            [new CurrencyPriceDto($id * 100, $rate)],
            'trade-' . $id,
            'detail-' . $id,
        );
    }

    private function ninjaResponse(array $items): PoeNinjaExchangeResponse
    {
        $core = (new ReflectionClass(PoeNinjaExchangeCore::class))->newInstanceWithoutConstructor();

        return new PoeNinjaExchangeResponse($core, [], $items);
    }

    private function ninjaItem(string $id, string $name, string $detailsId): PoeNinjaExchangeItem
    {
        return new PoeNinjaExchangeItem($id, $name, 'image.png', 'Currency', $detailsId);
    }

    public function test_get_all_caches_result(): void
    {
        $all = collect([
            $this->typeWithLeague(1, $this->league(1, 'Poe1 League', PoeVersionEnum::POE_1), 'a'),
            $this->typeWithLeague(2, $this->league(2, 'Poe2 League', PoeVersionEnum::POE_2), 'b'),
        ]);

        $this->repository->expects('getAll')->andReturns($all);

        $this->assertEquals($all, $this->service->getAll());
        $this->assertEquals($all, $this->service->getAll());
    }

    public function test_get_all_by_league_caches_per_league(): void
    {
        $poe1 = collect([$this->type(1)]);
        $poe2 = collect([$this->type(2)]);

        $this->repository->expects('getAllByLeague')->with(10)->andReturns($poe1);
        $this->repository->expects('getAllByLeague')->with(20)->andReturns($poe2);

        $this->assertEquals($poe1, $this->service->getAllByLeague(10));
        $this->assertEquals($poe2, $this->service->getAllByLeague(20));
        $this->assertEquals($poe1, $this->service->getAllByLeague(10));
        $this->assertEquals($poe2, $this->service->getAllByLeague(20));
    }

    public function test_get_all_by_league_does_not_mix_up_league_ids(): void
    {
        // ids 1 and 11 must not collide in the cache key ("all_by_league1" + "1" vs "all_by_league" + "11")
        $this->repository->expects('getAllByLeague')->with(1)->andReturn(collect([$this->type(1)]));
        $this->repository->expects('getAllByLeague')->with(11)->andReturn(collect([$this->type(11)]));

        $this->assertEquals(1, $this->service->getAllByLeague(1)->first()->id);
        $this->assertEquals(11, $this->service->getAllByLeague(11)->first()->id);
    }

    public function test_delete_flushes_cache_and_delegates(): void
    {
        $all = collect([$this->type(1)]);

        $this->repository->expects('getAllByLeague')->twice()->with(10)->andReturn($all);
        $this->repository->expects('delete')->with(1)->andReturn(true);

        $this->service->getAllByLeague(10);

        $this->assertTrue($this->service->delete(1));

        // cache was flushed, so the repository is hit again
        $this->service->getAllByLeague(10);
    }

    #[DataProvider('versionProvider')]
    public function test_upsert_by_ninja_requests_prices_for_league_version(PoeVersionEnum $version, string $leagueName): void
    {
        $league = $this->league(5, $leagueName, $version);

        $this->leagueService->expects('find')->with(5)->andReturn($league);

        $this->ninjaService
            ->expects('getCurrencyPrices')
            ->with($version, $leagueName)
            ->andReturn($this->ninjaResponse([
                $this->ninjaItem('divine', 'Divine Orb', 'divine-orb'),
                $this->ninjaItem('chaos', 'Chaos Orb', 'chaos-orb'),
            ]));

        $this->repository
            ->expects('upsert')
            ->with([
                [
                    'currency_type_name' => 'Divine Orb',
                    'currency_trade_id' => 'divine',
                    'currency_ninja_details_id' => 'divine-orb',
                    'poe_league_id' => 5,
                ],
                [
                    'currency_type_name' => 'Chaos Orb',
                    'currency_trade_id' => 'chaos',
                    'currency_ninja_details_id' => 'chaos-orb',
                    'poe_league_id' => 5,
                ],
            ]);

        $this->service->upsertByNinja(5);
    }

    public static function versionProvider(): array
    {
        return [
            'poe1 league' => [PoeVersionEnum::POE_1, 'Standard'],
            'poe2 league' => [PoeVersionEnum::POE_2, 'Hardcore'],
        ];
    }

    public function test_upsert_by_ninja_uses_the_requested_league_for_poe1_and_poe2(): void
    {
        $poe1 = $this->league(1, 'Standard', PoeVersionEnum::POE_1);
        $poe2 = $this->league(2, 'Hardcore', PoeVersionEnum::POE_2);

        $this->leagueService->allows('find')->with(1)->andReturn($poe1);
        $this->leagueService->allows('find')->with(2)->andReturn($poe2);

        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_1, 'Standard')
            ->andReturn($this->ninjaResponse([$this->ninjaItem('a', 'A', 'a-details')]));
        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_2, 'Hardcore')
            ->andReturn($this->ninjaResponse([$this->ninjaItem('b', 'B', 'b-details')]));

        $leagueIds = [];
        $this->repository->expects('upsert')->twice()->withArgs(function (array $data) use (&$leagueIds): bool {
            $leagueIds[] = array_column($data, 'poe_league_id');

            return true;
        });

        $this->service->upsertByNinja(1);
        $this->service->upsertByNinja(2);

        $this->assertSame([[1], [2]], $leagueIds);
    }

    public function test_upsert_by_ninja_with_no_items_upserts_empty_list(): void
    {
        $this->leagueService->expects('find')->with(5)
            ->andReturn($this->league(5, 'Standard', PoeVersionEnum::POE_1));
        $this->ninjaService->expects('getCurrencyPrices')->andReturn($this->ninjaResponse([]));
        $this->repository->expects('upsert')->with([]);

        $this->service->upsertByNinja(5);
    }

    public function test_upsert_by_ninja_flushes_cached_types_of_that_league(): void
    {
        $this->leagueService->expects('find')->with(5)
            ->andReturn($this->league(5, 'Standard', PoeVersionEnum::POE_1));
        $this->ninjaService->expects('getCurrencyPrices')->andReturn($this->ninjaResponse([]));
        $this->repository->expects('upsert');
        $this->repository->expects('getAll')->twice()->andReturn(collect());
        $this->repository->expects('getAllByLeague')->twice()->with(5)->andReturn(collect());

        $this->service->getAll();
        $this->service->getAllByLeague(5);

        $this->service->upsertByNinja(5);

        $this->service->getAll();
        $this->service->getAllByLeague(5);
    }

    public function test_fresh_all_by_ninja_upserts_every_active_league_of_both_versions(): void
    {
        $poe1Standard = $this->league(1, 'Standard', PoeVersionEnum::POE_1);
        $poe1Hardcore = $this->league(2, 'Hardcore', PoeVersionEnum::POE_1);
        $poe2Standard = $this->league(3, 'Standard', PoeVersionEnum::POE_2);
        $poe2Hardcore = $this->league(4, 'Hardcore', PoeVersionEnum::POE_2);

        $this->leagueService->expects('getAllActive')->andReturns(collect([$poe1Standard, $poe1Hardcore, $poe2Standard, $poe2Hardcore]));

        $this->leagueService->allows('find')->with(1)->andReturns($poe1Standard);
        $this->leagueService->allows('find')->with(2)->andReturns($poe1Hardcore);
        $this->leagueService->allows('find')->with(3)->andReturns($poe2Standard);
        $this->leagueService->allows('find')->with(4)->andReturns($poe2Hardcore);

        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_1, 'Standard')
            ->andReturns($this->ninjaResponse([$this->ninjaItem('a', 'A', 'a-details')]));
        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_1, 'Hardcore')
            ->andReturns($this->ninjaResponse([$this->ninjaItem('b', 'B', 'b-details')]));
        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_2, 'Standard')
            ->andReturns($this->ninjaResponse([$this->ninjaItem('c', 'C', 'c-details')]));
        $this->ninjaService->expects('getCurrencyPrices')->with(PoeVersionEnum::POE_2, 'Hardcore')
            ->andReturns($this->ninjaResponse([$this->ninjaItem('d', 'D', 'd-details')]));

        // poe1 leagues are processed first, then poe2
        $upserted = [];
        $this->repository->expects('upsert')->times(4)->withArgs(function (array $data) use (&$upserted): bool {
            $upserted[] = array_column($data, 'poe_league_id');

            return true;
        });

        $this->service->freshAllByNinja();

        $this->assertSame([[1], [2], [3], [4]], $upserted);
    }

    public function test_fresh_all_by_ninja_without_active_leagues_does_not_touch_ninja_or_repository(): void
    {
        $this->leagueService->allows('getAllActive')->andReturns(collect());

        $this->leagueService->allows('find')->never();
        $this->ninjaService->allows('getCurrencyPrices')->never();
        $this->repository->allows('upsert')->never();

        $this->service->freshAllByNinja();
    }

    public function test_fresh_all_by_ninja_flushes_cached_types(): void
    {
        $this->leagueService->expects('getAllActive')->andReturns(collect());

        $this->repository->expects('getAll')->twice()->andReturns(collect());
        $this->repository->expects('getAllByLeague')->twice()->with(10)->andReturns(collect());

        $this->service->getAll();
        $this->service->getAllByLeague(10);

        $this->service->freshAllByNinja();

        $this->service->getAll();
        $this->service->getAllByLeague(10);
    }

    public function test_upsert_adds_the_league_id_to_every_row(): void
    {
        $this->repository->expects('upsert')->with([
            [
                'currency_type_name' => 'Divine Orb',
                'currency_trade_id' => 'divine',
                'currency_ninja_details_id' => 'divine-orb',
                'poe_league_id' => 7,
            ],
            [
                'currency_type_name' => 'Chaos Orb',
                'currency_trade_id' => 'chaos',
                'currency_ninja_details_id' => 'chaos-orb',
                'poe_league_id' => 7,
            ],
        ]);

        $this->service->upsert([
            ['currency_type_name' => 'Divine Orb', 'currency_trade_id' => 'divine', 'currency_ninja_details_id' => 'divine-orb'],
            ['currency_type_name' => 'Chaos Orb', 'currency_trade_id' => 'chaos', 'currency_ninja_details_id' => 'chaos-orb'],
        ], 7);
    }

    public function test_upsert_drops_keys_that_are_not_type_columns(): void
    {
        $this->repository->expects('upsert')->with([
            [
                'currency_type_name' => 'Divine Orb',
                'currency_trade_id' => 'divine',
                'currency_ninja_details_id' => 'divine-orb',
                'poe_league_id' => 7,
            ],
        ]);

        $this->service->upsert([
            [
                'currency_type_name' => 'Divine Orb',
                'currency_trade_id' => 'divine',
                'currency_ninja_details_id' => 'divine-orb',
                'unexpected' => 'dropped',
            ],
        ], 7);
    }

    public function test_upsert_does_not_need_ninja_or_the_league_service(): void
    {
        // unlike upsertByNinja(), the caller already holds the poe.ninja data (PriceCheckService)
        $this->leagueService->allows('find')->never();
        $this->ninjaService->allows('getCurrencyPrices')->never();
        $this->repository->expects('upsert')->with([]);

        $this->service->upsert([], 7);
    }

    public function test_upsert_flushes_cached_types_of_that_league(): void
    {
        $this->repository->expects('upsert');
        $this->repository->expects('getAll')->twice()->andReturns(collect());
        $this->repository->expects('getAllByLeague')->twice()->with(5)->andReturns(collect());

        $this->service->getAll();
        $this->service->getAllByLeague(5);

        $this->service->upsert([], 5);

        $this->service->getAll();
        $this->service->getAllByLeague(5);
    }

    public function test_get_all_by_league_with_price_by_core_caches_result(): void
    {
        $types = collect([$this->typeWithPrices(1, 10.0)]);

        $this->repository->expects('getAllByLeagueWithPriceByCore')->with(10, 77)->andReturns($types);

        $this->assertEquals($types, $this->service->getAllByLeagueWithPriceByCore(10, 77));
        $this->assertEquals($types, $this->service->getAllByLeagueWithPriceByCore(10, 77));
    }

    public function test_get_all_by_league_with_price_by_core_caches_per_league(): void
    {
        $first = collect([$this->typeWithPrices(1, 10.0)]);
        $second = collect([$this->typeWithPrices(2, 20.0)]);

        $this->repository->expects('getAllByLeagueWithPriceByCore')->with(10, 77)->andReturns($first);
        $this->repository->expects('getAllByLeagueWithPriceByCore')->with(20, 77)->andReturns($second);

        $this->assertSame($first, $this->service->getAllByLeagueWithPriceByCore(10, 77));
        $this->assertSame($second, $this->service->getAllByLeagueWithPriceByCore(20, 77));
    }

    public function test_get_all_by_league_with_price_by_core_must_not_serve_one_core_for_another(): void
    {
        $oldCore = collect([$this->typeWithPrices(1, 10.0)]);
        $newCore = collect([$this->typeWithPrices(1, 12.0)]);

        $this->repository->expects('getAllByLeagueWithPriceByCore')->with(10, 77)->andReturns($oldCore);
        $this->repository->expects('getAllByLeagueWithPriceByCore')->with(10, 78)->andReturns($newCore);

        $this->assertSame($oldCore, $this->service->getAllByLeagueWithPriceByCore(10, 77));
        $this->assertSame($newCore, $this->service->getAllByLeagueWithPriceByCore(10, 78));
    }

    public function test_get_all_by_league_with_price_by_core_is_flushed_by_upsert(): void
    {
        $this->repository->expects('upsert');
        $this->repository->expects('getAllByLeagueWithPriceByCore')->twice()->with(10, 77)->andReturns(collect());

        $this->service->getAllByLeagueWithPriceByCore(10, 77);
        $this->service->upsert([], 10);
        $this->service->getAllByLeagueWithPriceByCore(10, 77);
    }
}
