<?php

namespace App\DataTransferObjects;

final class PoeNinjaVersionResponse
{
    public function __construct(
        /** @var PoeNinjaVersion[] */
        public readonly array $economyLeagues,
        /** @var PoeNinjaVersion[] */
        public readonly array $oldEconomyLeagues = [],
        /** @var PoeNinjaVersion[] */
        public readonly array $buildLeagues = [],
        /** @var PoeNinjaVersion[] */
        public readonly array $oldBuildLeagues = [],
    ) {}
}
