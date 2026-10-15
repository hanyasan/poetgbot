<?php

namespace App\DataTransferObjects\RepositoryObjects\League;

final readonly class PoeLeagueWithLastCoreDto
{
    public function __construct(
        public int    $id,
        public string $created_at,
        public string $updated_at,
        public string $league_name,
        public string $poe_version,
        public bool   $is_active,
        /** @var null|LeagueCheckRuleDto $check_rule */
        public null|object $check_rule = null,
        public string $last_core_created_at,
        public int $last_core_id,
    ) {}
}
