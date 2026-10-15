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
     * @return Collection<int, PoeLeagueWithLastCoreDto>
     */
    #[\Override]
    public function pickLeaguesForCheck(): Collection
    {
        return $this->poeLeagueService->getActiveWithLatestCore()
            ->filter(function (PoeLeagueWithLastCoreDto $poeLeagueDto) {
                return CarbonImmutable::now()->lessThanOrEqualTo(
                    CarbonImmutable::parse($poeLeagueDto->last_core_created_at)->addMinutes($poeLeagueDto->check_rule->minutes_check)
                );
            })
            ->map(fn ($league) => $this->mapperService->mapFromArray(PoeLeagueDto::class, $league->toArray()))
            ;
    }
}
