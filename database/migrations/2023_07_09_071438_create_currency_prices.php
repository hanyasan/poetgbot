<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currency_prices', function (Blueprint $table) {
            $table->id();

            $table->unsignedFloat('rate');

            $table->foreignIdFor(\App\Models\Currency\CurrencyPrice::class, 'currency_type_id');
            $table->foreignIdFor(\App\Models\Currency\PriceCheckCore::class, 'price_check_core_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_prices');
    }
};
