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
        Schema::create('receipts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->string('receipt_no')->unique(); 
        $table->string('invoice_no'); 
        $table->string('customer_name')->nullable();
        $table->string('customer_email')->nullable();
        $table->string('customer_phone')->nullable();
        $table->decimal('subtotal', 12, 2);              
        $table->decimal('tax_rate', 5, 2)->default(10.00); 
        $table->decimal('tax_amount', 12, 2)->default(0);   
        $table->decimal('discount_amount', 12, 2)->default(0); 
        $table->decimal('grand_total', 12, 2);           
        $table->string('payment_method')->default('cash');
        $table->string('payment_status')->default('paid');
        $table->string('payment_reference')->nullable();
        $table->boolean('is_voided')->default(false);
        $table->timestamp('voided_at')->nullable();
        $table->string('voided_by')->nullable();
        $table->text('void_reason')->nullable();
        $table->timestamp('issue_date')->nullable();
        $table->index('receipt_no');
        $table->index('order_id');
        $table->index('issue_date');
        $table->index('payment_status');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
