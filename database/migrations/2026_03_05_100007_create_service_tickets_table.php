<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number', 50)->nullable();
            $table->string('status', 30)->default('open'); // open, assigned, in_progress, resolved, closed
            $table->string('priority', 20)->default('medium'); // low, medium, high
            $table->text('complaint')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->timestamps();
        });
        Schema::table('service_tickets', fn (Blueprint $t) => $t->index(['organization_id', 'status']));
    }

    public function down(): void
    {
        Schema::dropIfExists('service_tickets');
    }
};
