<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function subDivisions()
    {
        return $this->hasMany(SubDivision::class, 'division_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'division_id');
    }
}
