<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    //
    public function detail()
    {
        return view('admin.profile.adminProfile');
    }

    // update profile
    public function update(Request $request)
    {
        // dd($request->all());
        $this->validationCheck($request);
        $adminData = $this->requestAdminData($request);

        if ($request->hasFile('image')) {
            if ($request->oldImage != null) {
                if (file_exists(public_path('/public/adminProfile/' . $request->oldImage))) {
                    unlink(public_path('/public/adminProfile/' . $request->oldImage));
                }
            }
            //upload new image
            $fileName = uniqid() . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path() . '/adminProfile/', $fileName);
            $adminData['profile'] = $fileName;
        } else {
            $adminData['profile'] = $request->oldImage;
        }

        // dd($adminData);
        User::where('id', Auth::user()->id)->update($adminData);

        return redirect()->route('profile.overview')->with('alert',
            [
                'type'    => 'success',
                'message' => 'Profile Updated successfully!',
            ]);
    }

    public function overview()
    {
        return view('admin.profile.overview');
    }

    private function validationCheck($request)
    {
        $rules = [
            'image' => ['mimes:png,jpeg,svg,gif,bmp,webp'],
            'phone' => ['required', 'unique:users,phone,' . Auth::user()->id],
        ];
        if (Auth::user()->provider == 'simple') {
            $validator['name']  = 'required';
            $validator['email'] = ['required', 'unique:user,email' . Auth::user()->id];
        }

        $validator = $request->validate($rules);
    }

    private function requestAdminData($request)
    {
        $data          = [];
        $data['name']  = Auth::user()->provider == 'simple' ? $request->name : Auth::user()->name;
        $data['email'] = Auth::user()->provider == 'simple' ? $request->email : Auth::user()->email;
        $data['phone'] = $request->phone;
        $data['role']  = Auth::user()->role;

        return $data;
    }

    //Create New User
    public function createNewUser()
    {
        $branches = Branch::where('status', 'Active')->orderBy('name')->get();
        return view('admin.profile.createprofile', compact('branches'));
    }

    public function addNewUser(Request $request)
    {
        // dd($request->all());

        $rolesRequiringBranch = ['cashier', 'chef', 'waiter'];
        $selectedRole = $request->input('profile');

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|unique:users,email',
            'password'        => 'required|min:8',
            'confirmpassword' => 'required|same:password',
            'profile'         => 'required|in:admin,cashier,chef,waiter',
            'branch_id'       => [
                Rule::requiredIf(in_array($selectedRole, $rolesRequiringBranch)),
                'nullable',
                'exists:branches,id',
            ],
        ]);

        $addAccount = [
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['profile'],
            'provider' => 'simple',
        ];

        // Only branch-based staff get a branch_id; admin stays null
        if (in_array($validated['profile'], $rolesRequiringBranch)) {
            $addAccount['branch_id'] = $validated['branch_id'];
        } else {
            $addAccount['branch_id'] = null;
        }

        User::create($addAccount);

        return redirect()->route('profile.createNewUser')->with('alert',
            [
                'type'    => 'success',
                'message' => 'New User created successfully!',
            ]);
    }

    public function changeProfilePage(Request $request)
    {
        // dd($request->all());

        $searchKey = trim($request->input('searchKey'));

        $query = User::select('id', 'name', 'email', 'phone', 'status', 'role', 'branch_id')
            ->where('role', '!=', 'user')
            ->when($searchKey, function ($q) use ($searchKey) {
                $q->where(function ($q) use ($searchKey) {
                    $q->where('name', 'like', '%' . $searchKey . '%')
                        ->orWhere('email', 'like', '%' . $searchKey . '%');
                });
            });

        $users = $query->get();

        // $users = User::where('role','!=','user')->get();
        $roles = ['admin', 'cashier', 'chef', 'waiter'];

        $branches = Branch::where('status', 'Active')->orderBy('name')->get();

        // dd($users->toArray());

        return view('admin.profile.changeprofile', compact('users', 'roles', 'branches'));
    }

    public function updateField(Request $request, $id)
    {
        // dd($request->all());

        $field = $request->input('field'); // e.g. status, role, branch
        $value = $request->input('value'); // e.g. Active, cashier, 1

        $user = User::findOrFail($id);

        if ($field === 'branch') {
            $request->validate([
                'value' => ['required', 'exists:branches,id'],
            ]);

            $user->branch_id = $value;
            $user->save();

            return back()->with('alert', [
                'type'    => 'success',
                'message' => 'Branch updated successfully!',
            ]);
        }

        if (! in_array($field, ['role', 'status'])) {
            return back()->with('alert', [
                'type'    => 'error',
                'message' => 'Invalid field',
            ]);
        }

        if ($field === 'status') {
            $request->validate([
                'value' => ['required', 'in:Active,Inactive'],
            ]);
        }

        if ($field === 'role') {
            $request->validate([
                'value' => ['required', 'in:admin,cashier,chef,waiter'],
            ]);

            // Branch-based staff require a branch — warn but allow admin to fix it after
            if (in_array($value, ['cashier', 'chef', 'waiter']) && empty($user->branch_id)) {
                return back()->with('alert', [
                    'type'    => 'error',
                    'message' => 'Please assign a branch to this user before setting a branch-based role.',
                ]);
            }

            // Admin role clears the branch
            if (! in_array($value, ['cashier', 'chef', 'waiter'])) {
                $user->branch_id = null;
            }
        }

        $user->$field = $value;
        $user->save();

        return back()->with('alert', [
            'type'    => 'success',
            'message' => ucfirst($field) . ' updated successfully!',
        ]);
    }

}
