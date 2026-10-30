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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('cost_centre_id')->constrained('cost_centres')->cascadeOnDelete();
            $table->foreignId('project_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('ordered_budget_pd', 8, 2);
            $table->decimal('hourly_rate_eur', 10, 2)->default(120.00);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
