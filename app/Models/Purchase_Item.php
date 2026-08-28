<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase_Item extends Model
{
    //
    protected $fillable = ['purchase_id','ingredient_id','quantity','cost_price','total_price'];
}
