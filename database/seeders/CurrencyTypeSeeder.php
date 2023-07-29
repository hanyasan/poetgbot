<?php

namespace Database\Seeders;

use App\Repositories\CurrencyType\CurrencyTypeRepositoryContract;
use Illuminate\Database\Seeder;

class CurrencyTypeSeeder extends Seeder
{
    private array $currencyTypes = [
        [
            'currency_type_name' => 'Mirror of Kalandra',
            'currency_trade_id' => 'mirror',
            'currency_ninja_details_id' => 'mirror-of-kalandra',
        ],
        [
            'currency_type_name' => 'Chaos Orb',
            'currency_trade_id' => 'chaos',
        ],
        [
            'currency_type_name' => 'Divine Orb',
            'currency_trade_id' => 'divine',
            'currency_ninja_details_id' => 'divine-orb',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencyTypeRepository = app(CurrencyTypeRepositoryContract::class);

        foreach ($this->currencyTypes as $currencyType) {
            $currencyTypeRepository->create($currencyType);
        }
    }
}
