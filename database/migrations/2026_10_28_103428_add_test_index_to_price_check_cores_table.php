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
        Schema::table('price_check_cores', function (Blueprint $table) {
            $table->rawIndex("date(created_at AT TIME ZONE 'UTC')", 'updated_at_utc_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_check_cores', function (Blueprint $table) {
            $table->dropIndex('updated_at_utc_date_index');
        });
    }
};
