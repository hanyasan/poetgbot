<?php

namespace Tests\Unit\NinjaService;

use App\Enums\PoeVersionEnum;
use App\Services\PoeNinjaService\PoeNinjaService;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
use Tests\TestCase;

class PoeNinjaServiceTest extends TestCase
{
    public function test_get_currency_prices(): void
    {
        $service = app(PoeNinjaServiceContract::class);

        $response = $service->getCurrencyPrices(
            PoeVersionEnum::POE_2,
            'Standard',
        );

        $this->assertNotNull($response);
    }

    public function test_get_available_leagues(): void
    {
        $service = app(PoeNinjaServiceContract::class);

        $response = $service->getAvailableLeagues(
            PoeVersionEnum::POE_2,
        );

        $this->assertNotNull($response);
    }
}

