<?php

namespace App\Services\DataServices\CurrencyPriceService;

use App\DataTransferObjects\Repository\CurrencyPrice;
use App\Repositories\CurrencyPrice\CurrencyPriceRepositoryContract;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Illuminate\Support\Facades\Cache;
use App\DataTransferObjects\Repository\CurrencyPriceWithType;

final class CurrencyPriceService implements CurrencyPriceServiceContract
{

    public function __construct(
        private readonly CurrencyPriceRepositoryContract $repository,
        private readonly CurrencyTypeServiceContract $currencyTypeService,
        private readonly PoeNinjaServiceContract $ninjaService
    ) {}

    public function create(array $params): CurrencyPrice
    {
        Cache::tags([
            'currency_price_get',
        ])->flush();

        return $this->repository->create($params);
    }

    public function delete(int $id): bool
    {
        Cache::tags([
            'currency_price_get',
        ])->flush();

        return $this->repository->delete($id);
    }

    public function getAll(): CurrencyPrice
    {
        return Cache::tags([
            'currency_price',
            'currency_price_get',
        ])->remember(
            'all',
            60 * 60,
            function () {
                return $this->repository->getAll();
            }
        );
    }

    public function updateOrCreate(array $params): CurrencyPrice
    {
        Cache::tags([
            'currency_price_get',
            'currency_price_type:' . $params['currency_type_id'] ?? 'zero',
        ])->flush();

        return $this->repository->updateOrCreate($params);
    }

    public function truncate(): void
    {
        Cache::tags([
            'currency_price',
        ])->flush();

        $this->repository->truncate();
    }

    public function updateOrCreateManyByNinja(): void
    {
        $prices = $this->ninjaService->getCurrencyPrices();

        foreach ($this->currencyTypeService->getAll() as $currencyType) {
            try {
                $price = collect($prices->lines)
                    ->firstWhere('currencyTypeName', $currencyType->currency_type_name);

                $this->repository->updateOrCreate([
                    'currency_type_id' => $currencyType->id,
                    'chaos_equivalent' => $price->chaosEquivalent,
                    'sell_price' => $price->pay?->value ? (1 / $price->pay->value) : null,
                    'buy_price' => $price->receive?->value,
                ]);
            } catch (\Exception) {
                continue;
            }
        }
    }

    public function findByTypeDetailId(string $detailId): CurrencyPriceWithType
    {
        $currencyType = $this->currencyTypeService->findByDetail($detailId);

        return Cache::tags([
            'currency_price',
            'currency_price_type:' . $currencyType->id,
        ])->remember(
            'by_price:' . $currencyType->id,
            60 * 60,
            function () use ($currencyType) {
                return $this->repository->findByCurrencyTypeId(
                    $currencyType->id
                );
            }
        );
    }
}
