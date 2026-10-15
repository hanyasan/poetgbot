<?php

namespace App\DataTransferObjects\ServiceObjects;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;

final readonly class LeaguePricesDto
{
    public function __construct(
        public PoeLeagueDto $poeLeague,
        public PriceCheckCoreDto $core,
        /** @var CurrencyTypeWithPricesDto[] $currencies */
        public array $currencies,
    ){}
}
