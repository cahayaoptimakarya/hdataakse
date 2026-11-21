<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Log aktivitas: siapa mengubah apa, kapan. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);          // tambah | ubah | hapus | import | batal | ...
            $table->string('subject_type', 50);    // Budget, Divisi, Akun, ...
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 255);
            $table->json('changes')->nullable();   // {field: [lama, baru]}
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
