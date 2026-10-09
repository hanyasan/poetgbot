<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\RepositoryObjects\CurrencyTypeWithLeague;
use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CurrencyTypeService implements CurrencyTypeServiceContract
{
    public function __construct(
        private readonly CurrencyTypeRepositoryContract $repository,
        private readonly PoeNinjaServiceContract $ninjaService
    ) {}

    public function create(array $params): CurrencyTypeWithLeague
    {
        Cache::tags([
            'currency_type_detail:' . $params['currency_ninja_details_id'],
            'currency_type_get',
        ])->flush();

        return $this->repository->create($params);
    }

    public function delete(int $id): bool
    {
        Cache::tags([
            'currency_type_id:' . $id,
            'currency_type_get',
        ])->flush();

        return $this->repository->delete($id);
    }

    public function findByDetail(string $detailId): CurrencyTypeWithLeague
    {
        return Cache::tags([
            'currency_type',
            'currency_type_detail:' . $detailId
        ])->remember(
            'by_detail:' . $detailId,
            60 * 60,
            function () use ($detailId) {
                return $this->repository->findByDetailId($detailId);
            }
        );
    }

    public function getAll(): Collection
    {
        return Cache::tags([
            'currency_type',
            'currency_type_get',
        ])->remember(
            'all',
            60 * 60,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    /**
     * @param int|array{
     *     poe_version: string,
     *     league_name: string,
     * } $league
     *
     * @return Collection<CurrencyTypeWithLeague>
     */
    public function getAllByLeague(int|array $league): Collection
    {
        //
    }

    /**
     * @param int|array{
     *     poe_version: string,
     *     league_name: string,
     * } $league
     */
    public function upsertByNinja(int|array $league): void
    {
        //
    }
}
