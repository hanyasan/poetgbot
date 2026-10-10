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
        Schema::create('currency_types', function (Blueprint $table) {
            $table->id();

            $table->string('currency_type_name');
            $table->string('currency_trade_id');
            $table->string('currency_ninja_details_id');

            $table->foreignIdFor(\App\Models\PoeLeague::class, 'poe_league_id')->constrained();

            $table->unique(['currency_trade_id', 'poe_league_id']);

            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_types');
    }
};
