<?php

namespace App\Repositories\PoeLeague;


use App\DataTransferObjects\RepositoryObjects\League\LeagueCheckRuleDto;
use App\Models\LeagueCheckRule;
use App\Services\Mapper\MapperService;
use CuyZ\Valinor\Mapper\MappingError;

final readonly class LeagueRuleRepository implements LeagueRuleRepositoryContract
{
    public function __construct(
        private LeagueCheckRule $model,
        private MapperService $mapper
    ) {}

    /**
     * @param int|null $minutesToCheck
     * @param int $leagueId
     * @return LeagueCheckRuleDto
     * @throws MappingError
     */
    #[\Override]
    public function updateOrCreate(int|null $minutesToCheck, int $leagueId): LeagueCheckRuleDto
    {
        $rule = $this->model->newQuery()
            ->updateOrCreate(
                ['poe_league_id' => $leagueId],
                [
                    'poe_league_id' => $leagueId,
                    'minutes_check' => $minutesToCheck,
                ]
            );

        return $this->mapper->mapFromArray(LeagueCheckRuleDto::class, $rule->toArray());
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function upsertDefaultForLeagues(array $leaguesId): void
    {
        $defaultRules = array_map(static function (int $id) {
            return [
                'poe_league_id' => $id,
                'minutes_check' => config('price_check.default_hours_check') * 60,
            ];
        }, $leaguesId);

        $this->model->newQuery()->upsert(
            $defaultRules,
            ['poe_league_id'],
            ['minutes_check'],
        );
    }
}
