<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->json('address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('base_currency', 3)->default('INR');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
