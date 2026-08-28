<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    //
    protected $fillable = ['name'];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'asset_category_id');
    }
}
