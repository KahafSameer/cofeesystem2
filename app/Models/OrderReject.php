<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReject extends Model
{
    use HasFactory;
    protected $fillable = ['product_id','user_id','order_code','status'];
}
