<?php

namespace App\Http\Controllers;

use App\Http\Resources\Role as RoleResources;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {   
        $roles = DB::table('roles')->select(['id', 'role_name', 'permissions', 'created_at'])
                    ->orderBy('created_at', 'desc')
                    ->paginate(100);

        return RoleResources::collection($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u', 'unique:roles,role_name'],
        ]);

        $role = new Role();
        $role->key = '';
        $role->role_name = $request->input('role_name');
        
        if ($request->input('permissions')) {
            $permissions = implode(',', $request->input('permissions'));
            $role->permissions = $permissions;
        }

        if ($role->save()) {
            return new RoleResources($role);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'role_name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u', 'unique:roles,role_name,'.$id]
        ]);

        $role = Role::findOrFail($id);
        $role->role_name = $request->input('role_name');

        if ($request->input('permissions')) {
            $permissions = implode(',', $request->input('permissions'));
            $role->permissions = $permissions;
        }

        if ($role->update()) {
            return new RoleResources($role);
        }
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->delete()) {
            return new RoleResources($role);
        }
    }

    public function getRoles()
    {
        $roles = DB::table('roles')->select(['id', 'key', 'role_name', 'permissions', 'created_at'])
                    ->where('key', '!=' , 'lrEdpvD0lbljzpVcdi5z')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return RoleResources::collection($roles);
    }
}
