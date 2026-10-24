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
        Schema::create('compact_price_check_core_types', function (Blueprint $table) {
            $table->id();

            $table->string('type_name');
            $table->float('avg_type_rate');
            $table->string('type_id');

            $table->foreignIdFor(CompactPriceCheckCore::class, 'core_id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compact_price_check_core_types');
    }
};
