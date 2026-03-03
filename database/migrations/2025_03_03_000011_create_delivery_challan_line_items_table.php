<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_challan_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_challan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->default('each');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_challan_line_items');
    }
};
