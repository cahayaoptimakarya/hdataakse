<?php

use App\Support\JurnalRowHash;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_hash', 64)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // berhasil | gagal | ditolak | dibatalkan
            $table->string('status', 20)->index();
            $table->unsignedInteger('rows_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->decimal('total_debet', 18, 2)->default(0);
            $table->decimal('total_kredit', 18, 2)->default(0);
            $table->date('tanggal_awal')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->string('message')->nullable();
            $table->string('error_file_path')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('jurnal_umum', function (Blueprint $table) {
            $table->foreignId('import_batch_id')->nullable()->after('id')
                ->constrained('jurnal_import_batches')->nullOnDelete();
            $table->string('row_hash', 64)->nullable()->after('kredit')->index();
        });

        // Isi row_hash untuk data lama agar pengecekan duplikat juga berlaku terhadapnya.
        DB::table('jurnal_umum')->orderBy('id')->chunkById(1000, function ($rows) {
            foreach ($rows as $row) {
                DB::table('jurnal_umum')->where('id', $row->id)->update([
                    'row_hash' => JurnalRowHash::make(
                        $row->tanggal,
                        $row->sub_divisi_id,
                        $row->sub_akun_biaya_id,
                        $row->debet,
                        $row->kredit,
                        $row->keterangan
                    ),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('jurnal_umum', function (Blueprint $table) {
            $table->dropConstrainedForeignId('import_batch_id');
            $table->dropIndex(['row_hash']);
            $table->dropColumn('row_hash');
        });
        Schema::dropIfExists('jurnal_import_batches');
    }
};
