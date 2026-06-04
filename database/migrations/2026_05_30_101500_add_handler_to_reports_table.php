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
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('handled_by')->nullable()->after('admin_notes')->constrained('users')->onDelete('set null');
            $table->timestamp('handled_at')->nullable()->after('handled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'handled_by')) {
                $table->dropForeign(['handled_by']);
                $table->dropColumn(['handled_by']);
            }
            if (Schema::hasColumn('reports', 'handled_at')) {
                $table->dropColumn(['handled_at']);
            }
        });
    }
};
