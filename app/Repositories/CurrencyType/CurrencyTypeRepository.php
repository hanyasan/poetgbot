<?php

namespace App\Repositories\CurrencyType;

use App\DataTransferObjects\RepositoryObjects\CurrencyType;
use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\Models\PoeLeague as CurrencyTypeModel;
use App\Repositories\BaseEloquentRepository;
use App\Services\Mapper\MapperService;
use Illuminate\Support\Collection;

final class CurrencyTypeRepository extends BaseEloquentRepository implements CurrencyTypeRepositoryContract
{
    public function __construct(
        CurrencyTypeModel $model,
        MapperService $mapper
    ) {
        parent::__construct($model, $mapper);
    }

    public function findByDetailId(string $detailId): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
            $this
                ->getQuery()
                ->where( 'currency_ninja_details_id', $detailId)
                ->firstOrFail()
                ->toArray()
        );
    }

    public function getAll(): Collection
    {
        return $this->getQuery()
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                $this->mapper->mapFromArray(
                    CurrencyTypeWithLeague::class,
                    $priceType->toArray()
                );
            });
    }

    /**
     * @return Collection<CurrencyType>
     */
    public function getAllByLeague(int $leagueId): Collection
    {
        return $this->getQuery()
            ->where( 'poe_league_id', $leagueId )
            ->get()
            ->toBase()
            ->map(function ($priceType) {
                $this->mapper->mapFromArray(
                    CurrencyType::class,
                    $priceType->toArray()
                );
            });
    }

    public function find(int $id): CurrencyTypeWithLeague
    {
        return $this->mapper->mapFromArray(
            CurrencyTypeWithLeague::class,
            parent::find($id)->toArray()
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
    public function upsert(array $insertData): void
    {
        $this->getQuery()->upsert(
            $insertData,
            ['currency_trade_id'],
            ['currency_type_name', 'currency_ninja_details_id'],
        );
    }
}
