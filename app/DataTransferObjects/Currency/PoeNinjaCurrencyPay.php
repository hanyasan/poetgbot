<?php

namespace App\DataTransferObjects\Currency;

class PoeNinjaCurrencyPay
{
    public function __construct(
        public readonly int $id,
        public readonly int $getCurrencyId,
        public readonly string $sampleTimeUtc,
        public readonly int $count,
        public readonly float $value,
        public readonly int $dataPointCount,
        public readonly bool $includesSecondary,
        public readonly int $listingCount,
        public readonly int $leagueId,
        public readonly int $payCurrencyId,
    ) {}
}
