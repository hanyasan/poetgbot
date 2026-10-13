<?php

namespace App\Services\PriceCheckServices\LeagueCheckPickerService;

use App\DataTransferObjects\RepositoryObjects\PoeLeagueDto;
use Illuminate\Support\Collection;

interface LeagueCheckPickerServiceContract
{
    /**
     * @return Collection<PoeLeagueDto>
     */
    public function pickLeaguesForCheck(): Collection;
}
