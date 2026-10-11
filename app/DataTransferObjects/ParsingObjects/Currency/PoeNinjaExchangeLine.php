<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeLine
{
    public function __construct(
        public string                    $id,
        public float                     $primaryValue,
        public float                     $volumePrimaryValue,
        public string                    $maxVolumeCurrency,
        public float                     $maxVolumeRate,
        public PoeNinjaExchangeSparkline $sparkline,
    ) {}
}
