<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

use Illuminate\Support\Carbon;

class PriceCheckCoreTypeDto
{
    public function __construct(
        public readonly int $id,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        public readonly bool $is_primary,
        public readonly float $rate,
        public readonly string $type_id,
        public readonly string $name,
        public readonly int $price_check_core_id,
    ) {}
}
