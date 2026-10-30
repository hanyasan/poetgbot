<?php

namespace Tests\Unit\Service\Pricing;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyPriceDto;
use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreTypeDto;
use App\Utility\PricingFormatter;
use Carbon\Carbon;
use Tests\TestCase;

class PricingFormatterServiceTest extends TestCase
{
    /**
     * @return array<int, PriceCheckCoreTypeDto>
     */
    private function coreTypes(): array
    {
        return [
            new PriceCheckCoreTypeDto(
                1,
                Carbon::now()->toDateString(),
                Carbon::now()->toDateString(),
                true,
                1,
                'chaos-orb',
                'Chaos',
                1,
            ),
            new PriceCheckCoreTypeDto(
                2,
                Carbon::now()->toDateString(),
                Carbon::now()->toDateString(),
                false,
                0.002,
                'divine-orb',
                'Divine',
                1,
            ),
        ];
    }

    /**
     * @return array<int, CurrencyTypeWithPricesDto>
     */
    private function currencyTypes(): array
    {
        return [
            new CurrencyTypeWithPricesDto(
                1,
                'Chaos',
                Carbon::now()->toDateString(),
                Carbon::now()->toDateString(),
                [
                    new CurrencyPriceDto(
                        1,
                        1,
                    ),
                ],
                'chaos-orb',
                'chaos-orb',
            ),
            new CurrencyTypeWithPricesDto(
                2,
                'Divine',
                Carbon::now()->toDateString(),
                Carbon::now()->toDateString(),
                [
                    new CurrencyPriceDto(
                        2,
                        500,
                    ),
                ],
                'divine-orb',
                'divine-orb',
            ),
        ];
    }

    public function test_format_price(): void
    {
        $data = PricingFormatter::formatPrice(
            $this->currencyTypes()[0],
            $this->coreTypes(),
        );

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_format_prices(): void
    {
        $data = PricingFormatter::formatPrices(
            $this->coreTypes(),
            $this->currencyTypes(),
        );

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }
}
