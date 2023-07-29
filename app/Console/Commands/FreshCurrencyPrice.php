<?php

namespace App\Console\Commands;

use App\Services\DataServices\CurrencyPriceService\CurrencyPriceServiceContract;
use Illuminate\Console\Command;

class FreshCurrencyPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fresh-currency-price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge old price checking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(CurrencyPriceServiceContract::class)->truncate();

        $this->call('app:check-currency-price');
    }
}
