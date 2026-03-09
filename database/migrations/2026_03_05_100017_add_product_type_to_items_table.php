<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'product_type')) {
                $table->string('product_type', 50)->nullable()->after('item_type'); // solar_panel, inverter, battery, mounting_kit, cable, junction_box, service
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'product_type')) {
                $table->dropColumn('product_type');
            }
        });
    }
};
