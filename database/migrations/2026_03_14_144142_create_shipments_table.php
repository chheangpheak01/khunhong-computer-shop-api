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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('cascade');
            $table->string('tracking_number')->unique(); 
            $table->string('carrier'); 
            $table->date('ship_date'); 
            $table->string('status')->default('in_transit'); 
            $table->timestamp('delivered_at')->nullable();
            $table->string('proof_of_delivery')->nullable(); 
            $table->text('delivery_notes')->nullable();
            $table->index('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
