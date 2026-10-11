<?php

namespace App\DataTransferObjects\RepositoryObjects;

use Illuminate\Support\Carbon;

class PoeLeagueDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly string $league_name,
        public readonly string $poe_version,
        public readonly bool $is_active
    ) {}
}
