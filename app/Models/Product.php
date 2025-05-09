<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'quantity',
        'category_id',
        'reorderLevel',
        'price',
        'status',
        'expiryDate',
    ];

    protected $casts = [
        'expiryDate' => 'date',
        'price' => 'float',
        'quantity' => 'integer',
        'reorderLevel' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function expReport()
    {
        return $this->hasOne(ExpReport::class);
    }

    public function usageReports()
    {
        return $this->hasMany(UsageReport::class);
    }

    public function salesReports()
    {
        return $this->hasMany(SalesReport::class);
    }

    public function transactionItems()
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
} 