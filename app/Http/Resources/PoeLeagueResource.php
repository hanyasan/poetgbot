<?php

namespace App\Http\Resources;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class PoeLeagueResource extends JsonResource
{
    #[\Override]
    public function toArray($request): array
    {
        /** @var PoeLeagueDto $league */
        $league = $this->resource;

        return [
            'id' => $league->id,
            'created_at' => $league->created_at,
            'updated_at' => $league->updated_at,
            'league_name' => $league->league_name,
            'poe_version' => $league->poe_version,
            'is_active' => $league->is_active,
            'check_rule' => LeagueCheckRule::make($league->check_rule),
        ];
    }
}
