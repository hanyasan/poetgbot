<?php

namespace Tests\Unit\Service\PoeLeagueService;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\ParsingObjects\PoeNinjaLeagueResponse;
use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\DataServices\PoeLeagueService\PoeLeagueService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PoeLeagueServiceTest extends TestCase
{
    private MockInterface|PoeLeagueRepositoryContract $repository;

    private MockInterface|PoeNinjaServiceContract $ninjaService;

    private PoeLeagueService $service;

    public function setUp(): void
    {
        parent::setUp();

        Cache::tags(['poe_league', 'poe_league_get'])->flush();

        $this->repository = Mockery::mock(PoeLeagueRepositoryContract::class);
        $this->ninjaService = Mockery::mock(PoeNinjaServiceContract::class);

        $this->service = new PoeLeagueService($this->repository, $this->ninjaService);

        Cache::flush();
    }

    private function league(int $id, string $name, PoeVersionEnum $version, bool $active = false): PoeLeagueDto
    {
        return new PoeLeagueDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), $name, $version->value, $active);
    }

    private function ninjaLeague(string $name): PoeNinjaLeague
    {
        return new PoeNinjaLeague($name, strtolower($name), $name, false, true);
    }

    public function test_find_returns_repository_result_and_caches_it(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);

        $this->repository->expects('find')->with(1)->andReturns($league);

        $this->assertEquals($league, $this->service->find(1));
        $this->assertEquals($league, $this->service->find(1));
    }

    public function test_get_all_caches_result(): void
    {
        $leagues = collect([
            $this->league(1, 'One', PoeVersionEnum::POE_1),
            $this->league(2, 'Two', PoeVersionEnum::POE_2),
        ]);

        $this->repository->shouldReceive('getAll')->once()->andReturn($leagues);

        $this->assertEquals($leagues, $this->service->getAll());
        $this->assertEquals($leagues, $this->service->getAll());
    }

    public function test_get_all_active_caches_per_version(): void
    {
        $poe1 = collect([$this->league(1, 'One', PoeVersionEnum::POE_1, true)]);
        $poe2 = collect([$this->league(2, 'Two', PoeVersionEnum::POE_2, true)]);

        $this->repository->expects('getAllActiveByVersion')->with(PoeVersionEnum::POE_1)->andReturns($poe1);
        $this->repository->expects('getAllActiveByVersion')->with(PoeVersionEnum::POE_2)->andReturns($poe2);

        $this->assertEquals($poe1, $this->service->getAllActiveByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2));
        $this->assertEquals($poe1, $this->service->getAllActiveByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2));
    }

    public function test_get_all_active_is_flushed_by_delete(): void
    {
        $leagues = collect([$this->league(1, 'One', PoeVersionEnum::POE_2, true)]);

        $this->repository->expects('getAllActiveByVersion')->twice()->with(PoeVersionEnum::POE_2)->andReturns($leagues);
        $this->repository->expects('delete')->with(1)->andReturns(true);

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);

        $this->service->delete(1);

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);
    }

    public function test_get_all_active_is_flushed_by_upsert_by_ninja(): void
    {
        $this->repository->expects('getAllActiveByVersion')->twice()->with(PoeVersionEnum::POE_2)->andReturns(collect());
        $this->repository->expects('getByLeague')->andReturns(collect());
        $this->repository->expects('upsert');
        $this->ninjaService
            ->expects('getAvailableLeagues')
            ->andReturns(new PoeNinjaLeagueResponse([$this->ninjaLeague('Standard')]));

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);
    }

    public function test_get_by_version_caches_per_version(): void
    {
        $poe1 = collect([$this->league(1, 'One', PoeVersionEnum::POE_1)]);
        $poe2 = collect([$this->league(2, 'Two', PoeVersionEnum::POE_2)]);

        $this->repository->shouldReceive('getByLeague')->once()->with(PoeVersionEnum::POE_1)->andReturn($poe1);
        $this->repository->shouldReceive('getByLeague')->once()->with(PoeVersionEnum::POE_2)->andReturn($poe2);

        $this->assertEquals($poe1, $this->service->getByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getByVersion(PoeVersionEnum::POE_2));
        $this->assertEquals($poe1, $this->service->getByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getByVersion(PoeVersionEnum::POE_2));
    }

    public function test_delete_flushes_cache_and_delegates(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);
        $all = collect([$league]);

        $this->repository->shouldReceive('getAll')->twice()->andReturn($all);
        $this->repository->shouldReceive('delete')->once()->with(1)->andReturn(true);

        $this->service->getAll();

        $this->assertTrue($this->service->delete(1));

        // cache was flushed, so the repository is hit again
        $this->service->getAll();
    }

    public function test_truncate_flushes_cache_and_delegates(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);

        $this->repository->shouldReceive('find')->twice()->with(1)->andReturn($league);
        $this->repository->shouldReceive('truncate')->once();

        $this->service->find(1);

        $this->service->truncate();

        $this->service->find(1);
    }

    public function test_upsert_by_ninja_upserts_ninja_leagues_as_active(): void
    {
        $this->ninjaService
            ->shouldReceive('getAvailableLeagues')
            ->once()
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(new PoeNinjaLeagueResponse([
                $this->ninjaLeague('Fresh League'),
                $this->ninjaLeague('Standard'),
            ]));

        $this->repository
            ->shouldReceive('getByLeague')
            ->once()
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(collect());

        $this->repository
            ->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($params): bool {
                $this->assertEquals([
                    ['league_name' => 'Fresh League', 'poe_version' => 'poe2', 'is_active' => true],
                    ['league_name' => 'Standard', 'poe_version' => 'poe2', 'is_active' => true],
                ], collect($params)->values()->all());

                return true;
            });

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);
    }

    public function test_upsert_by_ninja_deactivates_leagues_missing_from_ninja(): void
    {
        $this->ninjaService
            ->shouldReceive('getAvailableLeagues')
            ->once()
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(new PoeNinjaLeagueResponse([
                $this->ninjaLeague('Standard'),
            ]));

        $this->repository
            ->shouldReceive('getByLeague')
            ->once()
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(collect([
                $this->league(1, 'Standard', PoeVersionEnum::POE_2, true),
                $this->league(2, 'Old League', PoeVersionEnum::POE_2, true),
                $this->league(3, 'Ancient League', PoeVersionEnum::POE_2, false),
            ]));

        $this->repository
            ->shouldReceive('upsert')
            ->once()
            ->withArgs(function ($params): bool {
                $byName = collect($params)->keyBy('league_name');

                $this->assertCount(2, $byName);
                $this->assertTrue($byName['Standard']['is_active']);
                $this->assertFalse($byName['Old League']['is_active']);
                $this->assertSame('poe2', $byName['Old League']['poe_version']);

                return true;
            });

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);
    }

    public function test_upsert_by_ninja_flushes_cached_leagues(): void
    {
        $this->repository->shouldReceive('getAll')->twice()->andReturn(collect());
        $this->repository->shouldReceive('getByLeague')->once()->andReturn(collect());
        $this->repository->shouldReceive('upsert')->once();
        $this->ninjaService
            ->shouldReceive('getAvailableLeagues')
            ->once()
            ->andReturn(new PoeNinjaLeagueResponse([$this->ninjaLeague('Standard')]));

        $this->service->getAll();

        $this->service->upsertByNinja(PoeVersionEnum::POE_1);

        $this->service->getAll();
    }
}
