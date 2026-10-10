<?php

namespace App\Services\DataServices\CurrencyPriceService;

use App\DataTransferObjects\RepositoryObjects\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\CurrencyPriceWithTypeDto;

interface CurrencyPriceServiceContract
{
    public function create(array $params): CurrencyPriceDto;

    public function delete(int $id): bool;

    public function findByTypeDetailId(string $detailId): CurrencyPriceWithTypeDto;

    public function getAll(): CurrencyPriceDto;

    public function updateOrCreate(array $paramsToMatch, array $params): CurrencyPriceDto;

    public function truncate(): void;

    public function updateOrCreateManyByNinja(): void;
}
