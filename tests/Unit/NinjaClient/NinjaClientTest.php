<?php

namespace Tests\Unit\NinjaClient;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClient;
use Tests\TestCase;

class NinjaClientTest extends TestCase
{
    public function test_get_currency_prices(): void
    {
        $client = new NinjaClient();

        $response = $client->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Standard',
        );

        $this->assertIsString($response);
    }

    public function test_available_leagues(): void
    {
        $client = new NinjaClient();

        $response = $client->getAvailableLeagues(
            PoeVersionEnum::POE_2,
        );

        $this->assertIsString($response);
    }
}

