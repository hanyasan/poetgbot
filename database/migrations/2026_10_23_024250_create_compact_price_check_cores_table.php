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
        Schema::create('compact_price_check_cores', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(\App\Models\PoeLeague::class, 'league_id')->constrained();

            $table->dateTimeTz('date')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compact_price_check_cores');
    }
};
