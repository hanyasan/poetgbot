<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaExchangeResponse;
use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Mapper\Source\Source;

class PoeNinjaService implements PoeNinjaServiceContract
{

    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string $league,
        NinjaClientContract $client,
    ): PoeNinjaExchangeResponse {
        $response = $client->getCurrencyPrices($version, $league);

        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(
                PoeNinjaExchangeResponse::class,
                Source::json($response)->camelCaseKeys()
            );
    }
}
