<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required']
        ]);

        $check_status = DB::table('users')
            ->select(['status'])
            ->where('username', '=', $request->input('username'))
            ->get()
            ->first();

        if (!$check_status) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
                'password' => ['The provided credentials are incorrect.']
            ]);
        }

        if ($check_status && $check_status->status == 'inactive') {
            throw ValidationException::withMessages([
                'username' => ['Your account has been deactivated. Please contact the Administrator for activation.']
            ]);
        } 

        if (Auth::attempt($request->only('username', 'password'))) {
            $permissions = Auth::user()->role->permissions;
            return response()->json(['user' => Auth::user(), 'permissions' => $permissions], 200);
        }

        throw ValidationException::withMessages([
            'username' => ['The provided credentials are incorrect.'],
            'password' => ['The provided credentials are incorrect.']
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json('logout');
    }
}
