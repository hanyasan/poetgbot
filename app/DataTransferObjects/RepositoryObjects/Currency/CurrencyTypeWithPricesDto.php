<?php

namespace App\DataTransferObjects\RepositoryObjects\Currency;

final readonly class CurrencyTypeWithPricesDto
{
    public function __construct(
        public int     $id,
        public string  $currency_type_name,
        public string  $created_at,
        public string  $updated_at,
        /** @var CurrencyPriceDto[] $prices */
        public array  $prices,
        public ?string $currency_trade_id = null,
        public ?string $currency_ninja_details_id = null,
    ){}
}
