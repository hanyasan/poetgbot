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
        Schema::create('price_check_cores', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(\App\Models\PoeLeague::class, 'poe_league_id')->constrained();

            $table->dateTime('created_at')->index();
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_check_cores');
    }
};
