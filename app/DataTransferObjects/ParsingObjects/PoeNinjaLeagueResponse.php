<?php

namespace App\DataTransferObjects\ParsingObjects;

final class PoeNinjaLeagueResponse
{
    public function __construct(
        /** @var PoeNinjaLeague[] */
        public readonly array $economyLeagues,
        /** @var PoeNinjaLeague[] */
        public readonly array $oldEconomyLeagues = [],
        /** @var PoeNinjaLeague[] */
        public readonly array $buildLeagues = [],
        /** @var PoeNinjaLeague[] */
        public readonly array $oldBuildLeagues = [],
    ) {}
}
