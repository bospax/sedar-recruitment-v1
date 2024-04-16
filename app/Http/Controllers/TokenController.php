<?php

namespace App\Http\Controllers;

use App\Models\Token;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TokenController extends Controller
{
    public function index() {
        $tokens = DB::table('tokens')
            ->select([
                'id',
                'unhashed_token',
                'created_at'
            ])
            ->get();

        return response()->json($tokens);
    }

    public function destroy($id)
    {
        $token = Token::findOrFail($id);

        if ($token->delete()) {
            DB::table('personal_access_tokens')->where('name', '=', $token->name)->delete();
            return response()->json($token);
        }
    }

    public function generateToken() {
        $logged = Auth::user()->id;
        $user = User::find($logged);
        $name = 'developer-access|'.time();
        $token = $user->createToken($name);

        $store = new Token();
        $store->unhashed_token = $token->plainTextToken;
        $store->name = $name;
        $store->save();

        return response()->json($store);
    }
}
