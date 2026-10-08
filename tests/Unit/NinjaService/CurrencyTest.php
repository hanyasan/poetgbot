<?php

namespace Tests\Unit\NinjaService;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClient;
use App\Services\PoeNinjaService\PoeNinjaService;
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
        $service = new PoeNinjaService();

        $response = $service->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Forbidden Rites',
            new NinjaClient(),
        );

        $this->assertNotNull($response);
    }
}

