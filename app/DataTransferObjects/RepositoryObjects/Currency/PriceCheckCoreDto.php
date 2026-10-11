<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

use Illuminate\Support\Carbon;

class PriceCheckCoreDto
{
    public function __construct(
        public readonly int $id,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var PriceCheckCoreTypeDto[] $price_check_core_types */
        public readonly array $price_check_core_types,
    ) {}
}
