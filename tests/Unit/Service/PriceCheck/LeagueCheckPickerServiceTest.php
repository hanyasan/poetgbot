<?php

namespace Tests\Unit\Service\PriceCheck;

use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\Mapper\MapperService;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LeagueCheckPickerServiceTest extends TestCase
{
    private MockInterface|PoeLeagueServiceContract $leagueService;

    private LeagueCheckPickerService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->leagueService = Mockery::mock(PoeLeagueServiceContract::class);
        $this->service = new LeagueCheckPickerService($this->leagueService, app(MapperService::class));
    }

    private function makeLeague(
        int $id,
        string $name,
        int $minutesSinceLastCore,
        int|null $minutesCheck,
    ): PoeLeagueWithLastCoreDto {
        $now = Carbon::now()->toDateTimeString();

        return new PoeLeagueWithLastCoreDto(
            $id,
            $now,
            $now,
            $name,
            'poe1',
            true,
            Carbon::now()->subMinutes($minutesSinceLastCore)->toDateTimeString(),
            $id * 100,
            new LeagueCheckRuleDto($id, $minutesCheck, $now, $now),
        );
    }

    public function test_picks_league_when_check_interval_has_elapsed(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Standard', 120, 60),
        ]));

        $result = $this->service->pickLeaguesForCheck();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame([1], $result->pluck('id')->values()->all());
    }

    public function test_skips_league_checked_within_interval(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Standard', 10, 60),
        ]));

        $this->assertTrue($this->service->pickLeaguesForCheck()->isEmpty());
    }

    public function test_picks_only_due_leagues_from_mixed_set(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Due', 90, 60),
            $this->makeLeague(2, 'Fresh', 5, 60),
            $this->makeLeague(3, 'Also Due', 30, 15),
        ]));

        $result = $this->service->pickLeaguesForCheck();

        $this->assertSame([1, 3], $result->pluck('id')->sort()->values()->all());
    }

    public function test_returns_empty_collection_when_no_active_leagues(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect());

        $result = $this->service->pickLeaguesForCheck();

        $this->assertSame(Collection::class, $result::class);
        $this->assertTrue($result->isEmpty());
    }

    public function test_picks_league_exactly_at_check_interval_boundary(): void
    {
        $this->travelTo(Carbon::parse('2030-01-01 12:00:00'));

        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Boundary', 60, 60),
        ]));

        $this->assertSame([1], $this->service->pickLeaguesForCheck()->pluck('id')->values()->all());
    }

    public function test_skips_league_one_minute_before_check_interval(): void
    {
        $this->travelTo(Carbon::parse('2030-01-01 12:00:00'));

        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Almost', 59, 60),
        ]));

        $this->assertTrue($this->service->pickLeaguesForCheck()->isEmpty());
    }

    public function test_returns_mapped_league_dtos(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(7, 'Mapped', 120, 60),
        ]));

        $result = $this->service->pickLeaguesForCheck();

        $this->assertContainsOnlyInstancesOf(PoeLeagueDto::class, $result);
        $this->assertSame('Mapped', $result->first()->league_name);
        $this->assertSame('poe1', $result->first()->poe_version);
        $this->assertTrue($result->first()->is_active);
    }

    public function test_does_not_fail_when_league_has_no_check_rule(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            new PoeLeagueWithLastCoreDto(1, $now, $now, 'No Rule', 'poe1', true, $now, 100, null),
        ]));

        $this->assertInstanceOf(Collection::class, $this->service->pickLeaguesForCheck());
    }

    public function test_leagues_without_cores_returns(): void
    {
        $now = Carbon::now()->toDateTimeString();
        $old = Carbon::now()->subCenturies(1)->toDateTimeString();

        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            new PoeLeagueWithLastCoreDto(1, $now, $now, 'No Core', 'poe1', true, null, null, new LeagueCheckRuleDto(1, 1, $old, $old)),
        ]));

        $this->assertInstanceOf(PoeLeagueDto::class, $this->service->pickLeaguesForCheck()->first());
    }

    public function test_does_not_fail_when_minutes_check_is_null(): void
    {
        $this->leagueService->expects('getActiveWithLatestCore')->andReturn(collect([
            $this->makeLeague(1, 'Null Minutes', 120, null),
        ]));

        $this->assertInstanceOf(Collection::class, $this->service->pickLeaguesForCheck());
    }
}
