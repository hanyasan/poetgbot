<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaCurrency
{
    public function __construct(
        public string  $currencyTypeName,
        public float   $chaosEquivalent,
        public string  $detailsId,
        /** @var ?PoeNinjaCurrencyPay */
        public ?object $pay = null,
        /** @var ?PoeNinjaCurrencyReceive */
        public ?object $receive = null
    ) {}
}
