<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request) 
    {
        $request->validate([
            'name' => ['required'],
            'username' => ['required', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password)
        ]);
    }
}
