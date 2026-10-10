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
        Schema::create('poe_leagues', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('league_name');
            $table->string('poe_version');
            $table->boolean('is_active')->default(false);

            $table->unique(['league_name', 'poe_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poe_leagues');
    }
};
