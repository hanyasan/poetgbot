<?php

namespace App\Console\Commands;

use App\Models\Currency\CurrencyPrice;
use App\Models\Currency\CurrencyType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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
        CurrencyPrice::truncate();

        $this->call('app:check-currency-price');
    }
}
