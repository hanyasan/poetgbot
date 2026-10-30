<?php

namespace Tests\Unit\Service\DataServices\League;

use App\DataTransferObjects\ParsingObjects\PoeNinjaLeague;
use App\DataTransferObjects\ParsingObjects\PoeNinjaLeagueResponse;
use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Repositories\PoeLeague\LeagueRuleRepositoryContract;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use App\Services\DataServices\PoeLeague\PoeLeagueService;
use App\Services\PoeNinja\PoeNinjaServiceContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PoeLeagueServiceTest extends TestCase
{
    private MockInterface|PoeLeagueRepositoryContract $repository;

    private MockInterface|LeagueRuleRepositoryContract $ruleRepository;

    private MockInterface|PoeNinjaServiceContract $ninjaService;

    private PoeLeagueService $service;

    public function setUp(): void
    {
        parent::setUp();

        Cache::tags(['poe_league', 'poe_league_get'])->flush();

        $this->repository = Mockery::mock(PoeLeagueRepositoryContract::class);
        $this->ninjaService = Mockery::mock(PoeNinjaServiceContract::class);
        $this->ruleRepository = Mockery::mock(LeagueRuleRepositoryContract::class);

        $this->service = new PoeLeagueService($this->repository, $this->ninjaService, $this->ruleRepository);

        Cache::flush();
    }

    private function league(int $id, string $name, PoeVersionEnum $version, bool $active = false, int|null $checkRuleId = null): PoeLeagueDto
    {
        $checkRule =  $checkRuleId ? (new LeagueCheckRuleDto($checkRuleId, 60, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString())) : null;

        return new PoeLeagueDto($id, Carbon::now()->toDateTimeString(), Carbon::now()->toDateTimeString(), $name, $version->value, $active, $checkRule);
    }

    private function ninjaLeague(string $name): PoeNinjaLeague
    {
        return new PoeNinjaLeague($name, strtolower($name), $name, false, true);
    }

    public function test_find_returns_repository_result_and_caches_it(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);

        $this->repository->expects('find')->once()->with(1)->andReturns($league);

        $this->assertEquals($league, $this->service->find(1));
        $this->assertEquals($league, $this->service->find(1));
    }

    public function test_find_active_by_name_version_returns_repository_result_and_caches_it(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2, true);

        $this->repository->expects('findActiveByNameVersion')->once()->with('Test League', PoeVersionEnum::POE_2)->andReturns($league);

        $this->assertEquals($league, $this->service->findActiveByNameVersion('Test League', PoeVersionEnum::POE_2));
        $this->assertEquals($league, $this->service->findActiveByNameVersion('Test League', PoeVersionEnum::POE_2));
    }

    public function test_get_all_caches_result(): void
    {
        $leagues = collect([
            $this->league(1, 'One', PoeVersionEnum::POE_1),
            $this->league(2, 'Two', PoeVersionEnum::POE_2),
        ]);

        $this->repository->expects('getAll')->andReturn($leagues);

        $this->assertEquals($leagues, $this->service->getAll());
        $this->assertEquals($leagues, $this->service->getAll());
    }

    public function test_get_all_active_caches_per_version(): void
    {
        $poe1 = collect([$this->league(1, 'One', PoeVersionEnum::POE_1, true, 1)]);
        $poe2 = collect([$this->league(2, 'Two', PoeVersionEnum::POE_2, true, 2)]);

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
        $this->ruleRepository->expects('upsertDefaultForLeagues');
        $this->repository->expects('getAllWithoutRules')->andReturns(collect());

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);

        $this->service->getAllActiveByVersion(PoeVersionEnum::POE_2);
    }

    public function test_get_by_version_caches_per_version(): void
    {
        $poe1 = collect([$this->league(1, 'One', PoeVersionEnum::POE_1)]);
        $poe2 = collect([$this->league(2, 'Two', PoeVersionEnum::POE_2)]);

        $this->repository->expects('getByLeague')->with(PoeVersionEnum::POE_1)->andReturn($poe1);
        $this->repository->expects('getByLeague')->with(PoeVersionEnum::POE_2)->andReturn($poe2);

        $this->assertEquals($poe1, $this->service->getByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getByVersion(PoeVersionEnum::POE_2));
        $this->assertEquals($poe1, $this->service->getByVersion(PoeVersionEnum::POE_1));
        $this->assertEquals($poe2, $this->service->getByVersion(PoeVersionEnum::POE_2));
    }

    public function test_delete_flushes_cache_and_delegates(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);
        $all = collect([$league]);

        $this->repository->expects('getAll')->twice()->andReturn($all);
        $this->repository->expects('delete')->with(1)->andReturn(true);

        $this->service->getAll();

        $this->assertTrue($this->service->delete(1));

        // cache was flushed, so the repository is hit again
        $this->service->getAll();
    }

    public function test_truncate_flushes_cache_and_delegates(): void
    {
        $league = $this->league(1, 'Test League', PoeVersionEnum::POE_2);

        $this->repository->expects('find')->twice()->with(1)->andReturn($league);
        $this->repository->expects('truncate');

        $this->service->find(1);

        $this->service->truncate();

        $this->service->find(1);
    }

    public function test_upsert_by_ninja_upserts_ninja_leagues_as_active(): void
    {
        $this->ninjaService
            ->expects('getAvailableLeagues')
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(new PoeNinjaLeagueResponse([
                $this->ninjaLeague('Fresh League'),
                $this->ninjaLeague('Standard'),
            ]));

        $this->repository
            ->expects('getByLeague')
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(collect());

        $this->repository
            ->expects('upsert')
            ->withArgs(function ($params): bool {
                $this->assertEquals([
                    ['league_name' => 'Fresh League', 'poe_version' => 'poe2', 'is_active' => true],
                    ['league_name' => 'Standard', 'poe_version' => 'poe2', 'is_active' => true],
                ], collect($params)->values()->all());

                return true;
            });

        $this->ruleRepository->expects('upsertDefaultForLeagues');
        $this->repository->expects('getAllWithoutRules')->andReturns(collect());

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);
    }

    public function test_upsert_by_ninja_deactivates_leagues_missing_from_ninja(): void
    {
        $this->ninjaService
            ->expects('getAvailableLeagues')
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(new PoeNinjaLeagueResponse([
                $this->ninjaLeague('Standard'),
            ]));

        $this->repository
            ->expects('getByLeague')
            ->with(PoeVersionEnum::POE_2)
            ->andReturn(collect([
                $this->league(1, 'Standard', PoeVersionEnum::POE_2, true),
                $this->league(2, 'Old League', PoeVersionEnum::POE_2, true),
                $this->league(3, 'Ancient League', PoeVersionEnum::POE_2, false),
            ]));

        $this->repository
            ->expects('upsert')
            ->withArgs(function ($params): bool {
                $byName = collect($params)->keyBy('league_name');

                $this->assertCount(2, $byName);
                $this->assertTrue($byName['Standard']['is_active']);
                $this->assertFalse($byName['Old League']['is_active']);
                $this->assertSame('poe2', $byName['Old League']['poe_version']);

                return true;
            });

        $this->ruleRepository->expects('upsertDefaultForLeagues');
        $this->repository->expects('getAllWithoutRules')->andReturns(collect());

        $this->service->upsertByNinja(PoeVersionEnum::POE_2);
    }

    public function test_upsert_by_ninja_flushes_cached_leagues(): void
    {
        $this->repository->expects('getAll')->twice()->andReturn(collect());
        $this->repository->expects('getByLeague')->andReturn(collect());
        $this->repository->expects('upsert');
        $this->ninjaService
            ->expects('getAvailableLeagues')
            ->andReturn(new PoeNinjaLeagueResponse([$this->ninjaLeague('Standard')]));

        $this->ruleRepository->expects('upsertDefaultForLeagues');
        $this->repository->expects('getAllWithoutRules')->andReturns(collect());

        $this->service->getAll();

        $this->service->upsertByNinja(PoeVersionEnum::POE_1);

        $this->service->getAll();
    }

    public function test_update_or_create_rule_delegates_to_repository(): void
    {
        $now = Carbon::now()->toDateString();

        $this->repository->expects('find')->with(1)->andReturns($this->league(1, 'Test League', PoeVersionEnum::POE_2));
        $this->ruleRepository->expects('updateOrCreate')->with(15, 1)->andReturns(
            new LeagueCheckRuleDto(
                1,
                15,
                $now, $now,
            )
        );

        $this->service->updateOrCreateRule(15, 1);
    }

    public function test_update_or_create_rule_passes_null_minutes(): void
    {
        $now = Carbon::now()->toDateString();

        $this->repository->expects('find')->with(1)->andReturns($this->league(1, 'Test League', PoeVersionEnum::POE_2));
        $this->ruleRepository->expects('updateOrCreate')->with(null, 1)->andReturns(
            new LeagueCheckRuleDto(
                1,
                15,
                $now, $now,
            )
        );

        $this->service->updateOrCreateRule(null, 1);
    }

    public function test_update_or_create_rule_throws_and_skips_write_when_league_missing(): void
    {
        $this->repository->expects('find')->with(99)->andThrow(new ModelNotFoundException());
        $this->ruleRepository->allows('updateOrCreate')->never();

        $this->expectException(ModelNotFoundException::class);

        $this->service->updateOrCreateRule(15, 99);
    }

    public function test_update_or_create_rule_flushes_cached_league(): void
    {
        $now = Carbon::now()->toDateString();

        $this->repository->expects('find')->with(1)->twice()->andReturns($this->league(1, 'Test League', PoeVersionEnum::POE_2));
        $this->ruleRepository->expects('updateOrCreate')->with(15, 1)->andReturns(
            new LeagueCheckRuleDto(
                1,
                15,
                $now, $now,
            )
        );

        $this->service->find(1);
        $this->service->updateOrCreateRule(15, 1);

        // cache was flushed by the rule update, so this hits the repository again
        $this->service->find(1);
    }

    private function leagueWithCore(int $id, string $name, ?int $coreId): PoeLeagueWithLastCoreDto
    {
        return new PoeLeagueWithLastCoreDto(
            $id,
            Carbon::now()->toDateTimeString(),
            Carbon::now()->toDateTimeString(),
            $name,
            PoeVersionEnum::POE_1->value,
            true,
            Carbon::now()->toDateTimeString(),
            $coreId,
            null,
        );
    }

    public function test_get_active_with_latest_core_returns_repository_result_and_caches_it(): void
    {
        $leagues = collect([
            $this->leagueWithCore(1, 'One', 10),
            $this->leagueWithCore(2, 'Two', 20),
        ]);

        $this->repository->expects('getActiveWithLatestCore')->once()->andReturn($leagues);

        $this->assertEquals($leagues, $this->service->getActiveWithLatestCore());
        $this->assertEquals($leagues, $this->service->getActiveWithLatestCore());
    }

    public function test_get_active_with_latest_core_caches_empty_result(): void
    {
        $this->repository->expects('getActiveWithLatestCore')->once()->andReturn(collect());

        $this->assertTrue($this->service->getActiveWithLatestCore()->isEmpty());
        $this->assertTrue($this->service->getActiveWithLatestCore()->isEmpty());
    }

    public function test_get_active_with_latest_core_is_flushed_by_delete(): void
    {
        $leagues = collect([$this->leagueWithCore(1, 'One', 10)]);

        $this->repository->expects('getActiveWithLatestCore')->twice()->andReturn($leagues);
        $this->repository->expects('delete')->with(1)->andReturn(true);

        $this->service->getActiveWithLatestCore();

        $this->service->delete(1);

        $this->service->getActiveWithLatestCore();
    }

    public function test_get_active_with_latest_core_is_flushed_by_flush_get(): void
    {
        $leagues = collect([$this->leagueWithCore(1, 'One', 10)]);

        $this->repository->expects('getActiveWithLatestCore')->twice()->andReturn($leagues);

        $this->service->getActiveWithLatestCore();

        $this->service->flushGet();

        $this->service->getActiveWithLatestCore();
    }

    public function test_get_active_with_latest_core_is_flushed_by_core_tags(): void
    {
        $leagues = collect([$this->leagueWithCore(1, 'One', 10)]);

        $this->repository->expects('getActiveWithLatestCore')->times(3)->andReturn($leagues);

        $this->service->getActiveWithLatestCore();

        Cache::tags([
            \App\Utility\CacheHelperService::generateCacheTag(
                \App\Utility\CacheHelperService::CORE_TAG
            ),
        ])->flush();

        $this->service->getActiveWithLatestCore();

        Cache::tags([
            \App\Utility\CacheHelperService::generateCacheTag(
                \App\Utility\CacheHelperService::CORE_GET_TAG
            ),
        ])->flush();

        $this->service->getActiveWithLatestCore();
    }
}
