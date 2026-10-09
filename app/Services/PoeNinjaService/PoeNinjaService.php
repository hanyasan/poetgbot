<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\PoeNinjaVersionResponse;
use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Mapper\Source\Source;

class PoeNinjaService implements PoeNinjaServiceContract
{
    public function __construct(
        private readonly NinjaClientContract $client,
    ) {
    }

    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string $league,
    ): PoeNinjaExchangeResponse {
        $response = $this->client->getCurrencyPrices($version, $league);

        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(
                PoeNinjaExchangeResponse::class,
                Source::json($response)->camelCaseKeys()
            );
    }

    public function getAvailableLeagues(
        PoeVersionEnum $version,
    ): PoeNinjaVersionResponse {
        $response = $this->client->getAvailableLeagues($version);

        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(
                PoeNinjaVersionResponse::class,
                Source::json($response)->camelCaseKeys()
            );
    }
}
