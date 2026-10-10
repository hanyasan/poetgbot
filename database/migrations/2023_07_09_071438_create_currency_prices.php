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

            $table->float('chaos_equivalent');
            $table->float('sell_price')->nullable();
            $table->float('buy_price')->nullable();

            $table->foreignIdFor(\App\Models\Currency\CurrencyPrice::class, 'currency_type_id');

            $table->dateTime('created_at')->index();
            $table->dateTime('updated_at');
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
