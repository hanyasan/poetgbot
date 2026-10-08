<?php

namespace App\DataTransferObjects\Repository;

use Illuminate\Support\Carbon;

class PoeLeague
{
    public function __construct(
        public readonly int $id,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        public readonly string $league_name,
        public readonly string $poe_version,
    ) {}
}
