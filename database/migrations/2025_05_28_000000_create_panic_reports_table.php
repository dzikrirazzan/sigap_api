<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panic_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->double('latitude');
            $table->double('longitude');
            $table->text('location_description')->nullable();
            $table->enum('status', ['pending', 'handling', 'resolved', 'cancelled'])->default('pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panic_reports');
    }
};