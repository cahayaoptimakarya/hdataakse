<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanResiShipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'resi_number',
        'sku',
        'quantity',
        'source_name',
        'scanned_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'scanned_at' => 'datetime',
    ];
}
