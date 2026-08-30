<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierDraft extends Model
{
    const STATUS_ACTIVE    = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PAID      = 'paid';
    const STATUS_DISCARDED = 'discarded';

    // Mirrors orders.order_type: 1 eat_in | 2 take_away | 3 delivery
    const ORDER_TYPE_EAT_IN    = 1;
    const ORDER_TYPE_TAKE_AWAY = 2;
    const ORDER_TYPE_DELIVERY  = 3;

    const ORDER_TYPE_STRING = [
        self::ORDER_TYPE_EAT_IN    => 'eat_in',
        self::ORDER_TYPE_TAKE_AWAY => 'take_away',
        self::ORDER_TYPE_DELIVERY  => 'delivery',
    ];

    protected $fillable = [
        'cashier_id',
        'branch_id',
        'order_code',
        'label',
        'status',
        'order_type',
        'delivery_location_id',
    ];

    protected $casts = [
        'order_type' => 'integer',
        'delivery_location_id' => 'integer',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveryLocation()
    {
        return $this->belongsTo(DeliveryFees::class, 'delivery_location_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'orderCode', 'order_code');
    }

    public function orderTypeString(): string
    {
        return self::ORDER_TYPE_STRING[(int) $this->order_type] ?? 'eat_in';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_SUSPENDED], true);
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isDiscarded(): bool
    {
        return $this->status === self::STATUS_DISCARDED;
    }
}