<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    //direct register page
    public function registerPage(){
        return view('Authentication.register');
    }

    public function loginPage(){
        return view('Authentication.login');
    }
}
