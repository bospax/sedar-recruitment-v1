<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\EmployeeDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Employee as EmployeeResources;
use App\Http\Resources\EmployeePosition as EmployeePositionResources;
use App\Http\Resources\EmployeeStatus as ResourcesEmployeeStatus;
use App\Http\Resources\EmployeeStateAttachment as ResourcesEmployeeStateAttachment;
use App\Http\Resources\EmployeeAttainment as EmployeeAttainmentResources;
use App\Http\Resources\EmployeeAccount as EmployeeAccountResources;
use App\Http\Resources\AddressLite as AddressLiteResources;
use App\Http\Resources\EmployeeContactDetails as ResourcesEmployeeContactDetails;
use App\Http\Resources\EmployeePositionWithRate as EmployeePositionWithRateResources;
use App\Http\Resources\EmployeePositionWithRateAndSuperior;
use App\Http\Resources\EmployeePositionWithRateAndUnits;
use App\Http\Resources\EmployeeStatusWithTenure;
use App\Http\Resources\EmployeeWithCode as EmployeeWithCodeResources;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeMasterlistController extends Controller
{
    public function getEmployeeGeneralInfo(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_statuses', function($join) { // ITO MAY CONFLICT
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)'));
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)'));
            })
            ->select([
                'employees.id',
                'employees.code',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.birthdate',
                'employees.religion',
                'employees.civil_status',
                'employees.gender',
                'employees.referrer_id',
                'employees.image',
                'employees.current_status_mark',
                'employees.remarks',
                'employees.created_at',
                'employees.manpower_details',
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
            ])
            ->where('employees.current_status', '=', 'approved');
            // ->where('employees.prefix_id', '!=', 'INACTIVE'); // remove after debug

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        // dd($employees->get());
        // dd(Helpers::loggedInUser()->team);

        // dd($request->is_historical_active);

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            }
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                ->orderBy($field, $sort)
                ->groupBy('employees.id')
                ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return EmployeeWithCodeResources::collection($employees);
    }

    public function getEmployeePosition(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'employee_positions.id',
                'employee_positions.employee_id',
                'employee_positions.position_id',
                'employee_positions.jobrate_id',
                'employee_positions.division_id',
                'employee_positions.division_cat_id',
                'employee_positions.company_id',
                'employee_positions.location_id',
                'employee_positions.additional_rate',
                'employee_positions.jobrate_name',
                'employee_positions.salary_structure',
                'employee_positions.job_level',
                'employee_positions.allowance',
                'employee_positions.job_rate',
                'employee_positions.salary',
                'employee_positions.additional_tool',
                'employee_positions.schedule',
                'employee_positions.emp_shift',
                'employee_positions.remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'positions.position_name',
                'positions.team',
                // 'jobrates.salary_structure',
                // 'jobrates.job_level',
                // 'jobrates.job_rate',
                // 'jobrates.jobrate_name',
                // 'jobrates.allowance',
                'departments.department_name',
                'subunits.subunit_name',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'locations.location_name'
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return EmployeePositionWithRateAndUnits::collection($employees);
    }

    public function getEmployeeStatus(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'employee_statuses.id',
                'employee_statuses.employee_id',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_type',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',

                DB::raw('CASE 
                    WHEN employee_states.employee_state = "resigned" 
                    THEN TIMESTAMPDIFF(YEAR, employee_statuses.hired_date_fix, STR_TO_DATE(employee_states.state_date, "%M %e, %Y"))
                    ELSE TIMESTAMPDIFF(YEAR, employee_statuses.hired_date_fix, NOW())
                    END AS tenure'),
                'employee_states.employee_state',
                'employee_states.state_date',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'employees.created_at',
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return EmployeeStatusWithTenure::collection($employees);
    }

    public function getEmployeeStates(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'employee_states.id',
                'employee_states.employee_id',
                'employee_states.employee_state_label',
                'employee_states.employee_state',
                'employee_states.state_date_start',
                'employee_states.state_date_end',
                'employee_states.state_date',
                'employee_states.status_remarks',
                'employee_states.attachment',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'employees.created_at',
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        // dd($employees);

        return ResourcesEmployeeStateAttachment::collection($employees);
    }

    public function getEmployeeAttainment(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_attainments', function($join) { 
                $join->on('employee_attainments.created_at', DB::raw('(SELECT MAX(employee_attainments.created_at) FROM employee_attainments WHERE employee_attainments.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'employee_attainments.id',
                'employee_attainments.employee_id',
                'employee_attainments.attainment',
                'employee_attainments.course',
                'employee_attainments.degree',
                'employee_attainments.honorary',
                'employee_attainments.institution',
                'employee_attainments.attachment',
                'employee_attainments.years',
                'employee_attainments.gpa',
                'employee_attainments.academic_year_from',
                'employee_attainments.academic_year_to',
                'employee_attainments.attainment_remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return EmployeeAttainmentResources::collection($employees);
    }

    public function getEmployeeAccounts(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_accounts', function($join) { 
                $join->on('employee_accounts.created_at', DB::raw('(SELECT MAX(employee_accounts.created_at) FROM employee_accounts WHERE employee_accounts.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'employee_accounts.id',
                'employee_accounts.employee_id',
                'employee_accounts.sss_no',
                'employee_accounts.pagibig_no',
                'employee_accounts.philhealth_no',
                'employee_accounts.tin_no',
                'employee_accounts.bank_name',
                'employee_accounts.bank_account_no',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }
            
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return EmployeeAccountResources::collection($employees);
    }

    public function getEmployeeAddress(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('addresses', function($join) { 
                $join->on('addresses.created_at', DB::raw('(SELECT MAX(addresses.created_at) FROM addresses WHERE addresses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->select([
                'addresses.id',
                'addresses.employee_id',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image'
            ])
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->groupBy('employees.id')
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return AddressLiteResources::collection($employees);
    }

    public function getEmployeeContact(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');

        $query = DB::table('employees')
            // ->leftJoin('employee_contacts', function($join) { 
            //     $join->on('employee_contacts.created_at', DB::raw('(SELECT MAX(employee_contacts.created_at) FROM employee_contacts WHERE employee_contacts.employee_id = employees.id)')); 
            // })
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_contacts', 'employees.id', '=', 'employee_contacts.employee_id')
            ->select([
                'employee_contacts.id',
                'employee_contacts.employee_id',
                'employee_contacts.contact_type',
                'employee_contacts.contact_details',
                'employee_contacts.description',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image'
            ])
            // ->where('employee_contacts.contact_details', '!=', '')
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $employees = $query
                    ->where(function($query) use ($value){
                        $query->where('employees.last_name', 'LIKE', $value)
                            ->orWhere('employees.middle_name', 'LIKE', $value)
                            ->orWhere('employees.first_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                    });
            }

            if ($request->prefix_id) {
                $employees = $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $employees = $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $employees = $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }

            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }

            $employees = $query
                    ->orderBy($field, $sort)
                    ->paginate(20);
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $employees = $query->paginate(20);
        }

        return ResourcesEmployeeContactDetails::collection($employees);
    }

    public function export() {
        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
            ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_attainments', function($join) { 
                $join->on('employee_attainments.created_at', DB::raw('(SELECT MAX(employee_attainments.created_at) FROM employee_attainments WHERE employee_attainments.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_accounts', function($join) { 
                $join->on('employee_accounts.created_at', DB::raw('(SELECT MAX(employee_accounts.created_at) FROM employee_accounts WHERE employee_accounts.employee_id = employees.id)')); 
            })
            ->leftJoin('addresses', function($join) { 
                $join->on('addresses.created_at', DB::raw('(SELECT MAX(addresses.created_at) FROM addresses WHERE addresses.employee_id = employees.id)')); 
            })
            // ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
            // ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            // ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
            // ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
            ->leftJoin('employee_contacts', function($join) { 
                $join->on('employee_contacts.created_at', DB::raw('(SELECT MAX(employee_contacts.created_at) FROM employee_contacts WHERE employee_contacts.employee_id = employees.id)')); 
            })
            ->select([
                'employees.id',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.birthdate',
                'employees.religion',
                'employees.civil_status',
                'employees.gender',
                'employees.referrer_id',
                'employees.image',
                'employees.current_status_mark',
                'employees.remarks',
                'employees.created_at',
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'positions.position_name',
                'subunits.subunit_name',
                'departments.department_name',
                'jobbands.jobband_name',
                'divisions.division_name',
                'division_categories.category_name',
                'locations.location_name',
                'companies.company_name',
                'jobrates.job_level',
                'jobrates.job_rate',
                'jobrates.salary_structure',
                'jobrates.jobrate_name',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_type',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'employee_statuses.hired_date_fix',
                'employee_states.employee_state_label',
                'employee_states.employee_state',
                'employee_states.state_date_start',
                'employee_states.state_date_end',
                'employee_states.state_date',
                'employee_states.status_remarks',
                'employee_attainments.attainment',
                'employee_attainments.course',
                'employee_accounts.sss_no',
                'employee_accounts.pagibig_no',
                'employee_accounts.philhealth_no',
                'employee_accounts.tin_no',
                // 'addresses.region',
                // 'addresses.province',
                // 'addresses.municipal',
                // 'addresses.barangay',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                // 'regions.reg_desc',
                // 'provinces.prov_desc',
                // 'municipals.citymun_desc',
                // 'barangays.brgy_desc',
                'employee_contacts.contact_details',
            ])
            ->orderBy('employees.created_at', 'desc');

        $filename = 'employeedata-exportall.xlsx';
        $employee_export = new EmployeeDataExport($query);
        $employee_export->store('public/files/'.$filename);
        $link = '/storage/files/'.$filename;
        
        return response()->json([
            'link' => $link
        ]);
    }

    public function exportByDate(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $fields = request('fields');

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_attainments', function($join) { 
                $join->on('employee_attainments.created_at', DB::raw('(SELECT MAX(employee_attainments.created_at) FROM employee_attainments WHERE employee_attainments.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_accounts', function($join) { 
                $join->on('employee_accounts.created_at', DB::raw('(SELECT MAX(employee_accounts.created_at) FROM employee_accounts WHERE employee_accounts.employee_id = employees.id)')); 
            })
            ->leftJoin('addresses', function($join) { 
                $join->on('addresses.created_at', DB::raw('(SELECT MAX(addresses.created_at) FROM addresses WHERE addresses.employee_id = employees.id)')); 
            })
            
            // ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
            // ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            // ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
            // ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
            ->leftJoin('employee_contacts', function($join) { 
                $join->on('employee_contacts.created_at', DB::raw('(SELECT MAX(employee_contacts.created_at) FROM employee_contacts WHERE employee_contacts.employee_id = employees.id)')); 
            })
            ->select([
                'employees.id',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.birthdate',
                'employees.religion',
                'employees.civil_status',
                'employees.gender',
                'employees.referrer_id',
                'employees.image',
                'employees.current_status_mark',
                'employees.remarks',
                'employees.created_at',
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
                'employee_positions.schedule',
                'employee_positions.additional_rate',
                'employee_positions.jobrate_name',
                'employee_positions.salary_structure',
                'employee_positions.job_level',
                'employee_positions.allowance',
                'employee_positions.job_rate',
                'employee_positions.salary',
                'positions.position_name',
                'positions.team',
                'positions.payrate',
                'subunits.subunit_name',
                'departments.department_name',
                'jobbands.jobband_name',
                'divisions.division_name',
                'division_categories.category_name',
                'locations.location_name',
                'companies.company_name',
                // 'jobrates.job_level',
                // 'jobrates.job_rate',
                // 'jobrates.salary_structure',
                // 'jobrates.jobrate_name',
                // 'jobrates.allowance',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_type',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'employee_statuses.hired_date_fix',

                DB::raw('CASE 
                WHEN employee_states.employee_state = "resigned" 
                THEN TIMESTAMPDIFF(YEAR, employee_statuses.hired_date_fix, STR_TO_DATE(employee_states.state_date, "%M %e, %Y"))
                ELSE TIMESTAMPDIFF(YEAR, employee_statuses.hired_date_fix, NOW())
                END AS tenure'),

                'employee_states.employee_state_label',
                'employee_states.employee_state',
                'employee_states.state_date_start',
                'employee_states.state_date_end',
                'employee_states.state_date',
                'employee_states.status_remarks',
                'employee_attainments.attainment',
                'employee_attainments.course',
                'employee_accounts.sss_no',
                'employee_accounts.pagibig_no',
                'employee_accounts.philhealth_no',
                'employee_accounts.tin_no',
                // 'addresses.region',
                // 'addresses.province',
                // 'addresses.municipal',
                // 'addresses.barangay',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                // 'regions.reg_desc',
                // 'provinces.prov_desc',
                // 'municipals.citymun_desc',
                // 'barangays.brgy_desc',
                'employee_contacts.contact_details',
            ])
            ->where('employees.current_status', '=', 'approved');
            // ->where('employees.prefix_id', '!=', 'INACTIVE');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc']) && $request->is_historical_active == 'false') {
            $field = ($field == 'name') ? 'employees.last_name' : 'employees.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $query->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(employees.first_name, ' ', employees.middle_name, ' ', employees.last_name) LIKE '{$value}'");
                });
            }

            if ($request->prefix_id) {
                $query->where('positions.team', '=', $request->prefix_id);
            }

            if ($request->id_number) {
                $query->where('employees.id_number', '=', $request->id_number);
            }

            if ($request->manpower_details) {
                $employees = $query->where('employees.manpower_details', '=', $request->manpower_details);
            }

            if ($request->daterange) {
                $daterange = explode('-', $request->daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $query->whereBetween('employee_statuses.hired_date_fix', [$dateFrom, $dateTo]);
            }

            if ($request->status && $request->status == 'ACTIVE') {
                if ($request->type && ($request->type != 'EXTENDED' && $request->type != 'SUSPENDED' && $request->type != 'MATERNITY')) {
                    $employees = $query->where('employee_statuses.employment_type_label', '=', $request->type);
                } else if ($request->type && ($request->type == 'EXTENDED' || $request->type == 'SUSPENDED' || $request->type == 'MATERNITY')) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    // $employees = $query->where(function ($q) use ($inactive) {
                    //     $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    //         ->orWhereNull('employee_states.employee_state');
                    // })
                    // ->orWhere(function ($q) use ($inactive) {
                    //     $q->whereIn('employee_states.employee_state_label', $inactive)
                    //         ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                    // })
                    // ->orWhereNull('employee_states.employee_state');

                    $employees = $query->where(function ($q) use ($inactive) {
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
                    });
                }
            } 
            
            if ($request->status && $request->status == 'INACTIVE') {
                if ($request->type) {
                    $employees = $query->where('employee_states.employee_state_label', '=', $request->type);
                } else {
                    $inactive = [
                        'TERMINATED',
                        'RESIGNED',
                        'ABSENT WITHOUT LEAVE',
                        'END OF CONTRACT',
                        'BLACKLISTED',
                        'DISMISSED',
                        'DECEASED',
                        'BACK OUT',
                        'RETURNED TO AGENCY'
                    ];

                    $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                        ->where(function ($query) {
                            $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                        });
                }
            }

            $query->orderBy($field, $sort)->groupBy('employees.id');
        
        
            if ($request->department) {
                $employees = $query->where('departments.department_name', '=', $request->department);
            }
    
            if ($request->position_name) {
                $employees = $query->where('positions.position_name', '=', $request->position_name);
            }
        }

        if ($request->is_historical_active == 'true' && $request->historical_employee_id) {

            $employee_id = $request->historical_employee_id;
            $employee_data = DB::table('employees')->where('employees.id', '=', $employee_id)
                ->leftJoin('employee_accounts', 'employees.id', '=', 'employee_accounts.employee_id')
                ->first();

            $query->where('employees.first_name', '=', $employee_data->first_name)
                ->where('employees.last_name', '=', $employee_data->last_name)
                ->where('employee_accounts.sss_no', '=', $employee_data->sss_no);

            $query->orderBy('employees.last_name', 'DESC')->groupBy('employees.id');
        }

        $count = $query->count();
        $filename = 'employeedata-export.xlsx';
        $link = ($count) ? '/storage/files/'.$filename : null;

        // dd($query->get());

        if ($count) {
            $employee_export = new EmployeeDataExport($query, $fields);
            $employee_export->store('public/files/'.$filename);
        }

        return response()->json([
            'link' => $link,
            'count' => $count
        ]);
    }

    public function getBindedManpower(Request $request) {
        $query = DB::table('employees')
            ->select(['manpower_details'])
            ->where('employees.manpower_id', '!=', 0)
            ->whereNotNull('employees.manpower_id')
            ->pluck('manpower_details');

        return $query;
    }
}
