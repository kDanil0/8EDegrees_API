<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'product_id',
        'pointsNeeded',
        'is_active'
    ];

    protected $casts = [
        'pointsNeeded' => 'integer',
        'value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Define reward types as constants
    const TYPE_PERCENTAGE_DISCOUNT = 'percentage_discount';
    const TYPE_FREE_ITEM = 'free_item';

    public function rewardsHistory()
    {
        return $this->hasMany(RewardsHistory::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 