<?php

use App\Models\Currency\Compact\CompactPriceCheckCore;
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
        Schema::create('compact_currency_prices', function (Blueprint $table) {
            $table->id();

            $table->float('price_avg_rate');
            $table->string('currency_type_name');
            $table->string('currency_trade_id');

            $table->foreignIdFor(CompactPriceCheckCore::class, 'core_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compact_currency_prices');
    }
};
