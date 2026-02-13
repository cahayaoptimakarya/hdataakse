<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akun_biaya', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sub_akun_biaya', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_biaya_id')
                ->constrained('akun_biaya')
                ->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_akun_biaya');
        Schema::dropIfExists('akun_biaya');
    }
};
