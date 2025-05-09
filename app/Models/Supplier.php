<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contactNum',
        'address',
    ];

    /**
     * Get the purchase orders for the supplier.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }
} 