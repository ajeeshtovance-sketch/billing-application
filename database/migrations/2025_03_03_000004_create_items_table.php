<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 50)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('item_type', 20)->default('product'); // product, service
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->nullable();
            $table->decimal('stock_quantity', 12, 2)->default(0);
            $table->decimal('low_stock_alert', 12, 2)->default(0);
            $table->string('unit', 20)->default('each');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->index(['organization_id', 'stock_quantity', 'low_stock_alert']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
