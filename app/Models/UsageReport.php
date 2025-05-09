<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'totalUsed',
        'usageDate',
    ];

    protected $casts = [
        'totalUsed' => 'integer',
        'usageDate' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 