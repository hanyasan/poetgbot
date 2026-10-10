<?php

namespace Tests\Unit\Repository\CurrencyTypeRepository;

use App\DataTransferObjects\RepositoryObjects\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeagueDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\CurrencyType as CurrencyTypeModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CurrencyTypeRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private CurrencyTypeRepositoryContract $repository;

    private PoeLeagueModel $poe1League;

    private PoeLeagueModel $poe1OtherLeague;

    private PoeLeagueModel $poe2League;

    private CurrencyTypeModel $poe1Divine;

    private CurrencyTypeModel $poe2Divine;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CurrencyTypeRepositoryContract::class);

        $this->poe1League = $this->league('Test Poe1 League', PoeVersionEnum::POE_1);
        $this->poe1OtherLeague = $this->league('Test Poe1 Other League', PoeVersionEnum::POE_1);
        $this->poe2League = $this->league('Test Poe2 League', PoeVersionEnum::POE_2);

        $this->poe1Divine = $this->type($this->poe1League, 'Test Divine Orb', 'test-divine-poe1', 'test-divine-orb-poe1');
        $this->poe2Divine = $this->type($this->poe2League, 'Test Divine Orb Two', 'test-divine-poe2', 'test-divine-orb-poe2');
    }

    private function league(string $name, PoeVersionEnum $version): PoeLeagueModel
    {
        return PoeLeagueModel::create([
            'league_name' => $name,
            'poe_version' => $version->value,
            'is_active' => true,
        ]);
    }

    private function type(PoeLeagueModel $league, string $name, string $tradeId, string $detailsId): CurrencyTypeModel
    {
        return CurrencyTypeModel::create([
            'currency_type_name' => $name,
            'currency_trade_id' => $tradeId,
            'currency_ninja_details_id' => $detailsId,
            'poe_league_id' => $league->id,
        ]);
    }

    public function test_find_returns_type_with_league(): void
    {
        $type = $this->repository->find($this->poe1Divine->id);

        $this->assertInstanceOf(CurrencyTypeWithLeagueDto::class, $type);
        $this->assertSame($this->poe1Divine->id, $type->id);
        $this->assertSame('Test Divine Orb', $type->currency_type_name);
        $this->assertSame('test-divine-poe1', $type->currency_trade_id);
        $this->assertSame($this->poe1League->id, $type->league->id);
        $this->assertSame('Test Poe1 League', $type->league->league_name);
        $this->assertSame(PoeVersionEnum::POE_1->value, $type->league->poe_version);
    }

    public function test_find_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->find(PHP_INT_MAX);
    }

    public function test_find_by_detail_id_for_each_poe_version(): void
    {
        $poe1 = $this->repository->findByDetailId('test-divine-orb-poe1');
        $poe2 = $this->repository->findByDetailId('test-divine-orb-poe2');

        $this->assertSame($this->poe1Divine->id, $poe1->id);
        $this->assertSame(PoeVersionEnum::POE_1->value, $poe1->league->poe_version);
        $this->assertSame($this->poe1League->id, $poe1->league->id);

        $this->assertSame($this->poe2Divine->id, $poe2->id);
        $this->assertSame(PoeVersionEnum::POE_2->value, $poe2->league->poe_version);
        $this->assertSame($this->poe2League->id, $poe2->league->id);
    }

    public function test_find_by_detail_id_throws_when_missing(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->findByDetailId('test-no-such-detail-id');
    }

    public function test_get_all_includes_types_of_both_poe_versions(): void
    {
        $all = $this->repository->getAll();

        $this->assertContainsOnlyInstancesOf(CurrencyTypeWithLeagueDto::class, $all);
        $this->assertSame(CurrencyTypeModel::count(), $all->count());

        $byId = $all->keyBy('id');
        $this->assertSame(PoeVersionEnum::POE_1->value, $byId[$this->poe1Divine->id]->league->poe_version);
        $this->assertSame(PoeVersionEnum::POE_2->value, $byId[$this->poe2Divine->id]->league->poe_version);
    }

    public function test_get_all_by_league_separates_leagues(): void
    {
        $poe1Other = $this->type($this->poe1OtherLeague, 'Test Chaos Orb', 'test-chaos-poe1-other', 'test-chaos-orb-other');

        $poe1 = $this->repository->getAllByLeague($this->poe1League->id);
        $poe1Other = $this->repository->getAllByLeague($this->poe1OtherLeague->id);
        $poe2 = $this->repository->getAllByLeague($this->poe2League->id);

        $this->assertContainsOnlyInstancesOf(CurrencyTypeDto::class, $poe1);
        $this->assertSame([$this->poe1Divine->id], $poe1->pluck('id')->all());
        $this->assertSame([$this->poe2Divine->id], $poe2->pluck('id')->all());
        $this->assertCount(1, $poe1Other);
        $this->assertNotContains($this->poe1Divine->id, $poe1Other->pluck('id')->all());
    }

    public function test_get_all_by_league_without_types_is_empty(): void
    {
        $empty = $this->league('Test Empty League', PoeVersionEnum::POE_2);

        $this->assertTrue($this->repository->getAllByLeague($empty->id)->isEmpty());
    }

    public function test_delete(): void
    {
        $this->assertTrue($this->repository->delete($this->poe1Divine->id));

        $this->assertDatabaseMissing('currency_types', ['id' => $this->poe1Divine->id]);
        $this->assertDatabaseHas('currency_types', ['id' => $this->poe2Divine->id]);
    }

    public function test_upsert_inserts_types_for_poe1_and_poe2_leagues(): void
    {
        $this->repository->upsert([
            [
                'currency_type_name' => 'Test Upsert One',
                'currency_trade_id' => 'test-upsert-one',
                'currency_ninja_details_id' => 'test-upsert-one-details',
                'poe_league_id' => $this->poe1League->id,
            ],
            [
                'currency_type_name' => 'Test Upsert Two',
                'currency_trade_id' => 'test-upsert-two',
                'currency_ninja_details_id' => 'test-upsert-two-details',
                'poe_league_id' => $this->poe2League->id,
            ],
        ]);

        $this->assertDatabaseHas('currency_types', [
            'currency_trade_id' => 'test-upsert-one',
            'poe_league_id' => $this->poe1League->id,
        ]);
        $this->assertDatabaseHas('currency_types', [
            'currency_trade_id' => 'test-upsert-two',
            'poe_league_id' => $this->poe2League->id,
        ]);
    }

    public function test_upsert_updates_existing_type_by_trade_id(): void
    {
        $this->repository->upsert([
            [
                'currency_type_name' => 'Test Divine Orb Renamed',
                'currency_trade_id' => 'test-divine-poe1',
                'currency_ninja_details_id' => 'test-divine-renamed',
                'poe_league_id' => $this->poe1League->id,
            ],
        ]);

        $type = $this->repository->find($this->poe1Divine->id);

        $this->assertSame('Test Divine Orb Renamed', $type->currency_type_name);
        $this->assertSame('test-divine-renamed', $type->currency_ninja_details_id);
        $this->assertSame(1, CurrencyTypeModel::where('currency_trade_id', 'test-divine-poe1')->count());
    }
}
