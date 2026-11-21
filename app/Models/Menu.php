<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use Auditable, HasFactory;

    protected $fillable = ['name','slug','route','icon','parent_id','sort_order','is_active'];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_menu')
            ->withPivot(['can_view','can_create','can_update','can_delete'])
            ->withTimestamps();
    }

    public function auditType(): string
    {
        return 'Menu';
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
