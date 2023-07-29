<?php

namespace App\Console\Commands;

use App\Services\DataServices\CurrencyPriceService\CurrencyPriceServiceContract;
use Illuminate\Console\Command;

class CheckCurrencyPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-currency-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check currency price';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(CurrencyPriceServiceContract::class)->updateOrCreateManyByNinja();

        $this->info('The command was successful!');
    }
}
