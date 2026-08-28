<?php

namespace App\Http\Controllers\API;


use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\Review;
use App\Models\Product;
use App\Models\Discount;
use App\Models\TaxSetting;



class RouteController extends Controller
{

    public function categoryList(){
        $categories = Category::get();

        return response()->json($categories, 200);
    }

}
