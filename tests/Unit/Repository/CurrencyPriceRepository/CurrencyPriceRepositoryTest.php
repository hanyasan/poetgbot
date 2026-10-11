<?php

namespace Tests\Unit\Repository\CurrencyPriceRepository;

use App\Enums\PoeVersionEnum;
use App\Models\Currency\CurrencyPrice as CurrencyPriceModel;
use App\Models\Currency\CurrencyType as CurrencyTypeModel;
use App\Models\Currency\PriceCheckCore as PriceCheckCoreModel;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\CurrencyPrice\CurrencyPriceRepositoryContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CurrencyPriceRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private CurrencyPriceRepositoryContract $repository;

    private PriceCheckCoreModel $core;

    private PriceCheckCoreModel $otherCore;

    private CurrencyTypeModel $divine;

    private CurrencyTypeModel $chaos;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CurrencyPriceRepositoryContract::class);

        $league = PoeLeagueModel::create([
            'league_name' => 'Price Test League',
            'poe_version' => PoeVersionEnum::POE_2->value,
        ]);

        $this->core = PriceCheckCoreModel::create(['poe_league_id' => $league->id]);
        $this->otherCore = PriceCheckCoreModel::create(['poe_league_id' => $league->id]);

        $this->divine = $this->type($league, 'Test Divine', 'test-price-divine');
        $this->chaos = $this->type($league, 'Test Chaos', 'test-price-chaos');
    }

    private function type(PoeLeagueModel $league, string $name, string $tradeId): CurrencyTypeModel
    {
        return CurrencyTypeModel::create([
            'currency_type_name' => $name,
            'currency_trade_id' => $tradeId,
            'currency_ninja_details_id' => $tradeId . '-details',
            'poe_league_id' => $league->id,
        ]);
    }

    public function test_insert_persists_every_row(): void
    {
        $this->repository->insert([
            ['rate' => 1.0, 'price_check_core_id' => $this->core->id, 'currency_type_id' => $this->divine->id],
            ['rate' => 0.25, 'price_check_core_id' => $this->core->id, 'currency_type_id' => $this->chaos->id],
        ]);

        $this->assertSame(2, CurrencyPriceModel::where('price_check_core_id', $this->core->id)->count());
        $this->assertDatabaseHas('currency_prices', [
            'rate' => 1.0,
            'price_check_core_id' => $this->core->id,
            'currency_type_id' => $this->divine->id,
        ]);
        $this->assertDatabaseHas('currency_prices', [
            'rate' => 0.25,
            'price_check_core_id' => $this->core->id,
            'currency_type_id' => $this->chaos->id,
        ]);
    }

    public function test_insert_keeps_prices_of_different_cores_apart(): void
    {
        $this->repository->insert([
            ['rate' => 10.0, 'price_check_core_id' => $this->core->id, 'currency_type_id' => $this->divine->id],
            ['rate' => 12.0, 'price_check_core_id' => $this->otherCore->id, 'currency_type_id' => $this->divine->id],
        ]);

        $this->assertSame(
            [10.0],
            CurrencyPriceModel::where('price_check_core_id', $this->core->id)->pluck('rate')->all()
        );
        $this->assertSame(
            [12.0],
            CurrencyPriceModel::where('price_check_core_id', $this->otherCore->id)->pluck('rate')->all()
        );
    }

    public function test_insert_ignores_keys_outside_the_price_columns(): void
    {
        $this->repository->insert([
            [
                'rate' => 2.5,
                'price_check_core_id' => $this->core->id,
                'currency_type_id' => $this->divine->id,
                'currency_type_name' => 'not a currency_prices column',
            ],
        ]);

        $this->assertDatabaseHas('currency_prices', [
            'rate' => 2.5,
            'price_check_core_id' => $this->core->id,
            'currency_type_id' => $this->divine->id,
        ]);
    }

    public function test_insert_with_no_rows_is_a_no_op(): void
    {
        $before = CurrencyPriceModel::count();

        $this->repository->insert([]);

        $this->assertSame($before, CurrencyPriceModel::count());
    }

    public function test_insert_is_not_an_upsert(): void
    {
        $row = ['rate' => 1.0, 'price_check_core_id' => $this->core->id, 'currency_type_id' => $this->divine->id];

        $this->repository->insert([$row]);
        $this->repository->insert([$row]);

        $this->assertSame(
            2,
            CurrencyPriceModel::where('price_check_core_id', $this->core->id)
                ->where('currency_type_id', $this->divine->id)
                ->count()
        );
    }
}
