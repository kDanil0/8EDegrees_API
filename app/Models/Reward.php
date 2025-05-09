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
        'pointsNeeded',
    ];

    protected $casts = [
        'pointsNeeded' => 'integer',
    ];

    public function rewardsHistory()
    {
        return $this->hasMany(RewardsHistory::class);
    }
} 