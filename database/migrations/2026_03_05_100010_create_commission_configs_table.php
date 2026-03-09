<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); // null = global (MD Super Admin)
            $table->string('name', 100);
            $table->string('type', 30)->default('percentage'); // percentage, fixed
            $table->decimal('value', 12, 2)->default(0);
            $table->string('applicable_to', 50)->nullable(); // role slug, or installation, amc, etc.
            $table->json('conditions')->nullable(); // e.g. min_sale_amount
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_configs');
    }
};
