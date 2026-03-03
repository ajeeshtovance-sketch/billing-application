<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('dc_number', 50);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft'); // draft, sent, delivered
            $table->date('delivery_date')->nullable();
            $table->json('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'dc_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challans');
    }
};
