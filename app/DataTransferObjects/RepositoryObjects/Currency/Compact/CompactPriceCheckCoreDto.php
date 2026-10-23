<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency\Compact;

final readonly class CompactPriceCheckCoreDto
{
    public function __construct(
        public int $id,
        public string $date,
        public int $league_id,
        /** @var CompactPriceCheckCoreTypeDto[] $types */
        public array $types,
        /** @var CompactCurrencyPriceDto[] $prices */
        public array $prices,
    ) {
    }
}
