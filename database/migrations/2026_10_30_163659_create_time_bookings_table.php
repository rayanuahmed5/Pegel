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
        Schema::create('time_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('consultant_id')->constrained('consultants')->cascadeOnDelete();
            $table->date('date')->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('duration_hours', 4, 2);
            $table->text('description');
            $table->string('accounting_object')->nullable();
            $table->string('activity')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consultant_id', 'date']);
            $table->index(['purchase_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_bookings');
    }
};
