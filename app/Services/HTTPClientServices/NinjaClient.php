<?php

namespace App\Services\HTTPClientServices;

use App\Enums\PoeVersionEnum;
use GuzzleHttp\Client;

class NinjaClient implements NinjaClientContract
{
    private Client $client;

    public function __construct(
    ) {
        $this->client = new Client([
            'base_uri' => config('poe.ninja.base_url'),
          ]);
    }

    public function getCurrencyPrices(PoeVersionEnum $version, string $league): string
    {
        $response = $this->client->get( $this->setVersionForUrl($version) . '/api/economy/exchange/current/overview', [
           'query' => [
              'league' => $league,
              'type' => 'Currency'
           ]
        ]);

        return $response->getBody()->getContents();
    }


    public function getAvailableLeagues(PoeVersionEnum $version): string
    {
        $response = $this->client->get( $this->setVersionForUrl($version) . '/api/data/index-state');

        return $response->getBody()->getContents();
    }

    private function setVersionForUrl(PoeVersionEnum $version): string
    {
        return $version->value;
    }
}
