<?php

use App\Models\Currency\PriceCheckCore;
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
        Schema::create('price_check_core_types', function (Blueprint $table) {
            $table->id();

            $table->boolean('is_primary')->default(false);
            $table->float('rate')->default(1);
            $table->string('type_id', 255);
            $table->string('name', 255);

            $table->foreignIdFor(PriceCheckCore::class, 'price_check_core_id')->constrained()->cascadeOnDelete();

            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_check_core_types');
    }
};
