<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use Auditable, HasFactory;

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

    public function auditType(): string
    {
        return 'Divisi';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
