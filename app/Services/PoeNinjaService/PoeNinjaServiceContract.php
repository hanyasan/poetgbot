<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\ParsingObjects\PoeNinjaLeagueResponse;
use App\Enums\PoeVersionEnum;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Object\Exception\InvalidSource;
use GuzzleHttp\Exception\GuzzleException;

interface PoeNinjaServiceContract
{
    /**
     * @throws InvalidSource
     * @throws MappingError
     * @throws GuzzleException
     */
    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string         $league,
    ): PoeNinjaExchangeResponse;

    /**
     * @throws InvalidSource
     * @throws MappingError
     * @throws GuzzleException
     */
    public function getAvailableLeagues(
        PoeVersionEnum $version,
    ): PoeNinjaLeagueResponse;
}
