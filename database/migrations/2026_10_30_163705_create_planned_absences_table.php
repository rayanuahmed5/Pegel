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
        Schema::create('planned_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->string('type', 30)->default('vacation');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['consultant_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planned_absences');
    }
};
