<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_divisi_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_divisi_id')
                ->constrained('sub_divisions')
                ->cascadeOnDelete();
            $table->foreignId('akun_biaya_id')
                ->constrained('akun_biaya')
                ->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->unique(['sub_divisi_id', 'akun_biaya_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_divisi_budgets');
    }
};
