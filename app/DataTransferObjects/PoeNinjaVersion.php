<?php

namespace App\DataTransferObjects;

final class PoeNinjaVersion
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $displayName,
        public readonly bool $hardcore,
        public readonly bool $indexed,
    ) {}
}
