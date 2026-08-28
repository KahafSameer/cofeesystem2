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
                            'delivery_location_id'
                          ];
}
