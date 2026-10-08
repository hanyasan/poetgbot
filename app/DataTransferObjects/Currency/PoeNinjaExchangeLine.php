<?php

namespace App\DataTransferObjects\Currency;

final class PoeNinjaExchangeLine
{
    public function __construct(
        public readonly string $id,
        public readonly float $primaryValue,
        public readonly float $volumePrimaryValue,
        public readonly string $maxVolumeCurrency,
        public readonly float $maxVolumeRate,
        public readonly PoeNinjaExchangeSparkline $sparkline,
    ) {}
}
