<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final class PoeNinjaExchangeResponse
{
    public function __construct(
        public readonly PoeNinjaExchangeCore $core,
        /** @var PoeNinjaExchangeLine[] */
        public readonly array $lines,
        /** @var PoeNinjaExchangeItem[] */
        public readonly array $items,
    ) {}
}
