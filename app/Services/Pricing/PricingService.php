<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\ServiceObjects\LeagueLatestPricesDto;
use App\Services\DataServices\Currency\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\DataServices\Currency\PriceCheckCoreService\PriceCheckCoreServiceContract;
use App\Services\DataServices\PoeLeague\PoeLeagueServiceContract;

final readonly class PricingService implements PricingServiceContract
{
    public function __construct(
        private PoeLeagueServiceContract $poeLeagueService,
        private CurrencyTypeServiceContract $currencyTypeService,
        private PriceCheckCoreServiceContract $priceCheckCoreService,
    ){}

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getLeagueLatestPrices(int $leagueId): LeagueLatestPricesDto
    {
        $this->poeLeagueService->find($leagueId);

        $core = $this->priceCheckCoreService->getLatestByLeague($leagueId);

        $prices = $this->currencyTypeService->getAllByLeagueWithPriceByCore($leagueId, $core->id);

        return new LeagueLatestPricesDto(
            $core,
            $prices->filter(static function (CurrencyTypeWithPricesDto $price) {
                return ! empty($price->prices);
            })->all(),
        );
    }
}
