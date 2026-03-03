<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('sequence_type', 50);
            $table->string('prefix', 20)->nullable();
            $table->unsignedInteger('current_value')->default(0);
            $table->unsignedInteger('padding')->default(5);
            $table->timestamps();

            $table->unique(['organization_id', 'sequence_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
