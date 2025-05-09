<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashDrawerOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'operation_date',
        'cash_in',
        'cash_out',
        'cash_count',
        'expected_cash',
        'short_over',
        'notes',
    ];

    protected $casts = [
        'operation_date' => 'date',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'cash_count' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'short_over' => 'decimal:2',
    ];
} 