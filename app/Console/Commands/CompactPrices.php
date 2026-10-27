<?php

namespace App\Console\Commands;

use App\Services\DataServices\Currency\CurrencyCompactService\CurrencyCompactServiceContract;
use Illuminate\Console\Command;

/** @psalm-suppress UnusedClass, PropertyNotSetInConstructor */
final class CompactPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:compact-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compact old price checks data';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyCompactServiceContract $service): void
    {
        $this->info('Compacting old price checks');

        $service->compactCoreData();

        $this->info('Old price checks compacted');
    }
}
