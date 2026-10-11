<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeResponse
{
    public function __construct(
        public PoeNinjaExchangeCore $core,
        /** @var PoeNinjaExchangeLine[] */
        public array                $lines,
        /** @var PoeNinjaExchangeItem[] */
        public array                $items,
    ) {}
}
