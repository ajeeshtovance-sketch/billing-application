<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });

        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });

        Schema::table('delivery_challans', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
        });
    }
};
