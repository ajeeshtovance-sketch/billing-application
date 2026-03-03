<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_number', 50);
            $table->string('supplier_name')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('purchase_date');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('bill_attachment_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['organization_id', 'purchase_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
