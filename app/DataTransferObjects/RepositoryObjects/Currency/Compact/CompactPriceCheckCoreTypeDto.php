<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency\Compact;

final readonly class CompactPriceCheckCoreTypeDto
{
    public function __construct(
        public int $id,
        public string $type_name,
        public float $avg_type_rate,
        public string $type_id,
        public int $core_id,
    ) {
    }
}
