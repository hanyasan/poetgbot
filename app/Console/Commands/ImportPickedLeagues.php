<?php

namespace App\Console\Commands;

use App\Services\PriceCheck\PriceCheckServiceContract;
use Illuminate\Console\Command;

/** @psalm-suppress UnusedClass, PropertyNotSetInConstructor */
final class ImportPickedLeagues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-picked';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import prices for all picked leagues';

    /**
     * Execute the console command.
     */
    public function handle(PriceCheckServiceContract $service): void
    {
        $this->info('Starting import prices for leagues');

        $service->importPickedLeaguesPrices();

        $this->info('Prices for leagues imported');
    }
}
