<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('scan_resi_shipments')) {
            return;
        }

        $indexName = 'scan_resi_shipments_resi_number_sku_unique';
        $hasIndex = $this->hasIndex('scan_resi_shipments', $indexName);

        if ($hasIndex) {
            Schema::table('scan_resi_shipments', function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('scan_resi_shipments')) {
            return;
        }

        Schema::table('scan_resi_shipments', function (Blueprint $table) {
            $table->unique(['resi_number', 'sku']);
        });
    }

    protected function hasIndex(string $table, string $index): bool
    {
        // Portabel untuk MySQL dan SQLite (dipakai saat test).
        return Schema::hasIndex($table, $index);
    }
};
