<?php

namespace Tests\Unit\Service\DataServices\Currency;

use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactPriceCheckCoreDto;
use App\Repositories\Currency\Compact\CurrencyCompactRepositoryContract;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use App\Services\DataServices\Currency\CurrencyCompactService\CurrencyCompactService;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class CurrencyCompactServiceTest extends TestCase
{
    private MockInterface|PriceCheckCoreRepositoryContract $coreRepository;

    private MockInterface|CurrencyPriceRepositoryContract $priceRepository;

    private MockInterface|CurrencyCompactRepositoryContract $compactRepository;

    private CurrencyCompactService $service;

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2024-03-10 15:00:00', 'UTC'));
        config(['poe.compact_price.days_fresh' => 7]);

        $this->coreRepository = Mockery::mock(PriceCheckCoreRepositoryContract::class);
        $this->priceRepository = Mockery::mock(CurrencyPriceRepositoryContract::class);
        $this->compactRepository = Mockery::mock(CurrencyCompactRepositoryContract::class);
        $this->coreService = Mockery::mock(PriceCheckCoreServiceContract::class);

        $this->service = new CurrencyCompactService(
            $this->coreRepository,
            $this->priceRepository,
            $this->compactRepository,
            $this->coreService,
        );
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function row(int $id, int $leagueId, string $date): array
    {
        return ['id' => $id, 'poe_league_id' => $leagueId, 'date' => $date];
    }

    private function compactDto(): CompactPriceCheckCoreDto
    {
        return new CompactPriceCheckCoreDto(1, '2024-03-01', 1, [], []);
    }

    public function test_it_asks_for_cores_older_than_the_fresh_days_threshold(): void
    {
        $this->coreRepository
            ->expects('getLeagueDailyPeriodsCoreIds')
            ->withArgs(fn (Carbon $date) => $date->toDateTimeString() === '2024-03-03 15:00:00'
                && $date->getTimezone()->getName() === 'UTC')
            ->andReturn([]);

        $this->service->compactCoreData();
    }

    public function test_it_uses_configured_fresh_days(): void
    {
        config(['poe.compact_price.days_fresh' => 30]);

        $this->coreRepository
            ->expects('getLeagueDailyPeriodsCoreIds')
            ->withArgs(fn (Carbon $date) => $date->toDateString() === '2024-02-09')
            ->andReturn([]);

        $this->service->compactCoreData();
    }

    public function test_nothing_is_compacted_or_deleted_without_cores(): void
    {
        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([]);
        $this->compactRepository->shouldNotReceive('makeCompactCore');
        $this->coreRepository->shouldNotReceive('deleteMany');

        $this->service->compactCoreData();
    }

    public function test_it_compacts_a_group_and_deletes_its_source_cores(): void
    {
        $prices = [['price_avg_rate' => 2.5, 'currency_type_name' => 'Divine Orb', 'currency_trade_id' => 'divine']];
        $types = [['type_id' => 'divine', 'name' => 'DIVINE', 'avg_type_rate' => 1.5]];

        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([
            $this->row(10, 1, '2024-03-01'),
            $this->row(11, 1, '2024-03-01'),
        ]);
        $this->priceRepository->expects('getAvgCorePricesData')->with([10, 11])->andReturn($prices);
        $this->coreRepository->expects('getAvgCoreTypesData')->with([10, 11])->andReturn($types);
        $this->compactRepository
            ->expects('makeCompactCore')
            ->withArgs(fn (int $leagueId, Carbon $date, array $p, array $t) => $leagueId === 1
                && $date->toDateString() === '2024-03-01'
                && $date->getTimezone()->getName() === 'UTC'
                && $p === $prices
                && $t === $types)
            ->andReturn($this->compactDto());
        $this->coreRepository->expects('deleteMany')->with([10, 11]);
        $this->coreService->expects('flushLeagueId')->once();

        $this->service->compactCoreData();
    }

    public function test_it_groups_cores_by_league_and_day(): void
    {
        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([
            $this->row(1, 1, '2024-03-01'),
            $this->row(2, 2, '2024-03-01'),
            $this->row(3, 1, '2024-03-02'),
            $this->row(4, 1, '2024-03-01'),
        ]);

        $this->priceRepository->allows('getAvgCorePricesData')->andReturn([]);
        $this->coreRepository->allows('getAvgCoreTypesData')->andReturn([]);

        $made = [];
        $this->compactRepository
            ->expects('makeCompactCore')
            ->times(3)
            ->andReturnUsing(function (int $leagueId, Carbon $date) use (&$made) {
                $made[] = $leagueId . ' ' . $date->toDateString();

                return $this->compactDto();
            });

        $deleted = [];
        $this->coreRepository
            ->expects('deleteMany')
            ->times(3)
            ->andReturnUsing(function (array $ids) use (&$deleted) {
                $deleted[] = $ids;
            });

        $this->coreService->expects('flushLeagueId')->times(3);

        $this->service->compactCoreData();

        $this->assertSame(['1 2024-03-01', '2 2024-03-01', '1 2024-03-02'], $made);
        $this->assertSame([[1, 4], [2], [3]], $deleted);
    }

    public function test_it_rethrows_and_stops_when_compacting_fails(): void
    {
        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([
            $this->row(1, 1, '2024-03-01'),
            $this->row(2, 2, '2024-03-01'),
        ]);
        $this->priceRepository->allows('getAvgCorePricesData')->andReturn([]);
        $this->coreRepository->allows('getAvgCoreTypesData')->andReturn([]);
        $this->compactRepository
            ->expects('makeCompactCore')
            ->once()
            ->andThrow(new RuntimeException('boom'));
        $this->coreRepository->shouldNotReceive('deleteMany');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $this->service->compactCoreData();
    }

    public function test_it_rolls_back_the_transaction_on_failure(): void
    {
        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([
            $this->row(1, 1, '2024-03-01'),
        ]);
        $this->priceRepository->allows('getAvgCorePricesData')->andReturn([]);
        $this->coreRepository->allows('getAvgCoreTypesData')->andReturn([]);
        $this->compactRepository->allows('makeCompactCore')->andThrow(new RuntimeException('boom'));

        $level = \DB::transactionLevel();

        try {
            $this->service->compactCoreData();
            $this->fail('Exception was not rethrown');
        } catch (RuntimeException) {
            $this->assertSame($level, \DB::transactionLevel());
        }
    }

    public function test_it_commits_the_transaction_on_success(): void
    {
        $this->coreRepository->expects('getLeagueDailyPeriodsCoreIds')->andReturn([
            $this->row(1, 1, '2024-03-01'),
        ]);
        $this->priceRepository->allows('getAvgCorePricesData')->andReturn([]);
        $this->coreRepository->allows('getAvgCoreTypesData')->andReturn([]);
        $this->compactRepository->allows('makeCompactCore')->andReturn($this->compactDto());
        $this->coreRepository->allows('deleteMany');
        $this->coreService->expects('flushLeagueId')->once();

        $level = \DB::transactionLevel();

        $this->service->compactCoreData();

        $this->assertSame($level, \DB::transactionLevel());
    }
}
