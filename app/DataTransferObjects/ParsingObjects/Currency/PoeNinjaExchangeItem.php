<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeItem
{
    public function __construct(
        public string $id,
        public string $name,
        public string $image,
        public string $category,
        public string $detailsId,
    ) {}
}
