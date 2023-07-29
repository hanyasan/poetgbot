<?php

namespace App\Services\DataServices\CurrencyTypeService;

use App\DataTransferObjects\CurrencyRepository\Currency;
use Illuminate\Support\Collection;

interface CurrencyTypeServiceContract
{
    public function create(array $params): Currency;

    public function delete(int $id): bool;

    public function findByDetail(string $detailId): Currency;

    public function getAll(): Collection;

    public function updateOrCreate(array $params): Currency;
}
