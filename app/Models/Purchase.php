<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    //
    protected $fillable = ['total_amount','paid_amount','due_amount','payment_status','supplier_id'];
}
