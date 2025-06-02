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
        Schema::table('panic_reports', function (Blueprint $table) {
            $table->string('emergency_type', 100)->nullable()->after('location_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panic_reports', function (Blueprint $table) {
            $table->dropColumn('emergency_type');
        });
    }
};
