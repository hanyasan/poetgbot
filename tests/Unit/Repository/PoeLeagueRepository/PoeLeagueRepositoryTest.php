<?php

namespace Tests\Unit\Repository\PoeLeagueRepository;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore;
use App\Models\PoeLeague;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PoeLeagueRepositoryTest extends TestCase
{
    private PoeLeagueRepositoryContract $repository;

    private PoeLeagueModel $poeLeague;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PoeLeagueRepositoryContract::class);

        $this->poeLeague = $this->createPoeLeague([
            'league_name' => 'Test League',
            'poe_version' => PoeVersionEnum::POE_2->value,
        ]);
    }

    private function createPoeLeague(array $data): PoeLeagueModel
    {
        return PoeLeagueModel::query()->create($data);
    }

    public function test_find(): void
    {
        $league = $this->repository->find($this->poeLeague->id);

        $this->assertSame($this->poeLeague->id, $league->id);
        $this->assertSame('Test League', $league->league_name);
        $this->assertFalse($league->is_active);
    }

    public function test_find_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->find(PHP_INT_MAX);
    }

    public function test_get_all(): void
    {
        $active = $this->createPoeLeague([
            'league_name' => 'Active League',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => true,
        ]);

        $all = $this->repository->getAll();

        $this->assertSame(PoeLeagueModel::count(), $all->count());
        $this->assertContainsOnlyInstancesOf(PoeLeagueDto::class, $all);
        $this->assertContains($this->poeLeague->id, $all->pluck('id')->all());

        $activeLeague = $all->firstWhere('id', $active->id);
        $this->assertTrue($activeLeague->is_active);
        $this->assertFalse($all->firstWhere('id', $this->poeLeague->id)->is_active);
    }

    public function test_get_by_version(): void
    {
        $poe1 = $this->createPoeLeague([
            'league_name' => 'Test League Poe1',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);

        $poe1Leagues = $this->repository->getByLeague(PoeVersionEnum::POE_1);
        $poe2Leagues = $this->repository->getByLeague(PoeVersionEnum::POE_2);

        $this->assertContainsOnlyInstancesOf(PoeLeagueDto::class, $poe1Leagues);
        $this->assertContains($poe1->id, $poe1Leagues->pluck('id')->all());
        $this->assertNotContains($this->poeLeague->id, $poe1Leagues->pluck('id')->all());

        $this->assertContains($this->poeLeague->id, $poe2Leagues->pluck('id')->all());
        $this->assertNotContains($poe1->id, $poe2Leagues->pluck('id')->all());
        $this->assertEmpty($poe1Leagues->pluck('poe_version')->reject(
            fn ($version) => $version === PoeVersionEnum::POE_1->value
        ));
    }

    public function test_get_all_active(): void
    {
        $activePoe2 = $this->createPoeLeague([
            'league_name' => 'Active League Poe2',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => true,
        ]);
        $activePoe1 = $this->createPoeLeague([
            'league_name' => 'Active League Poe1',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => true,
        ]);
        $inactivePoe1 = $this->createPoeLeague([
            'league_name' => 'Inactive League Poe1',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => false,
        ]);

        $poe2 = $this->repository->getAllActiveByVersion(PoeVersionEnum::POE_2);
        $poe1 = $this->repository->getAllActiveByVersion(PoeVersionEnum::POE_1);

        $this->assertContainsOnlyInstancesOf(PoeLeagueDto::class, $poe2);
        $this->assertContains($activePoe2->id, $poe2->pluck('id')->all());
        $this->assertNotContains($this->poeLeague->id, $poe2->pluck('id')->all());
        $this->assertNotContains($activePoe1->id, $poe2->pluck('id')->all());
        $this->assertEmpty($poe2->reject(fn (PoeLeagueDto $league) => $league->is_active));

        $this->assertContains($activePoe1->id, $poe1->pluck('id')->all());
        $this->assertNotContains($inactivePoe1->id, $poe1->pluck('id')->all());
        $this->assertNotContains($activePoe2->id, $poe1->pluck('id')->all());
    }

    public function test_empty_results_are_base_collections(): void
    {
        DB::beginTransaction();

        try {
            // price_check_cores and currency_types reference poe_leagues, so a bare
            // delete() fails with a foreign key violation once real data was imported.
            // The enclosing transaction rolls all of this back.
            DB::table('price_check_core_types')->delete();
            DB::table('currency_prices')->delete();
            DB::table('price_check_cores')->delete();
            DB::table('currency_types')->delete();
            DB::table('league_check_rules')->delete();
            PoeLeagueModel::query()->delete();

            $this->assertSame(Collection::class, $this->repository->getAll()::class);
            $this->assertSame(Collection::class, $this->repository->getByLeague(PoeVersionEnum::POE_1)::class);
            $this->assertSame(Collection::class, $this->repository->getAllActiveByVersion(PoeVersionEnum::POE_1)::class);
        } finally {
            DB::rollBack();
        }
    }

    public function test_upsert_inserts_new_leagues(): void
    {
        $this->repository->upsert([
            [
                'league_name' => 'Upserted League One',
                'poe_version' => PoeVersionEnum::POE_1->value,
                'is_active' => true,
            ],
            [
                'league_name' => 'Upserted League Two',
                'poe_version' => PoeVersionEnum::POE_2->value,
                'is_active' => false,
            ],
        ]);

        $this->assertDatabaseHas('poe_leagues', [
            'league_name' => 'Upserted League One',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('poe_leagues', [
            'league_name' => 'Upserted League Two',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => false,
        ]);
    }

    public function test_upsert_updates_is_active_of_existing_league(): void
    {
        $this->assertFalse($this->repository->find($this->poeLeague->id)->is_active);

        $this->repository->upsert([
            [
                'league_name' => 'Test League',
                'poe_version' => PoeVersionEnum::POE_2->value,
                'is_active' => true,
            ],
        ]);

        $this->assertTrue($this->repository->find($this->poeLeague->id)->is_active);
        $this->assertSame(1, PoeLeagueModel::where('league_name', 'Test League')->where('poe_version', PoeVersionEnum::POE_2->value)->count());
    }

    public function test_upsert_only_touches_is_active(): void
    {
        $this->repository->upsert([
            [
                'league_name' => 'Test League',
                'poe_version' => PoeVersionEnum::POE_2->value,
                'is_active' => true,
            ],
        ]);

        $league = $this->repository->find($this->poeLeague->id);

        $this->assertSame($this->poeLeague->id, $league->id);
        $this->assertSame('Test League', $league->league_name);
        $this->assertSame(PoeVersionEnum::POE_2->value, $league->poe_version);
    }

    private function createCore(int $leagueId, string $createdAt): PriceCheckCore
    {
        return PriceCheckCore::query()->create([
            'poe_league_id' => $leagueId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_get_active_with_latest_core_returns_latest_core_per_league(): void
    {
        $league = $this->createPoeLeague([
            'league_name' => 'Cored League',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => true,
        ]);

        $this->createCore($league->id, '2030-01-01 10:00:00');
        $latest = $this->createCore($league->id, '2030-01-01 12:00:00');
        $this->createCore($league->id, '2030-01-01 11:00:00');

        $result = $this->repository->getActiveWithLatestCore();

        $this->assertContainsOnlyInstancesOf(PoeLeagueWithLastCoreDto::class, $result);

        $rows = $result->where('id', $league->id);
        $this->assertCount(1, $rows);

        $dto = $rows->first();
        $this->assertSame($latest->id, $dto->last_core_id);
        $this->assertSame('Cored League', $dto->league_name);
        $this->assertSame(PoeVersionEnum::POE_1->value, $dto->poe_version);
        $this->assertTrue($dto->is_active);
        $this->assertSame(
            Carbon::parse('2030-01-01 12:00:00')->getTimestamp(),
            Carbon::parse($dto->last_core_created_at)->getTimestamp(),
        );
    }

    public function test_get_active_with_latest_core_resolves_each_league_separately(): void
    {
        $first = $this->createPoeLeague([
            'league_name' => 'First Cored',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => true,
        ]);
        $second = $this->createPoeLeague([
            'league_name' => 'Second Cored',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => true,
        ]);

        $this->createCore($first->id, '2030-02-01 10:00:00');
        $firstLatest = $this->createCore($first->id, '2030-02-02 10:00:00');
        $secondLatest = $this->createCore($second->id, '2030-02-01 08:00:00');

        $result = $this->repository->getActiveWithLatestCore();

        $this->assertSame($firstLatest->id, $result->firstWhere('id', $first->id)->last_core_id);
        $this->assertSame($secondLatest->id, $result->firstWhere('id', $second->id)->last_core_id);
    }

    public function test_get_active_with_latest_core_excludes_inactive_leagues(): void
    {
        $inactive = $this->createPoeLeague([
            'league_name' => 'Inactive Cored',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => false,
        ]);
        $this->createCore($inactive->id, '2030-03-01 10:00:00');

        $result = $this->repository->getActiveWithLatestCore();

        $this->assertNotContains($inactive->id, $result->pluck('id')->all());
    }

    public function test_get_active_with_latest_core_returns_base_collection_when_empty(): void
    {
        DB::beginTransaction();

        try {
            PoeLeague::query()->update(['is_active' => false]);

            $result = $this->repository->getActiveWithLatestCore();

            $this->assertSame(Collection::class, $result::class);
            $this->assertTrue($result->isEmpty());
        } finally {
            DB::rollBack();
        }
    }

    public function test_get_all_without_rules(): void
    {
        $noRuleLeague = $this->createPoeLeague([
            'league_name' => 'No Rules',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => false,
        ]);

        $result = $this->repository->getAllWithoutRules();

        $this->assertSame(Collection::class, $result::class);
        $this->assertEquals(PoeLeagueDto::class, $result->where('id', $noRuleLeague->id)->first()::class);
        $this->assertDatabaseMissing('league_check_rules', ['poe_league_id' => $noRuleLeague->id]);
    }


}
