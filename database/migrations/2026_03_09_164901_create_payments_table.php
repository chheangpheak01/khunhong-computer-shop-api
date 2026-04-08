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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method'); 
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('paid');
            $table->string('reference_no')->nullable();
            $table->timestamp('payment_date')->nullable(); 
            $table->boolean('is_voided')->default(false);
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->index('method');
            $table->index('status');
            $table->index('is_voided');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
