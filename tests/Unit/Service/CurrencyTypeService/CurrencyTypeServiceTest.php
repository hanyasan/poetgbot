<?php

namespace Tests\Unit\Service\CurrencyTypeService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeCore;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\CurrencyType;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
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

    private function league(int $id, string $name, PoeVersionEnum $version): PoeLeague
    {
        return new PoeLeague($id, Carbon::now(), Carbon::now(), $name, $version->value, true);
    }

    private function typeWithLeague(int $id, PoeLeague $league, string $detailId): CurrencyTypeWithLeague
    {
        return new CurrencyTypeWithLeague($id, 'Type ' . $id, Carbon::now(), Carbon::now(), $league, 'trade-' . $id, $detailId);
    }

    private function type(int $id): CurrencyType
    {
        return new CurrencyType($id, 'Type ' . $id, Carbon::now(), Carbon::now(), 'trade-' . $id, 'detail-' . $id);
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

    public function test_find_by_detail_caches_per_detail_id(): void
    {
        $poe1 = $this->typeWithLeague(1, $this->league(1, 'Poe1 League', PoeVersionEnum::POE_1), 'divine-orb');
        $poe2 = $this->typeWithLeague(2, $this->league(2, 'Poe2 League', PoeVersionEnum::POE_2), 'divine-orb-2');

        $this->repository->shouldReceive('findByDetailId')->once()->with('divine-orb')->andReturn($poe1);
        $this->repository->shouldReceive('findByDetailId')->once()->with('divine-orb-2')->andReturn($poe2);

        $this->assertSame($poe1, $this->service->findByDetail('divine-orb'));
        $this->assertSame($poe2, $this->service->findByDetail('divine-orb-2'));
        $this->assertSame($poe1, $this->service->findByDetail('divine-orb'));
        $this->assertSame($poe2, $this->service->findByDetail('divine-orb-2'));
    }

    public function test_get_all_caches_result(): void
    {
        $all = collect([
            $this->typeWithLeague(1, $this->league(1, 'Poe1 League', PoeVersionEnum::POE_1), 'a'),
            $this->typeWithLeague(2, $this->league(2, 'Poe2 League', PoeVersionEnum::POE_2), 'b'),
        ]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($all);

        $this->assertSame($all, $this->service->getAll());
        $this->assertSame($all, $this->service->getAll());
    }

    public function test_get_all_by_league_caches_per_league(): void
    {
        $poe1 = collect([$this->type(1)]);
        $poe2 = collect([$this->type(2)]);

        $this->repository->shouldReceive('getAllByLeague')->once()->with(10)->andReturn($poe1);
        $this->repository->shouldReceive('getAllByLeague')->once()->with(20)->andReturn($poe2);

        $this->assertSame($poe1, $this->service->getAllByLeague(10));
        $this->assertSame($poe2, $this->service->getAllByLeague(20));
        $this->assertSame($poe1, $this->service->getAllByLeague(10));
        $this->assertSame($poe2, $this->service->getAllByLeague(20));
    }

    public function test_get_all_by_league_does_not_mix_up_league_ids(): void
    {
        // ids 1 and 11 must not collide in the cache key ("all_by_league1" + "1" vs "all_by_league" + "11")
        $this->repository->shouldReceive('getAllByLeague')->once()->with(1)->andReturn(collect([$this->type(1)]));
        $this->repository->shouldReceive('getAllByLeague')->once()->with(11)->andReturn(collect([$this->type(11)]));

        $this->assertSame(1, $this->service->getAllByLeague(1)->first()->id);
        $this->assertSame(11, $this->service->getAllByLeague(11)->first()->id);
    }

    public function test_delete_flushes_cache_and_delegates(): void
    {
        $all = collect([$this->type(1)]);

        $this->repository->shouldReceive('getAllByLeague')->twice()->with(10)->andReturn($all);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->service->getAllByLeague(10);

        $this->assertTrue($this->service->delete(1));

        // cache was flushed, so the repository is hit again
        $this->service->getAllByLeague(10);
    }

    /**
     * @dataProvider versionProvider
     */
    public function test_upsert_by_ninja_requests_prices_for_league_version(PoeVersionEnum $version, string $leagueName): void
    {
        $league = $this->league(5, $leagueName, $version);

        $this->leagueService->shouldReceive('find')->once()->with(5)->andReturn($league);

        $this->ninjaService
            ->shouldReceive('getCurrencyPrices')
            ->once()
            ->with($version, $leagueName)
            ->andReturn($this->ninjaResponse([
                $this->ninjaItem('divine', 'Divine Orb', 'divine-orb'),
                $this->ninjaItem('chaos', 'Chaos Orb', 'chaos-orb'),
            ]));

        $this->repository
            ->shouldReceive('upsert')
            ->once()
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
            'poe1 league' => [PoeVersionEnum::POE_1, 'Settlers'],
            'poe2 league' => [PoeVersionEnum::POE_2, 'Dawn of the Hunt'],
        ];
    }

    public function test_upsert_by_ninja_uses_the_requested_league_for_poe1_and_poe2(): void
    {
        $poe1 = $this->league(1, 'Settlers', PoeVersionEnum::POE_1);
        $poe2 = $this->league(2, 'Dawn of the Hunt', PoeVersionEnum::POE_2);

        $this->leagueService->shouldReceive('find')->with(1)->andReturn($poe1);
        $this->leagueService->shouldReceive('find')->with(2)->andReturn($poe2);

        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()->with(PoeVersionEnum::POE_1, 'Settlers')
            ->andReturn($this->ninjaResponse([$this->ninjaItem('a', 'A', 'a-details')]));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()->with(PoeVersionEnum::POE_2, 'Dawn of the Hunt')
            ->andReturn($this->ninjaResponse([$this->ninjaItem('b', 'B', 'b-details')]));

        $leagueIds = [];
        $this->repository->shouldReceive('upsert')->twice()->withArgs(function (array $data) use (&$leagueIds): bool {
            $leagueIds[] = array_column($data, 'poe_league_id');

            return true;
        });

        $this->service->upsertByNinja(1);
        $this->service->upsertByNinja(2);

        $this->assertSame([[1], [2]], $leagueIds);
    }

    public function test_upsert_by_ninja_with_no_items_upserts_empty_list(): void
    {
        $this->leagueService->shouldReceive('find')->once()->with(5)
            ->andReturn($this->league(5, 'Settlers', PoeVersionEnum::POE_1));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()->andReturn($this->ninjaResponse([]));
        $this->repository->shouldReceive('upsert')->once()->with([]);

        $this->service->upsertByNinja(5);
    }

    public function test_upsert_by_ninja_flushes_cached_types_of_that_league(): void
    {
        $this->leagueService->shouldReceive('find')->once()->with(5)
            ->andReturn($this->league(5, 'Settlers', PoeVersionEnum::POE_1));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()->andReturn($this->ninjaResponse([]));
        $this->repository->shouldReceive('upsert')->once();
        $this->repository->shouldReceive('getAll')->twice()->andReturn(collect());
        $this->repository->shouldReceive('getAllByLeague')->twice()->with(5)->andReturn(collect());

        $this->service->getAll();
        $this->service->getAllByLeague(5);

        $this->service->upsertByNinja(5);

        $this->service->getAll();
        $this->service->getAllByLeague(5);
    }

    public function test_upsert_by_ninja_flushes_cached_detail_lookups(): void
    {
        $this->leagueService->shouldReceive('find')->once()->with(5)
            ->andReturn($this->league(5, 'Settlers', PoeVersionEnum::POE_1));
        $this->ninjaService->shouldReceive('getCurrencyPrices')->once()->andReturn($this->ninjaResponse([]));
        $this->repository->shouldReceive('upsert')->once();

        // the by-detail cache shares the base tag, so it is flushed as well
        $type = $this->typeWithLeague(1, $this->league(5, 'Settlers', PoeVersionEnum::POE_1), 'divine-orb');
        $this->repository->shouldReceive('findByDetailId')->twice()->with('divine-orb')->andReturn($type);

        $this->service->findByDetail('divine-orb');
        $this->service->upsertByNinja(5);

        $this->service->findByDetail('divine-orb');
    }
}
