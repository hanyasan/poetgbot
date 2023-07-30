<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\Repository\CurrencyType;
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

    public function create(array $params): CurrencyType
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

    public function findByDetail(string $detailId): CurrencyType
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

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyType
    {
        Cache::tags([
            'currency_type_detail:' . ($params['currency_ninja_details_id'] ?? 'zero'),
            'currency_type_id:' . ($params['id'] ?? 'zero'),
            'currency_type_get',
        ])->flush();

        return $this->repository->updateOrCreate($paramsToMatch, $params);
    }

    public function updateOrCreateManyByNinja(): void
    {
        $currencyPrices = $this->ninjaService->getCurrencyPrices();
        $currencyPricesDetail = collect($currencyPrices->currencyDetails);

        collect($currencyPrices->lines)
            ->unique('currencyTypeName')
            ->each(
                function ($priceType) use ($currencyPricesDetail) {
                    $this->updateOrCreate(
                        [
                            'currency_type_name' => $priceType->currencyTypeName,
                        ],
                        [
                        'currency_type_name' => $priceType->currencyTypeName,
                        'currency_trade_id'=> $currencyPricesDetail->where(
                            'name',
                            $priceType->currencyTypeName
                        )->first()->tradeId,
                        'currency_ninja_details_id'=> $priceType->detailsId,
                    ]
                    );
                }
            )
        ;
    }
}
