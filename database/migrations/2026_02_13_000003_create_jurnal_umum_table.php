<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_umum', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->nullable();
            $table->string('keterangan')->nullable();
            $table->foreignId('sub_divisi_id')
                ->constrained('sub_divisions')
                ->cascadeOnDelete();
            $table->foreignId('sub_akun_biaya_id')
                ->constrained('sub_akun_biaya')
                ->cascadeOnDelete();
            $table->decimal('debet', 18, 2)->default(0);
            $table->decimal('kredit', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_umum');
    }
};
