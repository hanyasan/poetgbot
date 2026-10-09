<?php

namespace Tests\Unit\Repository\PoeLeagueRepository;

use App\DataTransferObjects\RepositoryObjects\PoeLeague;
use App\Enums\PoeVersionEnum;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\PoeLeague\PoeLeagueRepositoryContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class PoeLeagueRepositoryTest extends TestCase
{
    private PoeLeagueRepositoryContract $repository;

    private PoeLeague $poeLeague;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PoeLeagueRepositoryContract::class);

        $this->poeLeague = $this->repository->create([
            'league_name' => 'Test League',
            'poe_version' => PoeVersionEnum::POE_2->value,
        ]);
    }

    public function test_create(): void
    {
        $league = $this->repository->create([
            'league_name' => 'Another test League',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => true,
        ]);

        $this->assertSame('Another test League', $league->league_name);
        $this->assertSame(PoeVersionEnum::POE_2->value, $league->poe_version);
        $this->assertTrue($league->is_active);
        $this->assertDatabaseHas('poe_leagues', ['id' => $league->id, 'is_active' => true]);
    }

    public function test_create_defaults_to_inactive(): void
    {
        $league = $this->repository->create([
            'league_name' => 'Inactive League',
            'poe_version' => PoeVersionEnum::POE_2->value,
        ]);

        $this->assertFalse($league->is_active);
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
        $active = $this->repository->create([
            'league_name' => 'Active League',
            'poe_version' => PoeVersionEnum::POE_2->value,
            'is_active' => true,
        ]);

        $all = $this->repository->getAll();

        $this->assertSame(PoeLeagueModel::count(), $all->count());
        $this->assertContainsOnlyInstancesOf(PoeLeague::class, $all);
        $this->assertContains($this->poeLeague->id, $all->pluck('id')->all());

        $activeLeague = $all->firstWhere('id', $active->id);
        $this->assertTrue($activeLeague->is_active);
        $this->assertFalse($all->firstWhere('id', $this->poeLeague->id)->is_active);
    }

    public function test_get_by_version(): void
    {
        $poe1 = $this->repository->create([
            'league_name' => 'Test League Poe1',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);

        $poe1Leagues = $this->repository->getByLeague(PoeVersionEnum::POE_1);
        $poe2Leagues = $this->repository->getByLeague(PoeVersionEnum::POE_2);

        $this->assertContainsOnlyInstancesOf(PoeLeague::class, $poe1Leagues);
        $this->assertContains($poe1->id, $poe1Leagues->pluck('id')->all());
        $this->assertNotContains($this->poeLeague->id, $poe1Leagues->pluck('id')->all());

        $this->assertContains($this->poeLeague->id, $poe2Leagues->pluck('id')->all());
        $this->assertNotContains($poe1->id, $poe2Leagues->pluck('id')->all());
        $this->assertEmpty($poe1Leagues->pluck('poe_version')->reject(
            fn ($version) => $version === PoeVersionEnum::POE_1->value
        ));
    }

    public function test_update_or_create_updates_existing(): void
    {
        $league = $this->repository->updateOrCreate(
            ['id' => $this->poeLeague->id],
            ['league_name' => 'Renamed League', 'is_active' => true]
        );

        $this->assertSame($this->poeLeague->id, $league->id);
        $this->assertSame('Renamed League', $league->league_name);
        $this->assertTrue($league->is_active);
        $this->assertDatabaseHas('poe_leagues', [
            'id' => $this->poeLeague->id,
            'league_name' => 'Renamed League',
            'is_active' => true,
        ]);
    }

    public function test_update_or_create_creates_new(): void
    {
        $params = [
            'league_name' => 'Brand New League',
            'poe_version' => PoeVersionEnum::POE_1->value,
            'is_active' => true,
        ];

        $league = $this->repository->updateOrCreate($params, $params);

        $this->assertNotSame($this->poeLeague->id, $league->id);
        $this->assertTrue($league->is_active);
        $this->assertDatabaseHas('poe_leagues', $params);
    }

    public function test_update_one(): void
    {
        $this->assertTrue(
            $this->repository->updateOne($this->poeLeague->id, [
                'league_name' => 'Updated League',
                'is_active' => true,
            ])
        );

        $league = $this->repository->find($this->poeLeague->id);

        $this->assertSame('Updated League', $league->league_name);
        $this->assertTrue($league->is_active);
    }

    public function test_delete(): void
    {
        $this->assertTrue($this->repository->delete($this->poeLeague->id));

        $this->assertDatabaseMissing('poe_leagues', ['id' => $this->poeLeague->id]);
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
        $this->assertSame(1, PoeLeagueModel::count());
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
}
