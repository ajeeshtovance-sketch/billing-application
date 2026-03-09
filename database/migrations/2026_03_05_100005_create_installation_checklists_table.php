<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->string('task_name', 100); // panel_mounting, wiring, inverter_installation, grid_connection, system_testing
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_checklists');
    }
};
