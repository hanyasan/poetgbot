<?php

namespace App\Console\Commands;

use App\Services\PriceCheckServices\PriceCheckService\PriceCheckServiceContract;
use Illuminate\Console\Command;

class ImportPickedLeagues extends Command
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
    public function handle()
    {
        /** @var PriceCheckServiceContract $service */
        $service = app(PriceCheckServiceContract::class);

        $service->importPickedLeaguesPrices();

        $this->info('The command was successful!');
    }
}
