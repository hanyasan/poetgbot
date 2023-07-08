<?php

namespace App\DataTransferObjects\Currency;

final class PoeNinjaCurrencyDetail
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $tradeId = null,
        public readonly ?string $icon = null
    ) {}
}
