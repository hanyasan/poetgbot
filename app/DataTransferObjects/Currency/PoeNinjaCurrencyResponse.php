<?php

namespace App\DataTransferObjects\Currency;

final class PoeNinjaCurrencyResponse
{
    public function __construct(
        /** @var PoeNinjaCurrency[] */
        public readonly array $lines,
        /** @var PoeNinjaCurrencyDetail[] */
        public readonly array $currencyDetails,
    ) {}
}
