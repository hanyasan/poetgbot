<?php

namespace Tests\Unit\Repository\CurrencyTypeRepository;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\CurrencyPrice as CurrencyPriceModel;
use App\Models\Currency\CurrencyType as CurrencyTypeModel;
use App\Models\Currency\PriceCheckCore as PriceCheckCoreModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\CurrencyType\CurrencyTypeRepositoryContract;
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

    private function core(PoeLeagueModel $league): PriceCheckCoreModel
    {
        return PriceCheckCoreModel::create(['poe_league_id' => $league->id]);
    }

    /**
     * Plain insert: the currency_prices table has no timestamp columns, so Model::create() cannot be used.
     */
    private function price(CurrencyTypeModel $type, PriceCheckCoreModel $core, float $rate): int
    {
        return CurrencyPriceModel::query()->insertGetId([
            'rate' => $rate,
            'currency_type_id' => $type->id,
            'price_check_core_id' => $core->id,
        ]);
    }

    public function test_get_all_by_league_with_price_by_core_only_returns_prices_of_that_core(): void
    {
        $chaos = $this->type($this->poe1League, 'Test Chaos Orb', 'test-chaos-poe1', 'test-chaos-orb-poe1');
        $older = $this->core($this->poe1League);
        $newer = $this->core($this->poe1League);

        $this->price($this->poe1Divine, $older, 10.0);
        $this->price($this->poe1Divine, $newer, 12.0);
        $this->price($chaos, $older, 1.0);

        $olderTypes = $this->repository->getAllByLeagueWithPriceByCore($this->poe1League->id, $older->id)->keyBy('id');
        $newerTypes = $this->repository->getAllByLeagueWithPriceByCore($this->poe1League->id, $newer->id)->keyBy('id');

        $this->assertSame([10.0], array_column($olderTypes[$this->poe1Divine->id]->prices, 'rate'));
        $this->assertSame([1.0], array_column($olderTypes[$chaos->id]->prices, 'rate'));

        $this->assertSame([12.0], array_column($newerTypes[$this->poe1Divine->id]->prices, 'rate'));
    }

    public function test_get_all_by_league_with_price_by_core_keeps_types_that_have_no_price_for_the_core(): void
    {
        $chaos = $this->type($this->poe1League, 'Test Chaos Orb', 'test-chaos-poe1', 'test-chaos-orb-poe1');
        $core = $this->core($this->poe1League);

        $this->price($this->poe1Divine, $core, 10.0);

        $types = $this->repository->getAllByLeagueWithPriceByCore($this->poe1League->id, $core->id)->keyBy('id');

        $this->assertCount(2, $types);
        $this->assertCount(1, $types[$this->poe1Divine->id]->prices);
        $this->assertSame([], $types[$chaos->id]->prices);
    }

    public function test_get_all_by_league_with_price_by_core_maps_prices_to_dtos(): void
    {
        $core = $this->core($this->poe1League);
        $priceId = $this->price($this->poe1Divine, $core, 2.5);

        $types = $this->repository->getAllByLeagueWithPriceByCore($this->poe1League->id, $core->id);

        $this->assertContainsOnlyInstancesOf(CurrencyTypeWithPricesDto::class, $types);

        $divine = $types->firstWhere('id', $this->poe1Divine->id);
        $this->assertSame('Test Divine Orb', $divine->currency_type_name);
        $this->assertSame('test-divine-poe1', $divine->currency_trade_id);
        $this->assertSame('test-divine-orb-poe1', $divine->currency_ninja_details_id);
        $this->assertContainsOnlyInstancesOf(CurrencyPriceDto::class, $divine->prices);
        $this->assertSame($priceId, $divine->prices[0]->id);
        $this->assertSame(2.5, $divine->prices[0]->rate);
    }

    public function test_get_all_by_league_with_price_by_core_is_scoped_to_the_league(): void
    {
        $core = $this->core($this->poe1League);
        $this->price($this->poe1Divine, $core, 10.0);

        $types = $this->repository->getAllByLeagueWithPriceByCore($this->poe1League->id, $core->id);

        $this->assertSame([$this->poe1Divine->id], $types->pluck('id')->all());
        $this->assertNotContains($this->poe2Divine->id, $types->pluck('id')->all());
    }

    public function test_get_all_by_league_with_price_by_core_without_types_is_empty(): void
    {
        $empty = $this->league('Test Empty League', PoeVersionEnum::POE_2);

        $result = $this->repository->getAllByLeagueWithPriceByCore($empty->id, $this->core($empty)->id);

        $this->assertTrue($result->isEmpty());
    }

    private function coreAt(PoeLeagueModel $league, string $createdAt): PriceCheckCoreModel
    {
        return PriceCheckCoreModel::create([
            'poe_league_id' => $league->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_get_all_by_league_with_latest_price_uses_the_newest_core_of_the_league(): void
    {
        $chaos = $this->type($this->poe1League, 'Test Chaos Orb', 'test-chaos-poe1', 'test-chaos-orb-poe1');
        $old = $this->coreAt($this->poe1League, '2024-01-01 00:00:00');
        $new = $this->coreAt($this->poe1League, '2024-03-01 00:00:00');
        $mid = $this->coreAt($this->poe1League, '2024-02-01 00:00:00');

        foreach ([$old, $new, $mid] as $core) {
            $this->price($this->poe1Divine, $core, (float) $core->created_at->month);
            $this->price($chaos, $core, (float) $core->created_at->month / 10);
        }

        $types = $this->repository->getAllByLeagueWithLatestPrice($this->poe1League->id)->keyBy('id');

        $this->assertContainsOnlyInstancesOf(CurrencyTypeWithPricesDto::class, $types);
        $this->assertSame([3.0], array_column($types[$this->poe1Divine->id]->prices, 'rate'));
        $this->assertSame([0.3], array_column($types[$chaos->id]->prices, 'rate'));
    }

    public function test_get_all_by_league_with_latest_price_ignores_cores_of_other_leagues(): void
    {
        $mine = $this->coreAt($this->poe1League, '2024-01-01 00:00:00');
        $foreignNewer = $this->coreAt($this->poe1OtherLeague, '2024-06-01 00:00:00');

        $this->price($this->poe1Divine, $mine, 10.0);
        $this->price($this->poe1Divine, $foreignNewer, 99.0);

        $types = $this->repository->getAllByLeagueWithLatestPrice($this->poe1League->id);

        $this->assertSame([10.0], array_column($types->first()->prices, 'rate'));
    }

    public function test_get_all_by_league_with_latest_price_breaks_created_at_ties_by_id(): void
    {
        $first = $this->coreAt($this->poe1League, '2024-01-01 00:00:00');
        $second = $this->coreAt($this->poe1League, '2024-01-01 00:00:00');

        $this->price($this->poe1Divine, $first, 1.0);
        $this->price($this->poe1Divine, $second, 2.0);

        $types = $this->repository->getAllByLeagueWithLatestPrice($this->poe1League->id);

        $this->assertSame([2.0], array_column($types->first()->prices, 'rate'));
    }

    public function test_get_all_by_league_with_latest_price_keeps_types_unpriced_by_the_latest_core(): void
    {
        $chaos = $this->type($this->poe1League, 'Test Chaos Orb', 'test-chaos-poe1', 'test-chaos-orb-poe1');
        $old = $this->coreAt($this->poe1League, '2024-01-01 00:00:00');
        $this->coreAt($this->poe1League, '2024-02-01 00:00:00');

        // priced only by the older core: not part of the latest price check
        $this->price($chaos, $old, 1.0);

        $types = $this->repository->getAllByLeagueWithLatestPrice($this->poe1League->id)->keyBy('id');

        $this->assertSame([], $types[$chaos->id]->prices);
    }

    public function test_get_all_by_league_with_latest_price_without_any_core_returns_types_with_no_prices(): void
    {
        $types = $this->repository->getAllByLeagueWithLatestPrice($this->poe1League->id);

        $this->assertSame([$this->poe1Divine->id], $types->pluck('id')->all());
        $this->assertSame([], $types->first()->prices);
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
