<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubAkunBiaya extends Model
{
    use Auditable, HasFactory;

    protected $table = 'sub_akun_biaya';

    protected $fillable = [
        'akun_biaya_id',
        'name',
    ];

    public function akunBiaya()
    {
        return $this->belongsTo(AkunBiaya::class, 'akun_biaya_id');
    }

    public function auditType(): string
    {
        return 'Kategori';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
