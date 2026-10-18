<?php

namespace App\DataTransferObjects\ServiceObjects;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;

final readonly class LeagueLatestPricesDto
{
    public function __construct(
        /** @var PriceCheckCoreDto $core */
        public object $core,
        /** @var CurrencyTypeWithPricesDto[] $prices */
        public array $prices,
    ){}
}
