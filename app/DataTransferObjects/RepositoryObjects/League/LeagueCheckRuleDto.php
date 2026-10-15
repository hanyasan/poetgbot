<?php

namespace App\DataTransferObjects\RepositoryObjects\League;

final readonly class LeagueCheckRuleDto
{
    public function __construct(
        public int $id,
        public int|null $minutes_check,
        public string $created_at,
        public string $updated_at,
    ) {}
}
