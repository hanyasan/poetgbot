<?php

namespace Tests\Unit\Service\PriceCheckCoreService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeCore;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeItem;
use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreService;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PoeNinjaService\PoeNinjaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PriceCheckCoreServiceTest extends TestCase
{
    private MockInterface|PriceCheckCoreRepositoryContract $repository;

    private MockInterface|PoeNinjaService $ninjaService;

    private MockInterface|PoeLeagueServiceContract $leagueService;

    private PriceCheckCoreService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(PriceCheckCoreRepositoryContract::class);

        $this->ninjaService = Mockery::mock(PoeNinjaService::class);
        $this->leagueService = Mockery::mock(PoeLeagueServiceContract::class);

        $this->service = new PriceCheckCoreService($this->repository, $this->ninjaService, $this->leagueService);
    }

    private function core(int $id): PriceCheckCoreDto
    {
        return new PriceCheckCoreDto($id, Carbon::now(), Carbon::now(), []);
    }

    public function test_find_returns_repository_result_and_caches_it(): void
    {
        $core = $this->core(1);

        $this->repository->shouldReceive('find')->once()->with(1)->andReturn($core);

        $this->assertSame($core, $this->service->find(1));
        $this->assertSame($core, $this->service->find(1));
    }

    public function test_find_caches_per_id(): void
    {
        $one = $this->core(1);
        $two = $this->core(2);

        $this->repository->shouldReceive('find')->once()->with(1)->andReturn($one);
        $this->repository->shouldReceive('find')->once()->with(2)->andReturn($two);

        $this->assertSame($one, $this->service->find(1));
        $this->assertSame($two, $this->service->find(2));
    }

    public function test_find_propagates_not_found_and_does_not_cache(): void
    {
        $this->repository->shouldReceive('find')->twice()->with(99)->andThrow(new ModelNotFoundException());

        for ($i = 0; $i < 2; $i++) {
            try {
                $this->service->find(99);
                $this->fail('Expected ModelNotFoundException');
            } catch (ModelNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_get_all_caches_result(): void
    {
        $cores = collect([$this->core(1), $this->core(2)]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($cores);

        $this->assertSame($cores, $this->service->getAll());
        $this->assertSame($cores, $this->service->getAll());
    }

    public function test_get_by_league_caches_per_league(): void
    {
        $first = collect([$this->core(1)]);
        $second = collect([$this->core(2)]);

        $this->repository->shouldReceive('getByLeague')->once()->with(10)->andReturn($first);
        $this->repository->shouldReceive('getByLeague')->once()->with(20)->andReturn($second);

        $this->assertSame($first, $this->service->getByLeague(10));
        $this->assertSame($first, $this->service->getByLeague(10));
        $this->assertSame($second, $this->service->getByLeague(20));
    }

    public function test_get_latest_by_league_caches_per_league(): void
    {
        $first = $this->core(1);
        $second = $this->core(2);

        $this->repository->shouldReceive('getLatestByLeague')->once()->with(10)->andReturn($first);
        $this->repository->shouldReceive('getLatestByLeague')->once()->with(20)->andReturn($second);

        $this->assertSame($first, $this->service->getLatestByLeague(10));
        $this->assertSame($first, $this->service->getLatestByLeague(10));
        $this->assertSame($second, $this->service->getLatestByLeague(20));
    }

    public function test_get_from_date_by_league_caches_per_date_and_league(): void
    {
        $from = Carbon::parse('2024-01-01');
        $later = Carbon::parse('2024-02-01');
        $a = collect([$this->core(1)]);
        $b = collect([$this->core(2)]);

        $this->repository->shouldReceive('getFromDateByLeague')->once()->withArgs(
            fn ($date, $league) => $date->equalTo($from) && $league === 10
        )->andReturn($a);
        $this->repository->shouldReceive('getFromDateByLeague')->once()->withArgs(
            fn ($date, $league) => $date->equalTo($later) && $league === 10
        )->andReturn($b);

        $this->assertSame($a, $this->service->getFromDateByLeague($from, 10));
        $this->assertSame($a, $this->service->getFromDateByLeague($from, 10));
        $this->assertSame($b, $this->service->getFromDateByLeague($later, 10));
    }

    public function test_create_passes_types_to_repository_and_returns_dto(): void
    {
        $types = [
            ['is_primary' => true, 'rate' => 3.0, 'type_id' => 'divine', 'name' => 'Divine'],
            ['is_primary' => false, 'rate' => 1.0, 'type_id' => 'chaos', 'name' => 'Chaos'],
        ];
        $core = $this->core(5);

        $this->repository->shouldReceive('createCore')->once()->with(10, $types)->andReturn($core);

        $this->assertSame($core, $this->service->create(['types' => $types], 10));
    }

    public function test_create_invalidates_league_caches(): void
    {
        $stale = collect([$this->core(1)]);
        $fresh = collect([$this->core(1), $this->core(2)]);
        $other = collect([$this->core(3)]);

        $this->repository->shouldReceive('getByLeague')->once()->with(10)->andReturn($stale);
        $this->repository->shouldReceive('getByLeague')->once()->with(10)->andReturn($fresh);
        $this->repository->shouldReceive('createCore')->once()->andReturn($this->core(2));

        $this->assertSame($stale, $this->service->getByLeague(10));

        $this->service->create(['types' => []], 10);

        $this->assertSame($fresh, $this->service->getByLeague(10));
    }

    public function test_create_invalidates_get_all_cache(): void
    {
        $stale = collect([$this->core(1)]);
        $fresh = collect([$this->core(1), $this->core(2)]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($stale);
        $this->repository->shouldReceive('getAll')->once()->andReturn($fresh);
        $this->repository->shouldReceive('createCore')->once()->andReturn($this->core(2));

        $this->assertSame($stale, $this->service->getAll());

        $this->service->create(['types' => []], 10);

        $this->assertSame($fresh, $this->service->getAll());
    }

    public function test_create_new_by_ninja_maps_ninja_items_to_core_types(): void
    {
        $league = new PoeLeagueDto(10, Carbon::now(), Carbon::now(), 'Test League', PoeVersionEnum::POE_2->value, true);
        $response = new PoeNinjaExchangeResponse(
            new PoeNinjaExchangeCore(
                [
                    new PoeNinjaExchangeItem('divine', 'Divine Orb', 'img.png', 'Currency', 'divine-orb'),
                    new PoeNinjaExchangeItem('chaos', 'Chaos Orb', 'img.png', 'Currency', 'chaos-orb'),
                ],
                ['divine' => 1.0, 'chaos' => 0.25],
                'divine',
                'chaos',
            ),
            [],
            [],
        );
        $core = $this->core(7);

        $this->leagueService->shouldReceive('find')->once()->with(10)->andReturn($league);
        $this->ninjaService
            ->shouldReceive('getCurrencyPrices')
            ->once()
            ->with(PoeVersionEnum::POE_2, 'Test League')
            ->andReturn($response);
        $this->repository->shouldReceive('createCore')->once()->with(10, [
            ['is_primary' => true, 'rate' => 1, 'type_id' => 'divine', 'name' => 'Divine Orb'],
            ['is_primary' => false, 'rate' => 0.25, 'type_id' => 'chaos', 'name' => 'Chaos Orb'],
        ])->andReturn($core);

        $this->assertSame($core, $this->service->createNewByNinja(10));
    }
}
