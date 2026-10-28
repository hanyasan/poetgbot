<?php

namespace App\Repositories\Currency\Compact;

use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactPriceCheckCoreDto;
use App\Models\Currency\Compact\CompactPriceCheckCore;
use App\Services\Mapper\MapperService;
use Carbon\Carbon;
use CuyZ\Valinor\Mapper\MappingError;

final readonly class CurrencyCompactRepository implements CurrencyCompactRepositoryContract
{
    public function __construct(
        private CompactPriceCheckCore $model,
        private MapperService         $mapper,
    ) {}

    /**
     * @param int $leagueId
     * @param Carbon $date
     * @param array<int, array{
     *     price_avg_rate: float,
     *     currency_type_name: string,
     *     currency_trade_id: string,
     * }> $prices
     * @param array<int, array{
     *     type_name: string,
     *     type_id: string,
     *     avg_type_rate: float,
     * }> $types
     *
     * @return CompactPriceCheckCoreDto
     * @throws MappingError
     */
    #[\Override]
    public function makeCompactCore(
        int $leagueId,
        Carbon $date,
        array $prices,
        array $types,
    ): CompactPriceCheckCoreDto {
        $core = $this->createCore($leagueId, $date);

        $core->types()->createMany($types);
        $core->prices()->createMany($prices);

        $core->loadMissing(['prices', 'types']);

        return $this->mapper->mapFromArray(CompactPriceCheckCoreDto::class, $core->toArray());
    }

    private function createCore(int $leagueId, Carbon $date): CompactPriceCheckCore
    {
        return $this->model->newQuery()->create([
            'league_id' => $leagueId,
            'date' => $date,
        ]);
    }
}
