<?php

namespace App\Console\Commands;

use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use Illuminate\Console\Command;

class ImportCurrencyTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-currency-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /** @var CurrencyTypeServiceContract $service */
        $service = app(CurrencyTypeServiceContract::class);

        $service->freshAllByNinja();

        $this->info('The command was successful!');
    }
}
