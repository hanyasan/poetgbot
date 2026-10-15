<?php

namespace App\Services\PriceCheckServices\LeagueCheckPickerService;

use App\DataTransferObjects\RepositoryObjects\League\PoeLeagueDto;
use Illuminate\Support\Collection;

interface LeagueCheckPickerServiceContract
{
    /**
     * @return Collection<int, PoeLeagueDto>
     */
    public function pickLeaguesForCheck(): Collection;
}
