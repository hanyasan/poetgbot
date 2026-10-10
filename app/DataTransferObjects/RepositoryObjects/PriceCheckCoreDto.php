<?php

namespace App\DataTransferObjects\RepositoryObjects;

use Illuminate\Support\Carbon;

class PriceCheckCoreDto
{
    public function __construct(
        public readonly int $id,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        /** @var PriceCheckCoreTypeDto[] $types */
        public readonly object $types,
    ) {}
}
