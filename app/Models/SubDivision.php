<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDivision extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'division_id',
        'name',
    ];

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function auditType(): string
    {
        return 'Toko';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
