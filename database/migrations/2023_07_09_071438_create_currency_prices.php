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
            $table->unsignedBigInteger('currency_type_id');
            $table->float('chaos_equivalent');
            $table->float('sell_price')->nullable();
            $table->float('buy_price')->nullable();
            $table->timestamps();
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
