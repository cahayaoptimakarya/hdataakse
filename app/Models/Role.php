<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'permission_menu')
            ->withPivot(['can_view','can_create','can_update','can_delete'])
            ->withTimestamps();
    }

    public function auditType(): string
    {
        return 'Peran';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
