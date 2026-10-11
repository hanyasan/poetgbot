<?php

namespace App\DataTransferObjects\RepositoryObjects;

use Illuminate\Support\Carbon;

final readonly class PoeLeagueDto
{
    public function __construct(
        public int    $id,
        public string $created_at,
        public string $updated_at,
        public string $league_name,
        public string $poe_version,
        public bool   $is_active
    ) {}
}
