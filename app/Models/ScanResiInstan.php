<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanResiInstan extends Model
{
    use HasFactory;

    protected $table = 'scan_resi_instan';

    protected $fillable = [
        'order_id',
        'source_name',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];
}
