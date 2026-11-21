<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'division_id',
        'akun_biaya_id',
        'tahun',
        'bulan',
        'amount',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
    ];

    public function periodeLabel(): string
    {
        if ($this->bulan) {
            return (\App\Support\Periode::NAMA_BULAN[$this->bulan] ?? $this->bulan).' '.$this->tahun;
        }
        return 'Tahunan '.$this->tahun;
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function akunBiaya()
    {
        return $this->belongsTo(AkunBiaya::class, 'akun_biaya_id');
    }

    public function auditType(): string
    {
        return 'Budget';
    }

    public function auditName(): string
    {
        return (optional($this->division)->name ?? '?').' · '.(optional($this->akunBiaya)->name ?? '?').' · '.$this->periodeLabel();
    }
}
