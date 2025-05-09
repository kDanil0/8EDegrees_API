<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'percentage',
        'description',
        'is_active',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Get the discount as a decimal (e.g., 20% becomes 0.2)
    public function getDecimalValueAttribute()
    {
        return $this->percentage / 100;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
