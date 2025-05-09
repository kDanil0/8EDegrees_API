<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'totalSales',
        'totalAmount',
        'timestamp',
    ];

    protected $casts = [
        'totalSales' => 'integer',
        'totalAmount' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 