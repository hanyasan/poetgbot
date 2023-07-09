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
            $table->string('currency_type_name')->unique();
            $table->string('currency_trade_id')->nullable();
            $table->string('currency_ninja_details_id')->nullable();
            $table->timestamps();
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
