<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        $database = config('database.connections.'.config('database.default').'.database');
        $result = DB::select("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = ? AND table_name = ? AND index_name = ?
            ", [$database, $table, $index]);
        return isset($result[0]) && (int) $result[0]->cnt > 0;
    }
};
