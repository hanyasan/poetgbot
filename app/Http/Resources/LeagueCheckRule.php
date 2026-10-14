<?php

namespace App\Http\Resources;

use App\DataTransferObjects\RepositoryObjects\LeagueCheckRuleDto;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueCheckRule extends JsonResource
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
