<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'dateOfExp',
        'daysTimeExp',
    ];

    protected $casts = [
        'dateOfExp' => 'date',
        'daysTimeExp' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 