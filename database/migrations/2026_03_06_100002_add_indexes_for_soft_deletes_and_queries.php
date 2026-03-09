<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes for soft delete filtering and common query patterns
        $indexes = [
            'users' => [
                ['organization_id', 'deleted_at'],
                ['role', 'deleted_at'],
            ],
            'organizations' => [
                ['status', 'deleted_at'],
            ],
            'customers' => [
                ['organization_id', 'deleted_at'],
                ['organization_id', 'status', 'deleted_at'],
            ],
            'items' => [
                ['organization_id', 'status', 'deleted_at'],
                ['organization_id', 'product_type', 'deleted_at'],
            ],
            'categories' => [
                ['organization_id', 'deleted_at'],
            ],
            'quotations' => [
                ['organization_id', 'status', 'deleted_at'],
                ['customer_id', 'deleted_at'],
                ['lead_id', 'deleted_at'],
            ],
            'quotation_line_items' => [
                ['quotation_id', 'deleted_at'],
            ],
            'invoices' => [
                ['organization_id', 'status', 'deleted_at'],
                ['customer_id', 'deleted_at'],
                ['installation_id', 'deleted_at'],
            ],
            'invoice_line_items' => [
                ['invoice_id', 'deleted_at'],
            ],
            'delivery_challans' => [
                ['organization_id', 'status', 'deleted_at'],
                ['customer_id', 'deleted_at'],
            ],
            'delivery_challan_line_items' => [
                ['delivery_challan_id', 'deleted_at'],
            ],
            'credit_notes' => [
                ['organization_id', 'status', 'deleted_at'],
                ['customer_id', 'deleted_at'],
            ],
            'credit_note_line_items' => [
                ['credit_note_id', 'deleted_at'],
            ],
            'payments' => [
                ['organization_id', 'deleted_at'],
                ['invoice_id', 'deleted_at'],
                ['customer_id', 'deleted_at'],
            ],
            'payment_methods' => [
                ['organization_id', 'deleted_at'],
            ],
            'number_sequences' => [
                ['organization_id', 'deleted_at'],
            ],
            'purchases' => [
                ['organization_id', 'deleted_at'],
            ],
            'purchase_line_items' => [
                ['purchase_id', 'deleted_at'],
            ],
            'expenses' => [
                ['organization_id', 'deleted_at'],
            ],
            'stock_movements' => [
                ['organization_id', 'item_id', 'deleted_at'],
            ],
            'leads' => [
                ['organization_id', 'deleted_at'],
                ['created_at', 'deleted_at'],
            ],
            'surveys' => [
                ['organization_id', 'deleted_at'],
                ['lead_id', 'deleted_at'],
            ],
            'installations' => [
                ['organization_id', 'deleted_at'],
                ['scheduled_date', 'deleted_at'],
            ],
            'installation_assignments' => [
                ['installation_id', 'deleted_at'],
                ['user_id', 'deleted_at'],
            ],
            'installation_checklists' => [
                ['installation_id', 'deleted_at'],
            ],
            'installation_photos' => [
                ['installation_id', 'deleted_at'],
            ],
            'service_tickets' => [
                ['organization_id', 'deleted_at'],
                ['customer_id', 'deleted_at'],
                ['installation_id', 'deleted_at'],
            ],
            'amc_contracts' => [
                ['organization_id', 'deleted_at'],
                ['installation_id', 'deleted_at'],
                ['customer_id', 'deleted_at'],
            ],
            'vendors' => [
                ['organization_id', 'status', 'deleted_at'],
            ],
            'commission_configs' => [
                ['organization_id', 'deleted_at'],
            ],
            'documents' => [
                ['organization_id', 'deleted_at'],
                ['documentable_type', 'documentable_id', 'deleted_at'],
            ],
            'inventory_stocks' => [
                ['organization_id', 'deleted_at'],
            ],
            'inventory_transfers' => [
                ['from_organization_id', 'deleted_at'],
                ['to_organization_id', 'deleted_at'],
                ['item_id', 'deleted_at'],
            ],
            'integrations' => [
                ['organization_id', 'deleted_at'],
            ],
        ];

        foreach ($indexes as $tableName => $indexList) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($indexList, $tableName) {
                foreach ($indexList as $columns) {
                    $indexName = $tableName . '_' . implode('_', $columns) . '_idx';
                    try {
                        $table->index($columns, $indexName);
                    } catch (\Throwable $e) {
                        // Index may already exist
                    }
                }
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'users' => [['organization_id', 'deleted_at'], ['role', 'deleted_at']],
            'organizations' => [['status', 'deleted_at']],
            'customers' => [['organization_id', 'deleted_at'], ['organization_id', 'status', 'deleted_at']],
            'items' => [['organization_id', 'status', 'deleted_at'], ['organization_id', 'product_type', 'deleted_at']],
            'categories' => [['organization_id', 'deleted_at']],
            'quotations' => [['organization_id', 'status', 'deleted_at'], ['customer_id', 'deleted_at'], ['lead_id', 'deleted_at']],
            'quotation_line_items' => [['quotation_id', 'deleted_at']],
            'invoices' => [['organization_id', 'status', 'deleted_at'], ['customer_id', 'deleted_at'], ['installation_id', 'deleted_at']],
            'invoice_line_items' => [['invoice_id', 'deleted_at']],
            'delivery_challans' => [['organization_id', 'status', 'deleted_at'], ['customer_id', 'deleted_at']],
            'delivery_challan_line_items' => [['delivery_challan_id', 'deleted_at']],
            'credit_notes' => [['organization_id', 'status', 'deleted_at'], ['customer_id', 'deleted_at']],
            'credit_note_line_items' => [['credit_note_id', 'deleted_at']],
            'payments' => [['organization_id', 'deleted_at'], ['invoice_id', 'deleted_at'], ['customer_id', 'deleted_at']],
            'payment_methods' => [['organization_id', 'deleted_at']],
            'number_sequences' => [['organization_id', 'deleted_at']],
            'purchases' => [['organization_id', 'deleted_at']],
            'purchase_line_items' => [['purchase_id', 'deleted_at']],
            'expenses' => [['organization_id', 'deleted_at']],
            'stock_movements' => [['organization_id', 'item_id', 'deleted_at']],
            'leads' => [['organization_id', 'deleted_at'], ['created_at', 'deleted_at']],
            'surveys' => [['organization_id', 'deleted_at'], ['lead_id', 'deleted_at']],
            'installations' => [['organization_id', 'deleted_at'], ['scheduled_date', 'deleted_at']],
            'installation_assignments' => [['installation_id', 'deleted_at'], ['user_id', 'deleted_at']],
            'installation_checklists' => [['installation_id', 'deleted_at']],
            'installation_photos' => [['installation_id', 'deleted_at']],
            'service_tickets' => [['organization_id', 'deleted_at'], ['customer_id', 'deleted_at'], ['installation_id', 'deleted_at']],
            'amc_contracts' => [['organization_id', 'deleted_at'], ['installation_id', 'deleted_at'], ['customer_id', 'deleted_at']],
            'vendors' => [['organization_id', 'status', 'deleted_at']],
            'commission_configs' => [['organization_id', 'deleted_at']],
            'documents' => [['organization_id', 'deleted_at'], ['documentable_type', 'documentable_id', 'deleted_at']],
            'inventory_stocks' => [['organization_id', 'deleted_at']],
            'inventory_transfers' => [['from_organization_id', 'deleted_at'], ['to_organization_id', 'deleted_at'], ['item_id', 'deleted_at']],
            'integrations' => [['organization_id', 'deleted_at']],
        ];

        foreach ($indexes as $tableName => $indexList) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($indexList, $tableName) {
                foreach ($indexList as $columns) {
                    $indexName = $tableName . '_' . implode('_', $columns) . '_index';
                    try {
                        $table->dropIndex($indexName);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            });
        }
    }
};
