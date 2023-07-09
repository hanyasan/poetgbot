<?php

namespace App\Console\Commands;

use App\Models\Currency\CurrencyPrice;
use App\Models\Currency\CurrencyType;
use Illuminate\Console\Command;

class PurgeCurrencyPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:purge-currency-price';

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
        foreach (CurrencyType::all() as $currencyType) {
            CurrencyPrice::where('currency_type_id', $currencyType->id)
                ->orderBy('created_at', 'desc')
                ->offset(1)
                ->get()
                ->each(function ($row) {
                    $row->delete();
                });
        }
    }
}
