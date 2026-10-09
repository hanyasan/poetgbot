<?php

namespace Tests\Unit\NinjaClient;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClient;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class NinjaClientTest extends TestCase
{
    use CreatesApplication;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->createApplication();
    }

    public function test_get_currency_prices(): void
    {
        $client = new NinjaClient();

        $response = $client->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Forbidden Rites',
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

