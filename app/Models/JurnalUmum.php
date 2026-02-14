<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalUmum extends Model
{
    use HasFactory;

    protected $table = 'jurnal_umum';

    protected $fillable = [
        'tanggal',
        'keterangan',
        'sub_divisi_id',
        'sub_akun_biaya_id',
        'debet',
        'kredit',
    ];

    public function subDivisi()
    {
        return $this->belongsTo(SubDivision::class, 'sub_divisi_id');
    }

    public function subAkunBiaya()
    {
        return $this->belongsTo(SubAkunBiaya::class, 'sub_akun_biaya_id');
    }
}
