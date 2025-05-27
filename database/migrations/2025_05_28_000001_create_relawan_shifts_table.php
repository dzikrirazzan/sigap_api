<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relawan_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('relawan_id')->constrained('users')->onDelete('cascade');
            $table->date('shift_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relawan_shifts');
    }
};