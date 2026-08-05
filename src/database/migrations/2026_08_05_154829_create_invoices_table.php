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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->nullable();
            $table->string('supplier_name')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('issue_date')->nullable();
            $table->string('file_path');
            
            // Estado para la cola de vectorización
            $table->enum('vector_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');
            $table->string('vector_id')->nullable(); 
            $table->text('vector_error')->nullable(); 
            
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
