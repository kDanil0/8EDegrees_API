<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RewardsHistory extends Model
{
    use HasFactory;

    protected $table = 'rewards_history';

    protected $fillable = [
        'customer_id',
        'reward_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }
} 