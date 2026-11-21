<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu kali upload file jurnal umum.
 */
class JurnalImportBatch extends Model
{
    public const STATUS_BERHASIL = 'berhasil';
    public const STATUS_GAGAL = 'gagal';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'file_name',
        'file_hash',
        'user_id',
        'status',
        'rows_count',
        'error_count',
        'total_debet',
        'total_kredit',
        'tanggal_awal',
        'tanggal_akhir',
        'message',
        'error_file_path',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'tanggal_awal' => 'date',
        'tanggal_akhir' => 'date',
        'cancelled_at' => 'datetime',
        'total_debet' => 'float',
        'total_kredit' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function jurnal()
    {
        return $this->hasMany(JurnalUmum::class, 'import_batch_id');
    }

    public function kode(): string
    {
        return '#'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /** Batch berhasil yang datanya masih ada (belum dibatalkan). */
    public function scopeAktif($query)
    {
        return $query->where('status', self::STATUS_BERHASIL);
    }
}
