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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');        
            $table->string('brand');        
            $table->string('slug')->unique(); 
            $table->decimal('price', 10, 2);  
            $table->integer('stock')->default(0); 
            $table->text('description')->nullable();
            $table->string('image')->nullable(); 
            $table->boolean('status')->default(true); 
            $table->index('status');
            $table->index('category_id');
            $table->index('brand');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
