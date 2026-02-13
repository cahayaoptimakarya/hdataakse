<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'akun_biaya_id',
        'amount',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function akunBiaya()
    {
        return $this->belongsTo(AkunBiaya::class, 'akun_biaya_id');
    }
}
