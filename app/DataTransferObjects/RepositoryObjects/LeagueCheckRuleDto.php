<?php

namespace App\DataTransferObjects\RepositoryObjects;

final readonly class LeagueCheckRuleDto
{
    public function __construct(
        public int $id,
        public int|null $minutes_check,
    ) {}
}
