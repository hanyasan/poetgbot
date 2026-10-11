<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

final readonly class PriceCheckCoreTypeDto
{
    public function __construct(
        public int    $id,
        public string $created_at,
        public string $updated_at,
        public bool   $is_primary,
        public float  $rate,
        public string $type_id,
        public string $name,
        public int    $price_check_core_id,
    ) {}
}
