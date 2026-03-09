<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $softDeleteTables = [
            'users',
            'organizations',
            'customers',
            'items',
            'categories',
            'quotations',
            'quotation_line_items',
            'invoices',
            'invoice_line_items',
            'delivery_challans',
            'delivery_challan_line_items',
            'credit_notes',
            'credit_note_line_items',
            'payments',
            'payment_methods',
            'number_sequences',
            'purchases',
            'purchase_line_items',
            'expenses',
            'stock_movements',
            'leads',
            'surveys',
            'installations',
            'installation_assignments',
            'installation_checklists',
            'installation_photos',
            'service_tickets',
            'amc_contracts',
            'vendors',
            'commission_configs',
            'documents',
            'inventory_stocks',
            'inventory_transfers',
            'integrations',
        ];

        foreach ($softDeleteTables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $softDeleteTables = [
            'users', 'organizations', 'customers', 'items', 'categories',
            'quotations', 'quotation_line_items', 'invoices', 'invoice_line_items',
            'delivery_challans', 'delivery_challan_line_items', 'credit_notes', 'credit_note_line_items',
            'payments', 'payment_methods', 'number_sequences', 'purchases', 'purchase_line_items',
            'expenses', 'stock_movements', 'leads', 'surveys', 'installations',
            'installation_assignments', 'installation_checklists', 'installation_photos',
            'service_tickets', 'amc_contracts', 'vendors', 'commission_configs',
            'documents', 'inventory_stocks', 'inventory_transfers', 'integrations',
        ];

        foreach ($softDeleteTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $blueprint) {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
