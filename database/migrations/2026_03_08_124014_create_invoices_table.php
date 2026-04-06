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
        Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->cascadeOnDelete();
        $table->string('invoice_no')->unique(); 
        $table->string('customer_name')->nullable();
        $table->string('customer_email')->nullable();
        $table->string('customer_phone')->nullable();
        $table->text('shipping_address')->nullable();
        $table->decimal('subtotal', 12, 2);              
        $table->decimal('tax_rate', 5, 2)->default(10.00); 
        $table->decimal('tax_amount', 12, 2)->default(0);   
        $table->decimal('discount_amount', 12, 2)->default(0); 
        $table->decimal('grand_total', 12, 2);  
        $table->timestamp('issue_date')->nullable();
        $table->index('invoice_no');
        $table->index('order_id');
        $table->index('issue_date');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
