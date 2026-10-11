<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

final readonly class PriceCheckCoreDto
{
    public function __construct(
        public int    $id,
        public string $created_at,
        public string $updated_at,
        /** @var PriceCheckCoreTypeDto[] $price_check_core_types */
        public array  $price_check_core_types,
    ) {}
}
