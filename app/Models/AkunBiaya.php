<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AkunBiaya extends Model
{
    use Auditable, HasFactory;

    protected $table = 'akun_biaya';

    protected $fillable = [
        'name',
        'dikecualikan',
    ];

    protected $casts = [
        'dikecualikan' => 'boolean',
    ];

    public function subAkunBiaya()
    {
        return $this->hasMany(SubAkunBiaya::class, 'akun_biaya_id');
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class, 'akun_biaya_id');
    }

    public function auditType(): string
    {
        return 'Akun';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
