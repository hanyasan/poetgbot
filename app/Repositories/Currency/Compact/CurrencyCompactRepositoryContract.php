<?php

namespace App\Repositories\Currency\Compact;

use App\DataTransferObjects\RepositoryObjects\Currency\Compact\CompactPriceCheckCoreDto;
use Carbon\Carbon;

interface CurrencyCompactRepositoryContract
{
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
     */
    public function makeCompactCore(
        int $leagueId,
        Carbon $date,
        array $prices,
        array $types,
    ): CompactPriceCheckCoreDto;
}
