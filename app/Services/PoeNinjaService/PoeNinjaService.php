<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\Currency\PoeNinjaCurrencyResponse;
use CuyZ\Valinor\MapperBuilder;
use GuzzleHttp\Client;
use CuyZ\Valinor\Mapper\Source\Source;

class PoeNinjaService implements PoeNinjaServiceContract
{

    public function getCurrencyPrices(): PoeNinjaCurrencyResponse
    {
        $client = new Client();
        $response = $client->get( 'https://poe.ninja/api/data/currencyoverview', [
           'query' => [
              'league' => config('poe.current_league'),
              'type' => 'Currency'
           ]
        ])->getBody()->getContents();

        return (new MapperBuilder())
            ->allowSuperfluousKeys()
            ->mapper()
            ->map(
                PoeNinjaCurrencyResponse::class,
                Source::json($response)->camelCaseKeys()
            );
    }
}
