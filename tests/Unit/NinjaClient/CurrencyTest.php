<?php

namespace Tests\Unit\NinjaClient;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClient;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class CurrencyTest extends TestCase
{
    use CreatesApplication;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->createApplication();
    }

    /**
     * A basic test example.
     */
    public function test_has_response(): void
    {
        $client = new NinjaClient();

        $response = $client->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Forbidden Rites',
        );

        $this->assertIsString($response);
    }
}

