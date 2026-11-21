<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akun yang tidak dihitung sebagai biaya (misalnya kas) kini diatur lewat kolom,
 * bukan hardcode. Akun id 1 / bernama "kas" otomatis ditandai agar laporan tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akun_biaya', function (Blueprint $table) {
            $table->boolean('dikecualikan')->default(false)->after('name');
        });
        DB::table('akun_biaya')->where('id', 1)->orWhereRaw('LOWER(name) = ?', ['kas'])->update(['dikecualikan' => true]);
    }

    public function down(): void
    {
        Schema::table('akun_biaya', function (Blueprint $table) {
            $table->dropColumn('dikecualikan');
        });
    }
};
