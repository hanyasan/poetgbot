<?php

namespace App\Repositories\Currency\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithLeagueDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\Models\Currency\CurrencyType as CurrencyTypeModel;
use App\Models\Currency\PriceCheckCore;
use App\Services\Mapper\MapperService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

final readonly class CurrencyTypeRepository implements CurrencyTypeRepositoryContract
{
    public function __construct(
        private CurrencyTypeModel $model,
        private MapperService     $mapper
    ) {}

    #[\Override]
    public function delete(int $id): bool
    {
        return $this->model->newQuery()->where('id', $id)->delete();
    }

    /**
     * @psalm-return Collection<int, CurrencyTypeWithLeagueDto>
     */
    #[\Override]
    public function getAll(): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->with('league')
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                return $this->mapper->mapFromArray(
                    CurrencyTypeWithLeagueDto::class,
                    $priceType->toArray()
                );
            });
    }

    /**
     * @psalm-return Collection<int, CurrencyTypeDto>
     */
    #[\Override]
    public function getAllByLeague(int $leagueId): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->where('poe_league_id', $leagueId)
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                return $this->mapper->mapFromArray(
                    CurrencyTypeDto::class,
                    $priceType->toArray()
                );
            });
    }

    /**
     * @psalm-return Collection<int, CurrencyTypeWithPricesDto>
     */
    #[\Override]
    public function getAllByLeagueWithPriceByCore(int $leagueId, int $coreId): Collection
    {
        return $this
            ->model
            ->newQuery()
            ->where('poe_league_id', $leagueId)
            ->with(['prices' => static function (Relation $query) use ($coreId) {
                $query->where('price_check_core_id', $coreId);
            }])
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                return $this->mapper->mapFromArray(
                    CurrencyTypeWithPricesDto::class,
                    $priceType->toArray()
                );
            });
    }

    /**
     * @psalm-return Collection<int, CurrencyTypeWithPricesDto>
     */
    #[\Override]
    public function getAllByLeagueWithLatestPrice(int $leagueId): Collection
    {
        //todo: зарефачить джойном
        $latestCoreId = PriceCheckCore::query()
            ->select('id')
            ->where('poe_league_id', $leagueId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);

        return $this
            ->model
            ->newQuery()
            ->where('poe_league_id', $leagueId)
            ->with(['prices' => static function (Relation $query) use ($latestCoreId) {
                $query->where('price_check_core_id', $latestCoreId);
            }])
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                return $this->mapper->mapFromArray(
                    CurrencyTypeWithPricesDto::class,
                    $priceType->toArray()
                );
            });
    }

    #[\Override]
    public function find(int $id): CurrencyTypeWithLeagueDto
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeagueDto::class,
            $this->model->newQuery()->with('league')->findOrFail($id)->toArray()
        );
    }

    /**
     * @param array<int, array{
     *   currency_type_name: string,
     *   currency_trade_id: string,
     *   currency_ninja_details_id: string,
     *   poe_league_id: int,
     * }> $insertData
     *
     * @return void
     */
    #[\Override]
    public function upsert(array $insertData): void
    {
        $this->model->newQuery()->upsert(
            $insertData,
            ['currency_trade_id' , 'poe_league_id'],
            ['currency_type_name', 'currency_ninja_details_id'],
        );
    }
}
