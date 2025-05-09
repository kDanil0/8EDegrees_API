<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'order_number',
        'quantity',
        'totalAmount',
        'orderDate',
        'status',
        'expected_delivery_date',
        'notes',
    ];

    protected $casts = [
        'orderDate' => 'date',
        'expected_delivery_date' => 'date',
        'quantity' => 'integer',
        'totalAmount' => 'float',
    ];

    /**
     * Get the supplier that owns the purchase order.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the items for the purchase order.
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
} 