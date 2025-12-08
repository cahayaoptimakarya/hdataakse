<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('scan_resi_shipments') || Schema::hasColumn('scan_resi_shipments', 'order_id')) {
            return;
        }

        Schema::table('scan_resi_shipments', function (Blueprint $table) {
            $table->string('order_id', 150)->after('resi_number');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('scan_resi_shipments') || !Schema::hasColumn('scan_resi_shipments', 'order_id')) {
            return;
        }

        Schema::table('scan_resi_shipments', function (Blueprint $table) {
            $table->dropIndex(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
