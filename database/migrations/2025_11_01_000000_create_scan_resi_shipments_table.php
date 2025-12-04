<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scan_resi_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('resi_number', 150);
            $table->string('sku', 120);
            $table->unsignedInteger('quantity')->default(0);
            $table->string('source_name')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index('resi_number');
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_resi_shipments');
    }
};
