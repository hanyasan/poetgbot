<?php

namespace App\DataTransferObjects\ParsingObjects;

final readonly class PoeNinjaLeagueResponse
{
    public function __construct(
        /** @var PoeNinjaLeague[] */
        public array $economyLeagues,
        /** @var PoeNinjaLeague[] */
        public array $oldEconomyLeagues = [],
        /** @var PoeNinjaLeague[] */
        public array $buildLeagues = [],
        /** @var PoeNinjaLeague[] */
        public array $oldBuildLeagues = [],
    ) {}
}
