<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete(); // site survey engineer
            $table->string('roof_type', 50)->nullable();
            $table->decimal('roof_size_sqft', 10, 2)->nullable();
            $table->string('shadow_analysis', 100)->nullable();
            $table->string('direction', 20)->nullable(); // north, south, east, west
            $table->string('inverter_capacity_recommendation', 50)->nullable(); // kW
            $table->decimal('system_size_kw', 8, 2)->nullable();
            $table->text('load_analysis')->nullable();
            $table->text('electrical_connection_notes')->nullable();
            $table->string('report_url', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending, in_progress, completed
            $table->date('survey_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::table('surveys', fn (Blueprint $t) => $t->index(['lead_id', 'status']));
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
