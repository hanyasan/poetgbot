<?php

namespace App\Services\PriceCheckServices\LeagueCheckPickerService;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueWithLastCoreDto;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use App\Services\Mapper\MapperService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class LeagueCheckPickerService implements LeagueCheckPickerServiceContract
{
    public function __construct(
        private PoeLeagueServiceContract  $poeLeagueService,
        private MapperService $mapperService,
    ) {
    }

    /**
     * @return Collection<int, PoeLeagueDto>
     */
    #[\Override]
    public function pickLeaguesForCheck(): Collection
    {
        return $this->poeLeagueService->getActiveWithLatestCore()
            ->filter(function (PoeLeagueWithLastCoreDto $poeLeagueDto) {
                $minutesCheck = $poeLeagueDto->check_rule?->minutes_check;

                // No rule (or a null interval) means the league is not scheduled for checks.
                if ($minutesCheck === null) {
                    return false;
                }

                return is_null($poeLeagueDto->last_core_created_at) || CarbonImmutable::now()->greaterThanOrEqualTo(
                    CarbonImmutable::parse($poeLeagueDto->last_core_created_at)->addMinutes($minutesCheck)
                );
            })
            ->map(fn (PoeLeagueWithLastCoreDto $league) => $this->mapperService->mapFromArray(PoeLeagueDto::class, get_object_vars($league)))
            ;
    }
}
