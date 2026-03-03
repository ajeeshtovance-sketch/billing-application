<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('credit_note_number', 50);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('open'); // open, refunded, applied, void
            $table->decimal('total', 15, 2);
            $table->decimal('balance', 15, 2);
            $table->string('refund_status', 20)->nullable(); // refund, no_refund
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'credit_note_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
