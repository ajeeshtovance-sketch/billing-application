<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['organization_id', 'issue_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['organization_id', 'payment_date']);
            $table->index(['organization_id', 'payment_method_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['organization_id', 'expense_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_organization_id_issue_date_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_organization_id_payment_date_index');
            $table->dropIndex('payments_organization_id_payment_method_id_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_organization_id_expense_date_index');
        });
    }
};
