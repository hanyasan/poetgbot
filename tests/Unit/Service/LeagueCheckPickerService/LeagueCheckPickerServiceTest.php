<?php

namespace Tests\Unit\Service\LeagueCheckPickerService;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\PriceCheckServices\LeagueCheckPickerService\LeagueCheckPickerService;
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
        $this->service = new LeagueCheckPickerService($this->leagueService);
    }

    private function makeLeague(int $id, string $name): PoeLeagueDto
    {
        return new PoeLeagueDto($id, '2024-01-01 00:00:00', '2024-01-01 00:00:00', $name, 'poe1', true);
    }

    public function test_returns_active_leagues_from_league_service(): void
    {
        $leagues = collect([
            $this->makeLeague(1, 'Standard'),
            $this->makeLeague(2, 'Settlers'),
        ]);

        $this->leagueService->expects('getAllActive')->andReturn($leagues);

        $result = $this->service->pickLeaguesForCheck();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame($leagues, $result);
        $this->assertCount(2, $result);
    }

    public function test_returns_empty_collection_when_no_active_leagues(): void
    {
        $this->leagueService->expects('getAllActive')->andReturn(collect());

        $this->assertTrue($this->service->pickLeaguesForCheck()->isEmpty());
    }
}
