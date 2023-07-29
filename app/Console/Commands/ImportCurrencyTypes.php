<?php

namespace App\Console\Commands;

use App\Services\DataServices\CurrencyTypeService\CurrencyTypeServiceContract;
use App\Services\PoeNinjaService\PoeNinjaServiceContract;
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
        $currencyPrices = app(PoeNinjaServiceContract::class)->getCurrencyPrices();
        $currencyPricesDetail = collect($currencyPrices->currencyDetails);

        collect($currencyPrices->lines)
            ->unique('currencyTypeName')
            ->each(
                function ($priceType) use ($currencyPricesDetail) {
                    app(CurrencyTypeServiceContract::class)->updateOrCreate([
                        'currency_type_name' => $priceType->currencyTypeName,
                        'currency_trade_id'=> $currencyPricesDetail->where(
                            'name',
                            $priceType->currencyTypeName
                        )->first()->tradeId,
                        'currency_ninja_details_id'=> $priceType->detailsId,
                    ]);
                }
            )
        ;
    }
}
