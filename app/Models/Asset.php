<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    //
    protected $fillable = [
        'name',
        'asset_category_id',
        'assigned_user_id',
        'purchase_date',
        'purchase_value',
        'depreciation_rate',
        'status',
        'unit',
        'warranty_expiry_date',
        'serial_number',
        'notes',
    ];

     public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
