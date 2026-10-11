<?php

namespace App\DataTransferObjects\ParsingObjects;

final readonly class PoeNinjaLeague
{
    public function __construct(
        public string $name,
        public string $url,
        public string $displayName,
        public bool   $hardcore = false,
        public bool   $indexed = false,
    ) {}
}
