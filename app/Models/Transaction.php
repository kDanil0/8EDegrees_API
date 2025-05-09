<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_id',
        'total_amount',
        'timestamp',
        'is_discount',
        'payment_mode',
        'reference_number',
        'discount_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'timestamp' => 'datetime',
        'is_discount' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(TransactionItem::class);
    }
    
    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
} 