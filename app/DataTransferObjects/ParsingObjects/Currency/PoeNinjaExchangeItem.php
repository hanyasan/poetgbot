<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final class PoeNinjaExchangeItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $image,
        public readonly string $category,
        public readonly string $detailsId,
    ) {}
}
