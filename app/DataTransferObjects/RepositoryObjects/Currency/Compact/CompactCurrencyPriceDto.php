<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency\Compact;

final readonly class CompactCurrencyPriceDto
{
    public function __construct(
        public int $id,
        public string $currency_type_name,
        public float $price_avg_rate,
        public string $currency_trade_id,
        public int $core_id,
    ) {
    }
}
