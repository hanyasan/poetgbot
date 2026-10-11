<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaCurrencyDetail
{
    public function __construct(
        public int     $id,
        public string  $name,
        public ?string $tradeId = null,
        public ?string $icon = null
    ) {}
}
