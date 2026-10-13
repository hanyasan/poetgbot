<?php

namespace App\DataTransferObjects\ParsingObjects\Currency;

final readonly class PoeNinjaExchangeLine
{
    public function __construct(
        public string                     $id,
        public ?float                     $primaryValue = null,
        public ?float                     $volumePrimaryValue = null,
        public ?string                    $maxVolumeCurrency = null,
        public ?float                     $maxVolumeRate = null,
        public ?PoeNinjaExchangeSparkline $sparkline = null,
    ) {}
}
