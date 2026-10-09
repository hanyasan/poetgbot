<?php

namespace Tests\Unit\NinjaService;

use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClient;
use App\Services\PoeNinjaService\PoeNinjaService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class PoeNinjaServiceTest extends TestCase
{
    use CreatesApplication;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->createApplication();
    }

    public function test_get_currency_prices(): void
    {
        $service = app(PoeNinjaServiceContract::class);

        $response = $service->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Standard',
            new NinjaClient(),
        );

        $this->assertNotNull($response);
    }

    public function test_get_available_leagues(): void
    {
        $service = app(PoeNinjaServiceContract::class);

        $response = $service->getAvailableLeagues(
            PoeVersionEnum::POE_2,
            new NinjaClient(),
        );

        $this->assertNotNull($response);
    }
}

