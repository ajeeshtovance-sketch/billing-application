<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete(); // null = system-wide
            $table->string('key', 100); // payment_gateway, sms, whatsapp, email
            $table->string('name', 100)->nullable();
            $table->json('config')->nullable(); // encrypted credentials / settings
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::table('integrations', fn (Blueprint $t) => $t->unique(['organization_id', 'key']));
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
