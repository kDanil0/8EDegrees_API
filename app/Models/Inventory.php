<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'lastUpdated',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'lastUpdated' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 