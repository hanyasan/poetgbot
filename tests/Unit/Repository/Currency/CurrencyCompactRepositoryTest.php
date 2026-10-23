<?php

namespace Tests\Unit\Repository\Currency;

use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactCurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactPriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactPriceCheckCoreTypeDto;
use App\Enums\PoeVersionEnum;
use App\Models\Currency\Compact\CompactCurrencyPrice;
use App\Models\Currency\Compact\CompactPriceCheckCore;
use App\Models\Currency\Compact\CompactPriceCheckCoreType;
use App\Models\PoeLeague as PoeLeagueModel;
use App\Repositories\Currency\Compact\CurrencyCompactRepositoryContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CurrencyCompactRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private CurrencyCompactRepositoryContract $repository;

    private PoeLeagueModel $league;

    private PoeLeagueModel $otherLeague;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CurrencyCompactRepositoryContract::class);

        $this->league = PoeLeagueModel::create([
            'league_name' => 'Compact Test League',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);
        $this->otherLeague = PoeLeagueModel::create([
            'league_name' => 'Compact Test Other League',
            'poe_version' => PoeVersionEnum::POE_1->value,
        ]);
    }

    private function price(string $name, string $tradeId, float $avgRate): array
    {
        return [
            'currency_type_name' => $name,
            'currency_trade_id' => $tradeId,
            'price_avg_rate' => $avgRate,
        ];
    }

    private function type(string $typeId, float $avgTypeRate): array
    {
        return [
            'type_name' => strtoupper($typeId),
            'type_id' => $typeId,
            'avg_type_rate' => $avgTypeRate,
        ];
    }

    public function test_make_compact_core_returns_dto(): void
    {
        $date = Carbon::parse('2024-05-01 12:00:00');

        $dto = $this->repository->makeCompactCore($this->league->id, $date, [], []);

        $this->assertInstanceOf(CompactPriceCheckCoreDto::class, $dto);
        $this->assertSame($this->league->id, $dto->league_id);
        $this->assertSame($date->timestamp, Carbon::parse($dto->date)->timestamp);
        $this->assertSame([], $dto->types);
        $this->assertSame([], $dto->prices);
    }

    public function test_make_compact_core_persists_core_row(): void
    {
        $date = Carbon::parse('2024-05-01 12:00:00');

        $dto = $this->repository->makeCompactCore($this->league->id, $date, [], []);

        $core = CompactPriceCheckCore::query()->sole();

        $this->assertSame($core->id, $dto->id);
        $this->assertSame($this->league->id, $core->league_id);
        $this->assertSame($date->timestamp, $core->date->timestamp);
    }

    public function test_make_compact_core_persists_types(): void
    {
        $dto = $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [],
            [
                $this->type('divine', 1.5),
                $this->type('chaos', 0.25),
            ],
        );

        $this->assertSame(2, CompactPriceCheckCoreType::count());
        $this->assertDatabaseHas('compact_price_check_core_types', [
            'core_id' => $dto->id,
            'type_id' => 'divine',
            'type_name' => 'DIVINE',
            'avg_type_rate' => 1.5,
        ]);
        $this->assertDatabaseHas('compact_price_check_core_types', [
            'core_id' => $dto->id,
            'type_id' => 'chaos',
            'type_name' => 'CHAOS',
            'avg_type_rate' => 0.25,
        ]);
    }

    public function test_make_compact_core_persists_prices(): void
    {
        $dto = $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [
                $this->price('Divine Orb', 'divine', 2.5),
                $this->price('Chaos Orb', 'chaos', 1.0),
            ],
            [],
        );

        $this->assertSame(2, CompactCurrencyPrice::count());
        $this->assertDatabaseHas('compact_currency_prices', [
            'core_id' => $dto->id,
            'currency_trade_id' => 'divine',
            'currency_type_name' => 'Divine Orb',
            'price_avg_rate' => 2.5,
        ]);
        $this->assertDatabaseHas('compact_currency_prices', [
            'core_id' => $dto->id,
            'currency_trade_id' => 'chaos',
            'currency_type_name' => 'Chaos Orb',
            'price_avg_rate' => 1.0,
        ]);
    }

    public function test_make_compact_core_maps_types_into_dtos(): void
    {
        $dto = $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [],
            [$this->type('divine', 1.5)],
        );

        $this->assertCount(1, $dto->types);
        $this->assertContainsOnlyInstancesOf(CompactPriceCheckCoreTypeDto::class, $dto->types);

        $type = $dto->types[0];
        $this->assertSame('divine', $type->type_id);
        $this->assertSame('DIVINE', $type->type_name);
        $this->assertSame(1.5, $type->avg_type_rate);
        $this->assertSame($dto->id, $type->core_id);
    }

    public function test_make_compact_core_maps_prices_into_dtos(): void
    {
        $dto = $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [$this->price('Divine Orb', 'divine', 2.5)],
            [],
        );

        $this->assertCount(1, $dto->prices);
        $this->assertContainsOnlyInstancesOf(CompactCurrencyPriceDto::class, $dto->prices);

        $price = $dto->prices[0];
        $this->assertSame('divine', $price->currency_trade_id);
        $this->assertSame('Divine Orb', $price->currency_type_name);
        $this->assertSame(2.5, $price->price_avg_rate);
        $this->assertSame($dto->id, $price->core_id);
    }

    public function test_make_compact_core_without_children_creates_only_the_core(): void
    {
        $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [],
            [],
        );

        $this->assertSame(1, CompactPriceCheckCore::count());
        $this->assertSame(0, CompactPriceCheckCoreType::count());
        $this->assertSame(0, CompactCurrencyPrice::count());
    }

    public function test_make_compact_core_keeps_children_scoped_to_its_own_core(): void
    {
        $first = $this->repository->makeCompactCore(
            $this->league->id,
            Carbon::parse('2024-05-01 12:00:00'),
            [$this->price('Divine Orb', 'divine', 2.5)],
            [$this->type('divine', 1.5)],
        );

        $second = $this->repository->makeCompactCore(
            $this->otherLeague->id,
            Carbon::parse('2024-05-02 12:00:00'),
            [$this->price('Chaos Orb', 'chaos', 1.0)],
            [$this->type('chaos', 0.25)],
        );

        $this->assertSame($this->league->id, $first->league_id);
        $this->assertSame($this->otherLeague->id, $second->league_id);

        $this->assertCount(1, $first->types);
        $this->assertCount(1, $first->prices);
        $this->assertSame('divine', $first->types[0]->type_id);
        $this->assertSame('divine', $first->prices[0]->currency_trade_id);

        $this->assertCount(1, $second->types);
        $this->assertCount(1, $second->prices);
        $this->assertSame('chaos', $second->types[0]->type_id);
        $this->assertSame('chaos', $second->prices[0]->currency_trade_id);

        $this->assertSame(2, CompactPriceCheckCore::count());
        $this->assertSame(2, CompactPriceCheckCoreType::count());
        $this->assertSame(2, CompactCurrencyPrice::count());
    }
}
