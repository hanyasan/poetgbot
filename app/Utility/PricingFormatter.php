<?php

namespace App\Utility;

use App\DataTransferObjects\RepositoryObjects\Currency\CurrencyTypeWithPricesDto;
use App\DataTransferObjects\RepositoryObjects\Currency\PriceCheckCoreTypeDto;

final readonly class PricingFormatter
{
    /**
     * @param array<int, PriceCheckCoreTypeDto> $coreTypes
     * @param array<int, CurrencyTypeWithPricesDto> $currencyTypesWithPrices
     *
     * @return array<int, array{
     *     currency_type_name: string,
     *     currency_trade_id: string|null,
     *     currency_ninja_details_id: string|null,
     *     prices: array<int, array{
     *         is_primary: bool,
     *         currency_name: string,
     *         currency_trade_id: string,
     *         integral_value: int,
     *         value: float,
     *     }>
     * }>
     */
    public static function formatPrices(array $coreTypes, array $currencyTypesWithPrices): array
    {
        $currencyTypes = [];

        foreach ($currencyTypesWithPrices as $currencyType) {
            if (is_null($currencyType->currency_trade_id)) continue;

            $currencyTypes[] = self::formatPrice($currencyType, $coreTypes);
        }

        return $currencyTypes;
    }

    /**
     * @param CurrencyTypeWithPricesDto $currencyType
     * @param array<int, PriceCheckCoreTypeDto> $coreTypes
     *
     * @return array{
     *     currency_type_name: string,
     *     currency_trade_id: string|null,
     *     currency_ninja_details_id: string|null,
     *     prices: array<int, array{
     *         is_primary: bool,
     *         currency_name: string,
     *         currency_trade_id: string,
     *         integral_value: int,
     *         value: float,
     *     }>
     * }
     */
    public static function formatPrice(CurrencyTypeWithPricesDto $currencyType, array $coreTypes): array
    {
        $prices = [];

        foreach ($coreTypes as $coreType) {
            if (empty($currencyType->prices)) break;

            $value = bcmul(self::formatToStr($coreType->rate), self::formatToStr($currencyType->prices[0]->rate), 10);

            $prices[] = [
                'is_primary' => $coreType->is_primary,
                'currency_name' => $coreType->name,
                'currency_trade_id' => $coreType->type_id,
                'integral_value' => (int) bcceil($value),
                'value' => (float) bcround($value, 10),
            ];
        }

        return [
            'currency_type_name' => $currencyType->currency_type_name,
            'currency_trade_id' => $currencyType->currency_trade_id,
            'currency_ninja_details_id' => $currencyType->currency_ninja_details_id,
            'prices' => $prices,
        ];
    }

    private static function formatToStr(int|float $num): string
    {
        return number_format($num, 10, '.', '');
    }
}
