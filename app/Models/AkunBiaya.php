<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunBiaya extends Model
{
    use HasFactory;

    protected $table = 'akun_biaya';

    protected $fillable = [
        'name',
    ];

    public function subAkunBiaya()
    {
        return $this->hasMany(SubAkunBiaya::class, 'akun_biaya_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'akun_biaya_id');
    }
}
