<?php

namespace Tests\Unit\Repository\PriceCheckCoreRepository;

use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreTypeDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\PriceCheckCore as PriceCheckCoreModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\PriceCheckCore\PriceCheckCoreRepositoryContract;
use Error;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PriceCheckCoreRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private PriceCheckCoreRepositoryContract $repository;

    private PoeLeagueModel $league;

    private PoeLeagueModel $otherLeague;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(PriceCheckCoreRepositoryContract::class);

        $this->league = PoeLeagueModel::create([
            'league_name' => 'Core Test League',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);
        $this->otherLeague = PoeLeagueModel::create([
            'league_name' => 'Core Test Other League',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);
    }

    private function core(PoeLeagueModel $league, string $createdAt, array $types = []): PriceCheckCoreModel
    {
        $core = PriceCheckCoreModel::create([
            'poe_league_id' => $league->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if ($types) {
            $core->priceCheckCoreTypes()->createMany($types);
        }

        return $core;
    }

    private function type(string $typeId, bool $isPrimary = false, float $rate = 1): array
    {
        return [
            'is_primary' => $isPrimary,
            'rate' => $rate,
            'type_id' => $typeId,
            'name' => strtoupper($typeId),
        ];
    }

    public function test_get_all(): void
    {
        $a = $this->core($this->league, '2024-01-01 00:00:00', [$this->type('divine', true)]);
        $b = $this->core($this->otherLeague, '2024-01-02 00:00:00');

        $all = $this->repository->getAll();

        $this->assertSame(PriceCheckCoreModel::count(), $all->count());
        $this->assertContainsOnlyInstancesOf(PriceCheckCoreDto::class, $all);
        $this->assertContains($a->id, $all->pluck('id')->all());
        $this->assertContains($b->id, $all->pluck('id')->all());
    }

    public function test_get_all_maps_core_types(): void
    {
        $core = $this->core($this->league, '2024-01-01 00:00:00', [
            $this->type('divine', true, 2.5),
            $this->type('chaos'),
        ]);

        $dto = $this->repository->getAll()->firstWhere('id', $core->id);

        $this->assertCount(2, $dto->price_check_core_types);
        $this->assertContainsOnlyInstancesOf(PriceCheckCoreTypeDto::class, $dto->price_check_core_types);

        $divine = collect($dto->price_check_core_types)->firstWhere('type_id', 'divine');
        $this->assertTrue($divine->is_primary);
        $this->assertSame(2.5, $divine->rate);
        $this->assertSame('DIVINE', $divine->name);
        $this->assertSame($core->id, $divine->price_check_core_id);
    }

    public function test_get_by_league(): void
    {
        $mine = $this->core($this->league, '2024-01-01 00:00:00');
        $other = $this->core($this->otherLeague, '2024-01-01 00:00:00');

        $result = $this->repository->getByLeague($this->league->id);

        $this->assertContainsOnlyInstancesOf(PriceCheckCoreDto::class, $result);
        $this->assertSame([$mine->id], $result->pluck('id')->all());
        $this->assertNotContains($other->id, $result->pluck('id')->all());
    }

    public function test_get_by_league_empty_is_base_collection(): void
    {
        $result = $this->repository->getByLeague($this->league->id);

        $this->assertSame(Collection::class, $result::class);
        $this->assertTrue($result->isEmpty());
    }

    public function test_get_latest_by_league(): void
    {
        $this->core($this->league, '2024-01-01 00:00:00');
        $newest = $this->core($this->league, '2024-03-01 00:00:00', [$this->type('divine', true)]);
        $this->core($this->league, '2024-02-01 00:00:00');
        $this->core($this->otherLeague, '2024-04-01 00:00:00');

        $latest = $this->repository->getLatestByLeague($this->league->id);

        $this->assertInstanceOf(PriceCheckCoreDto::class, $latest);
        $this->assertSame($newest->id, $latest->id);
        $this->assertCount(1, $latest->price_check_core_types);
    }

    public function test_get_latest_by_league_throws_when_league_is_empty(): void
    {
        $this->expectException(Error::class);

        $this->repository->getLatestByLeague($this->league->id);
    }

    public function test_get_from_date_by_league(): void
    {
        $old = $this->core($this->league, '2024-01-01 12:00:00');
        $boundary = $this->core($this->league, '2024-02-01 00:00:00');
        $new = $this->core($this->league, '2024-03-01 00:00:00');
        $otherLeague = $this->core($this->otherLeague, '2024-03-01 00:00:00');

        $result = $this->repository->getFromDateByLeague(Carbon::parse('2024-02-01'), $this->league->id);

        $ids = $result->pluck('id')->all();
        $this->assertContainsOnlyInstancesOf(PriceCheckCoreDto::class, $result);
        $this->assertEqualsCanonicalizing([$boundary->id, $new->id], $ids);
        $this->assertNotContains($old->id, $ids);
        $this->assertNotContains($otherLeague->id, $ids);
    }

    public function test_create_core_persists_core_and_types(): void
    {
        $this->repository->createCore($this->league->id, [
            $this->type('divine', true, 3),
            $this->type('chaos'),
        ]);

        $core = PriceCheckCoreModel::where('poe_league_id', $this->league->id)->sole();

        $this->assertCount(2, $core->priceCheckCoreTypes);
        $this->assertDatabaseHas('price_check_core_types', [
            'price_check_core_id' => $core->id,
            'type_id' => 'divine',
            'is_primary' => true,
            'rate' => 3,
        ]);
        $this->assertDatabaseHas('price_check_core_types', [
            'price_check_core_id' => $core->id,
            'type_id' => 'chaos',
            'is_primary' => false,
        ]);
    }

    public function test_create_core_without_types(): void
    {
        $this->repository->createCore($this->league->id, []);

        $core = PriceCheckCoreModel::where('poe_league_id', $this->league->id)->sole();

        $this->assertCount(0, $core->priceCheckCoreTypes);
    }
}
