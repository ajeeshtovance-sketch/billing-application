<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete(); // branch
            $table->string('lead_number', 50)->nullable();
            $table->string('customer_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->json('address')->nullable();
            $table->decimal('electricity_bill_amount', 12, 2)->nullable();
            $table->string('location_gps', 255)->nullable(); // lat,lng
            $table->string('roof_type', 50)->nullable(); // flat, sloped, etc.
            $table->string('lead_source', 50)->default('walk_in'); // website, facebook_ads, google_ads, whatsapp, referral, walk_in, phone
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // sales executive
            $table->string('status', 30)->default('new'); // new, contacted, site_survey, proposal, negotiation, confirmed, installation, closed_lost
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('leads', function (Blueprint $table) {
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'lead_source']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
