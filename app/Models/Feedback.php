<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'ratings',
        'comments',
        'is_critical',
        'date_submitted',
    ];

    protected $casts = [
        'ratings' => 'integer',
        'is_critical' => 'boolean',
        'date_submitted' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
} 