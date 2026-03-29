<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',    // Add this for guest checkout
        'customer_email',   // Add this for guest checkout
        'total',
        'total_amount',
        'shipping_address',
        'phone',
        'payment_method',
        'order_status',
        'status'
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}