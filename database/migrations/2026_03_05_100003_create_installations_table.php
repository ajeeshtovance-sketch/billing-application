<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installation_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('installation_number', 50)->nullable();
            $table->date('scheduled_date')->nullable();
            $table->string('status', 30)->default('scheduled'); // scheduled, material_dispatched, in_progress, quality_check, completed, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::table('installations', fn (Blueprint $t) => $t->index(['organization_id', 'status']));
    }

    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
