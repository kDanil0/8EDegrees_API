<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contactNum',
        'points',
        'eligibleForRewards',
    ];

    protected $casts = [
        'points' => 'integer',
        'eligibleForRewards' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function rewardsHistory()
    {
        return $this->hasMany(RewardsHistory::class);
    }
} 