<?php

namespace App\DataTransferObjects\ParsingObjects;

final class PoeNinjaLeague
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $displayName,
        public readonly bool $hardcore,
        public readonly bool $indexed,
    ) {}
}
