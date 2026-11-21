<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Catatan peringatan budget yang sudah dikirim, agar satu pos tidak diingatkan berulang kali. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->foreignId('division_id')->constrained('divisions')->cascadeOnDelete();
            $table->foreignId('akun_biaya_id')->constrained('akun_biaya')->cascadeOnDelete();
            $table->unsignedSmallInteger('level'); // 80 atau 100
            $table->decimal('pct', 8, 2)->nullable();
            $table->timestamp('sent_at');
            $table->unique(['tahun', 'division_id', 'akun_biaya_id', 'level'], 'budget_alerts_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
    }
};
