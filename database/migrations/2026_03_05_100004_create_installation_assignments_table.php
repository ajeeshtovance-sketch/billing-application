<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // technician
            $table->string('role', 30)->default('technician'); // lead_technician, technician
            $table->timestamps();
        });
        Schema::table('installation_assignments', fn (Blueprint $t) => $t->unique(['installation_id', 'user_id']));
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_assignments');
    }
};
