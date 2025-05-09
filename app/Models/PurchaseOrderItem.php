<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'received_quantity',
        'rejected_quantity',
        'rejection_notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
        'received_quantity' => 'integer',
        'rejected_quantity' => 'integer',
    ];

    /**
     * Get the purchase order that owns the item.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product that this item represents.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
} 