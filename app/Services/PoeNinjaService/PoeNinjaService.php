<?php

namespace App\Services\PoeNinjaService;

use App\DataTransferObjects\ParsingObjects\Currency\PoeNinjaExchangeResponse;
use App\DataTransferObjects\ParsingObjects\PoeNinjaLeagueResponse;
use App\Enums\PoeVersionEnum;
use App\Services\HTTPClientServices\NinjaClientContract;
use App\Services\Mapper\MapperService;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use GuzzleHttp\Exception\GuzzleException;

final readonly class PoeNinjaService implements PoeNinjaServiceContract
{
    public function __construct(
        private NinjaClientContract $client,
        private MapperService $mapperService,
    ) {
    }

    /**
     * @throws InvalidSource
     * @throws MappingError
     * @throws GuzzleException
     */
    #[\Override]
    public function getCurrencyPrices(
        PoeVersionEnum $version,
        string         $league,
    ): PoeNinjaExchangeResponse {
        $response = $this->client->getCurrencyPrices($version, $league);

        return $this->mapperService
            ->mapFromJson(
                PoeNinjaExchangeResponse::class,
                $response
            );
    }

    /**
     * @throws InvalidSource
     * @throws MappingError
     * @throws GuzzleException
     */
    #[\Override]
    public function getAvailableLeagues(
        PoeVersionEnum $version,
    ): PoeNinjaLeagueResponse {
        $response = $this->client->getAvailableLeagues($version);

        return $this->mapperService
            ->mapFromJson(
                PoeNinjaLeagueResponse::class,
                $response
            );
    }
}
