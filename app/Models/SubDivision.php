<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'name',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }
}
