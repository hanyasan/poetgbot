<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\Repository\CurrencyType;
use Illuminate\Support\Collection;

interface CurrencyTypeServiceContract
{
    public function create(array $params): CurrencyType;

    public function delete(int $id): bool;

    public function findByDetail(string $detailId): CurrencyType;

    public function getAll(): Collection;

    public function updateOrCreate(array $params): CurrencyType;

    public function updateOrCreateManyByNinja(): void;
}
