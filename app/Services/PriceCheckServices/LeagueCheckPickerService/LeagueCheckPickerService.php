<?php

namespace App\Services\PriceCheckServices\LeagueCheckPickerService;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use App\Services\DataServices\PoeLeagueService\PoeLeagueServiceContract;
use Illuminate\Support\Collection;

final readonly class LeagueCheckPickerService implements LeagueCheckPickerServiceContract
{
    public function __construct(
        private PoeLeagueServiceContract  $poeLeagueService,
    ) {
    }

    /**
     * @return Collection<PoeLeagueDto>
     */
    public function pickLeaguesForCheck(): Collection
    {
        return $this->poeLeagueService->getAllActive();
    }
}
