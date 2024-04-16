<?php 
namespace App\Custom;

use App\Models\ActivityLog;
use App\Models\JobHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class Helpers 
{
    public static function generateCode($prefix) {
        $code = time();
        // $code = substr($code, 4);
        $code = $prefix.'-'.$code;
        return $code;
    }

    public static function generateCodeNewVersion($table, $prefix) {
        $number = DB::table($table)->max('id') + 1;
        // $randomNumber = mt_rand(10000, 99999);
        $code = $prefix.'-'.str_pad($number, 4, '0', STR_PAD_LEFT);
        // $code = $prefix.'-'.$randomNumber.'-'.time();

        return $code;
    }

    public static function checkDuplicate($table, $field, $value, $id = '') {
        $duplicate = DB::table($table)
            ->select(['id'])
            ->where($field, '=', $value);

        if ($id) {
            $duplicate = DB::table($table)
                ->select(['id'])
                ->where($field, '=', $value)
                ->where('id', '!=', $id);
        }

        $duplicate->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                $field => ['The '.$field.' has already been taken.']
            ]);
        }
    }

    public static function LogActivity($reference_id, $module, $activity) {
        $user_id = Auth::user()->id;
        $log = new ActivityLog();
        $log->user_id = $user_id;
        $log->reference_id = $reference_id;
        $log->module = $module;
        $log->activity = $activity;
        $log->save();
    }

    public static function LogHistory($reference_id, $record_id, $record_type, $employee_id, $details) {
        $log = new JobHistory();
        $log->reference_number = $reference_id;
        $log->record_id = $record_id;
        $log->record_type = $record_type;
        $log->employee_id = $employee_id;
        $log->details = $details;
        $log->save();
    }

    public static function checkPermission($permission) {
        $isLoggedIn = Auth::check();

        if ($isLoggedIn) {
            $permissions = Auth::user()->role->permissions;
            $permissions = explode(',', $permissions);
            
            if (in_array($permission, $permissions)) {
                return true;
            }
        }

        return false;
    }

    public static function loggedInUser() {
        $loggedin_id = Auth::user()->employee_id;
        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.team'
            ])
            ->where('employees.id', '=', $loggedin_id)
            ->first();

        return $employee;
    }
}

?>