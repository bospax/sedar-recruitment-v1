<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $isLoggedIn = Auth::check();

        if ($isLoggedIn) {
            $permissions = Auth::user()->role->permissions;
            $permissions = explode(',', $permissions);
            
            if (!in_array($permission, $permissions)) {
                abort(401, 'Unauthorized');
            }
        }

        return $next($request);
    }
}
