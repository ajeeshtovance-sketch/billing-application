<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amc_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 20)->default('active'); // active, expired, renewed
            $table->boolean('renewal_reminder_sent')->default(false);
            $table->timestamps();
        });
        Schema::table('amc_contracts', fn (Blueprint $t) => $t->index(['organization_id', 'end_date']));
    }

    public function down(): void
    {
        Schema::dropIfExists('amc_contracts');
    }
};
