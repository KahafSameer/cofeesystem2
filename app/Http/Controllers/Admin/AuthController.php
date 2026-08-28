<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function passwordpage()
    {
        return view('admin.profile.passwordchange');
    }

    public function passwordchange(Request $request)
    {
        // dd($request->all());
        $validator = $request->validate([
            'oldPassword'     => 'required',
            'newPassword'     => 'required',
            'confirmPassword' => 'required|same:newPassword',
        ]);

        $dbHashPassword = User::select('password')->where('id', Auth::user()->id)->first();
        $dbHashPassword = $dbHashPassword['password'];
        //    dd($dbHashPassword);

        $userOldPassword = $request->oldPassword;
        if (Hash::check($userOldPassword, $dbHashPassword)) {
            $data = [
                'password' => Hash::make($request->newPassword),
            ];

            // dd($data);

            User::where('id', Auth::user()->id)->update($data);

            // return redirect()->route('profile.overview');
            return redirect()->route('profile.overview')->with('alert',
                [
                    'type' => 'success', 'message' => 'Password Reset successfully!',
                ]);
        }
    }

    //Password Reset Page
    public function resetPasswordPage()
    {
        return view('admin.profile.passwordreset');
    }

    public function resetPassword(Request $request)
    {
        // dd($request->all());

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return back()->with('error', 'Unable to process request');
        }

        $user->password = Hash::make(env('DEFAULT_USER_PASSWORD', 'Password123'));
        $user->save();

        return back()->with('success', 'Password has been reset to default');
    }
}
