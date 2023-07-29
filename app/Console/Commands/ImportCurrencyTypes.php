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
        app(CurrencyTypeServiceContract::class)->updateOrCreateManyByNinja();

        $this->info('The command was successful!');
    }
}
