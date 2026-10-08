<?php

namespace App\Services\DataServices\CurrencyPriceService;

use App\DataTransferObjects\Repository\CurrencyPrice;
use App\DataTransferObjects\Repository\PoeLeague;

interface CurrencyPriceServiceContract
{
    public function create(array $params): CurrencyPrice;

    public function delete(int $id): bool;

    public function findByTypeDetailId(string $detailId): PoeLeague;

    public function getAll(): CurrencyPrice;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyPrice;

    public function truncate(): void;

    public function updateOrCreateManyByNinja(): void;
}
