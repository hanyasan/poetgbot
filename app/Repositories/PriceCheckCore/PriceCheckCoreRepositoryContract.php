<?php

namespace App\Repositories\PriceCheckCore;

interface PriceCheckCoreRepositoryContract
{
    public function getAll();

    public function createTypes(array $types, int $coreId);
}
