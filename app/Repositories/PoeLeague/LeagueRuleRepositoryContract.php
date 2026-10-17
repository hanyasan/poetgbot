<?php

namespace App\Repositories\PoeLeague;

use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;

interface LeagueRuleRepositoryContract
{
    /**
     * @param int|null $minutesToCheck
     * @param int $leagueId
     * @return LeagueCheckRuleDto
     */
    public function updateOrCreate(int|null $minutesToCheck, int $leagueId): LeagueCheckRuleDto;

    /**
     * @param array<int, int> $leaguesId
     */
    public function upsertDefaultForLeagues(array $leaguesId): void;
}
