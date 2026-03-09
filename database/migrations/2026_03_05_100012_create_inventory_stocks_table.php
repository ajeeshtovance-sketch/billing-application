<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); // branch
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('low_stock_alert', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::table('inventory_stocks', fn (Blueprint $t) => $t->unique(['organization_id', 'item_id']));
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
