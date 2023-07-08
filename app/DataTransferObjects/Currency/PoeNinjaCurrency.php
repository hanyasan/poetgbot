<?php

namespace App\DataTransferObjects\Currency;

final class PoeNinjaCurrency
{
    public function __construct(
        public readonly string $currencyTypeName,
        public readonly float $chaosEquivalent,
        public readonly string $detailsId,
        /** @var ?PoeNinjaCurrencyPay */
        public readonly ?object $pay = null,
        /** @var ?PoeNinjaCurrencyReceive */
        public readonly ?object $receive = null
    ) {}
}
