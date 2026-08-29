<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [ 'product_id','user_id','status',
                            'order_code','quantity','totalprice',
                            'payment_method','order_type','size','notes',
                            'delivery_location_id','waiter_id','branch_id'
                          ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
