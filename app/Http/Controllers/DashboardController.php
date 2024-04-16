<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getEmployeeCount() {
        // getting employee count
        // $data = DB::table('employees')
        //     ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
        //     ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
        //     ->leftJoin('employee_statuses', function($join) { 
        //         $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
        //     })
        //     ->select(['employees.id', 'employees.first_name', 'employees.created_at', 'employee_statuses.employment_type', 'employee_statuses.hired_date', 'positions.department_id'])
        //     ->where('employee_statuses.hired_date', '!=', null)
        //     ->get()
        //     ->groupBy(function ($data) {
        //         return Carbon::parse($data->hired_date)->format('M');
        //     });

        // $months = [];
        // $employeePerMonth = [];

        // foreach ($data as $month => $value) {
        //     $months[] = $month;
        //     $employeePerMonth[] = count($value);
        // }

        // $result = [
        //     'data' => $data,
        //     'months' => $months,
        //     'count' => $employeePerMonth
        // ];

        $inactive = [
            'TERMINATED',
            'RESIGNED',
            'ABSENT WITHOUT LEAVE',
            'END OF CONTRACT',
            'BLACKLISTED',
            'DISMISSED',
            'DECEASED',
            'BACK OUT'
        ];

        $output = DB::table('employees')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select(['employees.prefix_id', 'employees.id_number','employees.first_name','positions.team'])
            // ->where(function ($q) use ($inactive) {
            //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
            //         ->orWhereNull('employee_states.employee_state');
            // })
            ->where('employees.current_status', '=', 'approved')
            ->where(function ($q) use ($inactive) {
                $q->where(function ($innerQ) use ($inactive) {
                    $innerQ->whereNotIn('employee_states.employee_state_label', $inactive)
                        ->orWhereNull('employee_states.employee_state');
                })
                ->orWhere(function ($innerQ) use ($inactive) {
                    $innerQ->whereIn('employee_states.employee_state_label', $inactive)
                        ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                });
            
                if (Helpers::checkPermission('agency_only')) {
                    $q->where('positions.team', '=', Helpers::loggedInUser()->team);
                }
            })
            ->get()
            ->mapToGroups(function ($employee, $key) {
                return [$employee->team => $employee];
            })
            ->map(function ($group) {
                return count($group);
            });

        $data = [];

        foreach ($output as $key => $value) {
            $a['name'] = $key;
            $a['y'] = $value;
            $data[] = $a;
        }

        return response()->json($data);
    }

    public function getAgeGroup() {
        // get age group 
        $ranges = [ 
            '15-19' => 19,
            '20-24' => 24, 
            '25-29' => 29, 
            '30-34' => 34, 
            '35-39' => 39, 
            '40-44' => 44,
            '45-49' => 49, 
            '50-54' => 54, 
            '55-59' => 59, 
            '60-64' => 64, 
            '65-69' => 69,
            '70-74' => 74, 
            '75-79' => 79, 
            '80-84' => 84,
            '85+' => 100,
        ];

        $inactive = [
            'TERMINATED',
            'RESIGNED',
            'ABSENT WITHOUT LEAVE',
            'END OF CONTRACT',
            'BLACKLISTED',
            'DISMISSED',
            'DECEASED',
            'BACK OUT'
        ];

        $output = DB::table('employees')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->where('employees.current_status', '=', 'approved')
            ->where('employees.gender', 'LIKE', 'MALE')
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->get()
            ->map(function ($employee) use ($ranges) {
                $age = Carbon::parse($employee->birthdate)->age;

                foreach($ranges as $key => $breakpoint) {
                    if ($breakpoint >= $age) {
                        $employee->range = $key;
                        break;
                    }
                }

                return $employee;
            })
            ->mapToGroups(function ($employee, $key) {
                return [$employee->range => $employee];
            })
            ->map(function ($group) {
                return count($group);
            })
            ->sortKeys();

        $output_fe = DB::table('employees')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->where('employees.current_status', '=', 'approved')
            ->where('employees.gender', 'LIKE', 'FEMALE')
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->get()
            ->map(function ($employee) use ($ranges) {
                $age = Carbon::parse($employee->birthdate)->age;

                foreach($ranges as $key => $breakpoint) {
                    if ($breakpoint >= $age) {
                        $employee->range = $key;
                        break;
                    }
                }

                return $employee;
            })
            ->mapToGroups(function ($employee, $key) {
                return [$employee->range => $employee];
            })
            ->map(function ($group) {
                return count($group);
            })
            ->sortKeys();

        $result_male = [];
        $result_female = [];

        foreach ($ranges as $key => $value) {
            $result_male[$key] = (isset($output[$key])) ? $output[$key] : 0;
            $result_female[$key] = (isset($output_fe[$key])) ? $output_fe[$key] : 0;
        }

        return response()->json(['male' => $result_male, 'female' => $result_female]);
    }

    public function getGenderGroup() {
        $inactive = [
            'TERMINATED',
            'RESIGNED',
            'ABSENT WITHOUT LEAVE',
            'END OF CONTRACT',
            'BLACKLISTED',
            'DISMISSED',
            'DECEASED',
            'BACK OUT'
        ];

        $output = DB::table('employees')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->where('employees.current_status', '=', 'approved')
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->get()
            ->mapToGroups(function ($employee, $key) {
                return [$employee->gender => $employee];
            })
            ->map(function ($group) {
                return count($group);
            });

        $data = [];

        foreach ($output as $key => $value) {
            $a['name'] = $key;
            $a['y'] = $value;
            $data[] = $a;
        }

        return response()->json($data);
    }

    public function getLocationGroup() {
        $location = [];
        $count = [];

        $output = DB::table('employees')
            ->leftJoin('addresses', 'employees.id', '=', 'addresses.employee_id')
            ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            ->select(['employees.first_name','provinces.prov_desc'])
            ->where('employees.current_status', '=', 'approved')
            ->get()
            ->mapToGroups(function ($employee, $key) {
                return [$employee->prov_desc => $employee];
            })
            ->map(function ($group) {
                return count($group);
            });

        foreach ($output as $key => $value) {
            $location[] = $key;
            $count[] = $value;
        }

        return response()->json(['location' => $location, 'count' => $count]);
    }
}
