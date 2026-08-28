<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RouteController;


//Get
Route::get('category/list', [RouteController::class, 'categoryList']);

/*
key
name => name
id  => category_id

*/


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

//http://localhost:8000/api/admin/category/list
