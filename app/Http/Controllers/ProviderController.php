<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    public function redirect($provider){

    return  Socialite::driver($provider)->redirect();

}

public function callback($provider){
    $socialUser = Socialite::driver($provider)->user();

    // dd($socialUser);

    $user = User::updateOrCreate([
        'provider' =>$provider,
        'provider_id' =>$socialUser->id
    ],
        [
        'name' => $socialUser->name,
        'email' =>$socialUser->email,
        'provider_token'=>$socialUser->token
        // 'role' => 'user'

    ]);

    // dd($user);
    Auth::login($user);

    // return redirect('dashboard');

    if(Auth::user()->role == 'admin' || Auth::user()->role == 'chef' || Auth::user()->role == 'cashier'){
        return to_route('adminDashboard');
    }
    if(Auth::user()->role == 'user'){
        return to_route('userDashboard');
    }

}


}
