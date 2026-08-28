<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRecord extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','status','order_code',
                            'net_amount','paid_amount','change_amount','payment_method'];
}
