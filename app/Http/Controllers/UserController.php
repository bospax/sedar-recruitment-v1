<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\UsersExport;
use App\Http\Requests\UserRequest;
use App\Http\Resources\User as UserResources;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {   
        $users = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $users = DB::table('users')
                    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                    ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                    ->select([
                        'users.id', 
                        'users.employee_id',
                        'users.name', 
                        'users.username', 
                        'users.role_id', 
                        'users.status',
                        'users.status_description',
                        'users.created_at',
                        'roles.key',
                        'roles.role_name',
                        'roles.permissions',
                        'employees.prefix_id',
                        'employees.id_number',
                        'employees.first_name',
                        'employees.middle_name',
                        'employees.last_name',
                        'employees.suffix',
                        'employees.gender',
                        'employees.image',
                    ])
                    ->where('name', 'LIKE', $value)
                    ->where('roles.key', '!=' , 'lrEdpvD0lbljzpVcdi5z')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $users = DB::table('users')
                    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                    ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                    ->select([
                        'users.id', 
                        'users.employee_id',
                        'users.name', 
                        'users.username', 
                        'users.role_id',
                        'users.status',
                        'users.status_description',
                        'users.created_at',
                        'roles.key',
                        'roles.role_name',
                        'roles.permissions',
                        'employees.prefix_id',
                        'employees.id_number',
                        'employees.first_name',
                        'employees.middle_name',
                        'employees.last_name',
                        'employees.suffix',
                        'employees.gender',
                        'employees.image',
                    ])
                    ->where('roles.key', '!=' , 'lrEdpvD0lbljzpVcdi5z')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return UserResources::collection($users);
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return new UserResources($user);
    }

    public function store(UserRequest $request)
    {  
        $user = new User();
        $user->employee_id = $request->input('employee_id');
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->role_id = $request->input('role_id');
        $user->status = 'active';
        $user->status_description = 'ACTIVE';
        $user->password = Hash::make($request->input('password'));

        $duplicate = DB::table('users')
            ->select(['id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('role_id', '=', $request->input('role_id'))
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'role_id' => ['Duplicate account not allowed. Choose different role instead.']
            ]);
        }

        if ($user->save()) {
            Helpers::LogActivity($user->id, 'USER ACCOUNT - REGISTRATION', 'ADDED A NEW USER ACCOUNT');
            return new UserResources($user);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required'],
            'role_id' => ['required'],
            'username' => ['required', 'unique:users,username,'.$id],
        ]);

        $duplicate = DB::table('users')
            ->select(['id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('role_id', '=', $request->input('role_id'))
            ->where('id', '!=', $id)
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'role_id' => ['Duplicate account not allowed. Choose different role instead.']
            ]);
        }

        $user = User::findOrFail($id);
        $user->employee_id = $request->input('employee_id');
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->role_id = $request->input('role_id');

        if ($user->update()) {
            Helpers::LogActivity($user->id, 'USER ACCOUNT', 'UPDATED USER ACCOUNT DATA');

            $user = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                ->select([
                    'users.id', 
                    'users.employee_id',
                    'users.name', 
                    'users.username', 
                    'users.role_id',
                    'users.status',
                    'users.status_description',
                    'users.created_at',
                    'roles.key',
                    'roles.role_name',
                    'roles.permissions',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->where('users.id', '=' , $user->id)
                ->get();

            return UserResources::collection($user);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->delete()) {
            Helpers::LogActivity($user->id, 'USER ACCOUNT', 'DELETED USER ACCOUNT DATA');
            return new UserResources($user);
        }
    }

    public function export() 
    {
        $query = DB::table('users')->select(['id', 'name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'users-exportall.xlsx';
        $user_export = new UsersExport($query);
        $user_export->store('public/files/'.$filename);
        $link = '/storage/files/'.$filename;
        
        return response()->json([
            'link' => $link
        ]);
    }

    public function exportByDate($daterange)
    {
        if (!empty($daterange)) {
            $daterange = explode('-', $daterange);
            $from = $daterange[0];
            $to = $daterange[1];
            $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
            $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

            $query = DB::table('users')->select(['id', 'name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'users-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $user_export = new UsersExport($query);
                $user_export->store('public/files/'.$filename);
            }

            return response()->json([
                'link' => $link,
                'count' => $count
            ]);
        }
    }

    public function sortData() 
    {
        $field = request('field');
        $sort = request('sort');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'name' : 'created_at';            

            $users = DB::table('users')->select(['id', 'name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return UserResources::collection($users);
        }
    }

    public function authenticated()
    {
        $isLoggedIn = Auth::check();

        if ($isLoggedIn) {
            $current_user = Auth::user();
            $user = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->select([
                    'users.id', 
                    'users.employee_id',
                    'users.name', 
                    'users.username', 
                    'users.role_id', 
                    'users.status',
                    'users.status_description',
                    'users.created_at',
                    'roles.key',
                    'roles.role_name',
                    'roles.permissions',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                    'positions.position_name',
                    'employee_statuses.hired_date',
                ])
                ->where('users.id', '=', $current_user->id)
                ->get();

            return $user;
        }

        return false;
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'oldpassword' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'confirmation_failed'
        ]);

        $isLoggedIn = Auth::check();
        $oldpassword = $request->input('oldpassword');
        $newpassword = $request->input('password');
        
        if ($isLoggedIn) {
            $user = Auth::user();
            $user = User::find($user->id);
            $current_password = $user->password;

            if (!Hash::check($oldpassword, $current_password)) {
                throw ValidationException::withMessages([
                    'oldpassword' => ['authentication failed']
                ]);
            }

            if (Hash::check($newpassword, $current_password)) {
                throw ValidationException::withMessages([
                    'password' => ['New password cannot be the same as the old one.']
                ]);
            }

            $user->password = Hash::make($newpassword);
            $user->update();

            return 'success';
        }
    }

    public function resetPassword(Request $request) 
    {
        $isLoggedIn = Auth::check();
        $newpassword = $request->input('new_password');

        if ($isLoggedIn) {
            $user = User::find($request->input('employee_id'));
            $user->password = Hash::make($newpassword);
            $user->update();

            Helpers::LogActivity($user->id, 'USER ACCOUNT', 'USER ACCOUNT PASSWORD RESET');

            return 'success';
        }
    }

    public function changeUserStatus(Request $request) 
    {
        $id = $request->input('id');
        $user = User::findOrFail($id);
        $user->status = ($request->input('current_status') == 'active') ? 'inactive' : 'active';
        $user->status_description = ($request->input('current_status') == 'active') ? 'DEACTIVATED' : 'ACTIVE';

        if ($user->update()) {
            $activity = '';
            $activity = ($request->input('current_status') == 'active') ? 'USER ACCOUNT DE-ACTIVATED' : 'USER ACCOUNT ACTIVATED';

            Helpers::LogActivity($user->id, 'USER ACCOUNT', $activity);

            $user = DB::table('users')
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->leftJoin('employees', 'users.employee_id', '=', 'employees.id')
                ->select([
                    'users.id', 
                    'users.employee_id',
                    'users.name', 
                    'users.username', 
                    'users.role_id',
                    'users.status',
                    'users.status_description',
                    'users.created_at',
                    'roles.key',
                    'roles.role_name',
                    'roles.permissions',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                ])
                ->where('users.id', '=' , $user->id)
                ->get();

            return UserResources::collection($user);
        }
    }
}
