<?php

namespace App\Console\Commands;

use App\Models\Currency\CurrencyPrice;
use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
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
        $prices = app(PoeNinjaServiceContract::class)->getCurrencyPrices();

        foreach (app(CurrencyTypeServiceContract::class)->getAll() as $currencyType) {
            try {
                $price = collect($prices->lines)
                    ->firstWhere('currencyTypeName', $currencyType->currency_type_name);

                CurrencyPrice::updateOrCreate(
                    ['currency_type_id' => $currencyType->id],
                    [
                        'chaos_equivalent' => $price->chaosEquivalent,
                        'sell_price' => $price->pay?->value ? (1 / $price->pay->value) : null,
                        'buy_price' => $price->receive?->value,
                    ]
                );
            } catch (\Exception) {
                continue;
            }
        }

        $this->info('The command was successful!');
    }
}
