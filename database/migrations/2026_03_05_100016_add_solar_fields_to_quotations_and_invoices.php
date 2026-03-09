<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('quotations', 'survey_id')) {
                $table->foreignId('survey_id')->nullable()->after('lead_id')->constrained()->nullOnDelete();
            }
        });
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'installation_id')) {
                $table->foreignId('installation_id')->nullable()->after('delivery_challan_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'payment_mode')) {
                $table->string('payment_mode', 30)->nullable()->after('balance_due'); // full_cash, bank_emi, bank_loan
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations', 'lead_id')) {
                $table->dropForeign(['lead_id']);
            }
            if (Schema::hasColumn('quotations', 'survey_id')) {
                $table->dropForeign(['survey_id']);
            }
        });
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'installation_id')) {
                $table->dropForeign(['installation_id']);
            }
            if (Schema::hasColumn('invoices', 'payment_mode')) {
                $table->dropColumn('payment_mode');
            }
        });
    }
};
