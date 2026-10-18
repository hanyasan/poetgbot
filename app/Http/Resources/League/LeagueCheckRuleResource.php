<?php

namespace App\Http\Resources\League;

use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;
use Illuminate\Http\Resources\Json\JsonResource;

final class LeagueCheckRuleResource extends JsonResource
{
    #[\Override]
    public function toArray($request): array
    {
        /** @var LeagueCheckRuleDto $rule */
        $rule = $this->resource;

        return [
            'id' => $rule->id,
            'created_at' => $rule->created_at,
            'updated_at' => $rule->updated_at,
            'minutes_check' => $rule->minutes_check,
        ];
    }
}
