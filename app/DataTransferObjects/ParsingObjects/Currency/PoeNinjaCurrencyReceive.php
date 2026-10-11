<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaCurrencyReceive
{
    public function __construct(
        public int    $id,
        public int    $getCurrencyId,
        public string $sampleTimeUtc,
        public int    $count,
        public float  $value,
        public int    $dataPointCount,
        public bool   $includesSecondary,
        public int    $listingCount,
        public int    $leagueId,
        public int    $payCurrencyId,
    ) {}
}
