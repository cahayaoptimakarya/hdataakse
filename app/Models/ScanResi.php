<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanResi extends Model
{
    use HasFactory;

    protected $table = 'scan_resi';

    protected $fillable = [
        'resi_number',
        'source_name',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];
}
