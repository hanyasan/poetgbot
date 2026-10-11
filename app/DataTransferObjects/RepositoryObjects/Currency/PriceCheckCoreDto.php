<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

class PriceCheckCoreDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $created_at,
        public readonly string $updated_at,
        /** @var PriceCheckCoreTypeDto[] $price_check_core_types */
        public readonly array $price_check_core_types,
    ) {}
}
