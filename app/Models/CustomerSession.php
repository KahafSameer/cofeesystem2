<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    use HasFactory;

    public const STATUS_OPEN          = 'open';
    public const STATUS_BILL_REQUESTED = 'bill_requested';
    public const STATUS_CLOSED        = 'closed';

    protected $fillable = [
        'session_code',
        'waiter_id',
        'branch_id',
        'status',
        'opened_at',
        'bill_requested_at',
        'closed_at',
    ];

    protected $casts = [
        'opened_at'         => 'datetime',
        'bill_requested_at' => 'datetime',
        'closed_at'         => 'datetime',
    ];

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'session_id');
    }

    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isBillRequested()
    {
        return $this->status === self::STATUS_BILL_REQUESTED;
    }

    public function isClosed()
    {
        return $this->status === self::STATUS_CLOSED;
    }

    //Running subtotal from all associated order line items (excludes rejected/cancelled)
    public function subtotal()
    {
        return (float) $this->orders()
            ->where('status', '!=', 3)
            ->get()
            ->sum(fn($order) => (float) $order->totalprice * (int) $order->quantity);
    }

    //Running number of order groups (distinct order_code)
    public function ordersCount()
    {
        return $this->orders()
            ->where('status', '!=', 3)
            ->get()
            ->groupBy('order_code')
            ->count();
    }

    //Unique settlement order code, created once when the session is settled
    public function settlementCode()
    {
        return 'SET-' . $this->session_code;
    }

    //The single PaymentRecord produced when this session is settled
    public function settlementRecord()
    {
        return PaymentRecord::where('order_code', $this->settlementCode())->first();
    }
}
