<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\EmployeesExport;
use App\Http\Requests\EmployeeRequest;
use App\Http\Resources\Employee as EmployeeResources;
use App\Http\Resources\EmployeeApprover as EmployeeApproverResources;
use App\Http\Resources\ProbationaryEmployee as ProbationaryEmployeeResources;
use App\Http\Resources\DaEvaluationEmployee as DaEvaluationEmployeeResources;
use App\Http\Resources\EmployeeDetails;
use App\Http\Resources\EmployeeMinified;
use App\Models\Employee;
use App\Models\FormHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'emp.first_name' : 'emp.created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $employees = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->select([
                        'emp.id',
                        'emp.prefix_id',
                        'emp.id_number',
                        'emp.first_name',
                        'emp.middle_name',
                        'emp.last_name',
                        'emp.suffix',
                        'emp.birthdate',
                        'emp.religion',
                        'emp.civil_status',
                        'emp.gender',
                        'emp.referrer_id',
                        'emp.image',
                        'emp.current_status_mark',
                        'emp.remarks',
                        'emp.created_at',
                        'ref.prefix_id AS r_prefix_id',
                        'ref.id_number AS r_id_number',
                        'ref.first_name AS r_first_name',
                        'ref.middle_name AS r_middle_name',
                        'ref.last_name AS r_last_name',
                        'ref.suffix AS r_suffix',
                        'emp.manpower_id',
                        'emp.manpower_details',
                    ])
                    ->where('emp.first_name', 'LIKE', $value)
                    ->where('emp.current_status', '=', 'approved')
                    ->orderBy($field, $sort)
                    ->paginate(100);
            } else {
                $employees = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->select([
                        'emp.id',
                        'emp.prefix_id',
                        'emp.id_number',
                        'emp.first_name',
                        'emp.middle_name',
                        'emp.last_name',
                        'emp.suffix',
                        'emp.birthdate',
                        'emp.religion',
                        'emp.civil_status',
                        'emp.gender',
                        'emp.referrer_id',
                        'emp.image',
                        'emp.current_status_mark',
                        'emp.remarks',
                        'emp.created_at',
                        'ref.prefix_id AS r_prefix_id',
                        'ref.id_number AS r_id_number',
                        'ref.first_name AS r_first_name',
                        'ref.middle_name AS r_middle_name',
                        'ref.last_name AS r_last_name',
                        'ref.suffix AS r_suffix',
                        'emp.manpower_id',
                        'emp.manpower_details',
                    ])
                    ->orderBy($field, $sort)
                    ->where('emp.current_status', '=', 'approved')
                    ->paginate(100);
            }
        }

        return EmployeeResources::collection($employees);
    }

    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        return new EmployeeResources($employee);
    }

    public function store(EmployeeRequest $request)
    {
        date_default_timezone_set('Asia/Manila');
        
        $employee = new Employee();
        $employee->prefix_id = $request->input('prefix_id');
        $employee->id_number = $request->input('id_number');
        $employee->first_name = $request->input('first_name');
        $employee->middle_name = ($request->input('middle_name')) ? $request->input('middle_name') : '';
        $employee->last_name = $request->input('last_name');
        $employee->suffix = ($request->input('suffix')) ? $request->input('suffix') : '';
        $employee->birthdate = strtoupper($request->input('birthdate'));
        // $employee->age = $request->input('age');
        $employee->religion = $request->input('religion');
        $employee->civil_status = $request->input('civil_status');
        $employee->gender = $request->input('gender');
        $employee->remarks = ($request->input('remarks')) ? $request->input('remarks') : '';
        $employee->referrer_id = ($request->input('referrer_id')) ? $request->input('referrer_id') : null;
        $employee->form_type = 'employee-registration';
        $employee->level = 1;
        $employee->current_status = 'for-approval';
        $employee->current_status_mark = 'FOR APPROVAL';
        $employee->requestor_id = auth()->user()->employee_id;

        if ($request->hasFile('employee_image')) {
            $filenameWithExt = $request->file('employee_image')->getClientOriginalName();
            $fileNameToStore = time().'_'.$filenameWithExt;
            $path = $request->file('employee_image')->storeAs('public/employee_images', $fileNameToStore);
        } else {
            $fileNameToStore = 'noimage.png';
        }

        $employee->image = $fileNameToStore;

        if ($employee->save()) {
            $form_history = new FormHistory();
            // $form_history->code = $employee->code;
            $form_history->form_id = $employee->id;
            $form_history->form_type = $employee->form_type;
            $form_history->form_data = $employee->toJson();
            $form_history->status = $employee->current_status;
            $form_history->status_mark = $employee->current_status_mark;
            $form_history->reviewer_id = $employee->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->remarks = $employee->remarks;
            $form_history->level = $employee->level;
            $form_history->requestor_id = $employee->requestor_id;
            $form_history->employee_id = $employee->id;
            $form_history->is_fulfilled = 'PENDING';
            $form_history->description = 'REQUESTING FOR REGISTRATION APPROVAL';
            $form_history->save();

            Helpers::LogActivity($employee->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA');
            return new EmployeeResources($employee);
        }
    }

    public function update(Request $request, $id)
    {
        $duplicate = DB::table('employees')
            ->select(['id'])
            ->where('prefix_id', '=', $request->prefix_id)
            ->where('id_number', '=', $request->id_number)
            ->where('id', '!=', $id)
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'id_number' => ['The id number has already been taken.']
            ]);
        }

        $this->validate($request, [
            'prefix_id' => ['required'],
            // 'id_number' => ['required', 'numeric', 'unique:emp\()&\/.,loyees,id_number,'.$id],
            'first_name' => ['required'],
            'middle_name' => ['nullable', 'regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'last_name' => ['required', 'regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'suffix' => ['nullable','regex:/^[0-9\pL\s\()&\/,_-]+$/u'],
            'birthdate' => ['required'],
            'religion' => ['required'],
            'civil_status' => ['required'],
            'gender' => ['required']
        ]);

        $employee = Employee::findOrFail($id);

        // dd($employee);
        $employee->prefix_id = $request->input('prefix_id');
        $employee->id_number = $request->input('id_number');
        $employee->first_name = $request->input('first_name');
        $employee->middle_name = (!$request->input('middle_name') || $request->input('middle_name') == 'null') ? '' : $request->input('middle_name');
        $employee->last_name = $request->input('last_name');
        $employee->suffix = (!$request->input('suffix') || $request->input('suffix') == 'null') ? '' : $request->input('suffix');
        $employee->birthdate = strtoupper($request->input('birthdate'));
        // $employee->age = $request->input('age');
        $employee->religion = $request->input('religion');
        $employee->civil_status = $request->input('civil_status');
        $employee->gender = $request->input('gender');
        $employee->remarks = (!$request->input('remarks') || $request->input('remarks') == 'null') ? '' : $request->input('remarks');
        $employee->referrer_id = ($request->input('referrer_id')) ? $request->input('referrer_id') : null;

        $employee->manpower_id = (int) $request->input('manpower_id');
        $employee->manpower_details = $request->input('manpower_details');

        if ($request->hasFile('employee_image')) {
            $filenameWithExt = $request->file('employee_image')->getClientOriginalName();
            $fileNameToStore = time().'_'.$filenameWithExt;
            $path = $request->file('employee_image')->storeAs('public/employee_images', $fileNameToStore);
            $employee->image = $fileNameToStore;
        }

        // // return the updated or newly added article
        if ($employee->save()) {
            $form_history = FormHistory::where('form_id', $employee->id)
            ->where('form_type', 'employee-registration')
            ->first();

            if ($form_history) {
                $form_history->form_id = $employee->id;
                $form_history->form_type = $employee->form_type;
                $form_history->form_data = $employee->toJson();
                $form_history->status = $employee->current_status;
                $form_history->status_mark = $employee->current_status_mark;
                $form_history->reviewer_id = $employee->requestor_id;
                $form_history->review_date = Carbon::now();
                $form_history->remarks = $employee->remarks;
                $form_history->level = $employee->level;
                $form_history->requestor_id = $employee->requestor_id;
                $form_history->employee_id = $employee->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'REQUESTING FOR REGISTRATION APPROVAL';
                $form_history->save();
            }

            Helpers::LogActivity($employee->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - GENERAL SECTION');

            $employee = DB::table('employees AS emp')
                ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                ->select([
                    'emp.id',
                    'emp.prefix_id',
                    'emp.id_number',
                    'emp.first_name',
                    'emp.middle_name',
                    'emp.last_name',
                    'emp.suffix',
                    'emp.birthdate',
                    'emp.religion',
                    'emp.civil_status',
                    'emp.gender',
                    'emp.referrer_id',
                    'emp.image',
                    'emp.current_status_mark',
                    'emp.remarks',
                    'emp.created_at',
                    'ref.prefix_id AS r_prefix_id',
                    'ref.id_number AS r_id_number',
                    'ref.first_name AS r_first_name',
                    'ref.middle_name AS r_middle_name',
                    'ref.last_name AS r_last_name',
                    'ref.suffix AS r_suffix',
                    'emp.manpower_id',
                    'emp.manpower_details',
                ])
                ->where('emp.id', '=', $employee->id)
                ->get();

            return EmployeeResources::collection($employee);
        }
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        if ($employee->delete()) {
            DB::table('employee_positions')
                ->where('employee_id', '=', $id)
                ->delete();

            // employment type 
            DB::table('employee_statuses')
                ->where('employee_id', '=', $id)
                ->delete();

            // status 
            DB::table('employee_states')
                ->where('employee_id', '=', $id)
                ->delete();

            // address 
            DB::table('addresses')
                ->where('employee_id', '=', $id)
                ->delete();

            // contacts
            DB::table('employee_contacts')
                ->where('employee_id', '=', $id)
                ->delete();

            // attainment 
            DB::table('employee_attainments')
                ->where('employee_id', '=', $id)
                ->delete();

            // account 
            DB::table('employee_accounts')
                ->where('employee_id', '=', $id)
                ->delete();

            // files 
            DB::table('employee_files')
                ->where('employee_id', '=', $id)
                ->delete();

            // job history
            DB::table('job_history')
                ->where('employee_id', '=', $id)
                ->delete();

            // form history
            DB::table('form_history')
                ->where('employee_id', '=', $id)
                ->delete();

            // users 
            DB::table('users')
                ->where('employee_id', '=', $id)
                ->delete();

            // forms 
            DB::table('forms')
                ->where('employee_id', '=', $id)
                ->delete();

            // monthly evaluation 
            DB::table('monthly_evaluations')
                ->where('employee_id', '=', $id)
                ->delete();

            // probi evaluation
            DB::table('probi_evaluations')
                ->where('employee_id', '=', $id)
                ->delete();

            // annual evaluation 
            DB::table('annual_evaluations')
                ->where('employee_id', '=', $id)
                ->delete();

            // datachange
            $query = DB::table('datachange_forms')
                ->where('employee_id', '=', $id);

            // $datachange = $query->get();

            $transaction_statuses = DB::table('transaction_statuses')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', 'employee-registration')
                ->delete();

            $transaction_approvers = DB::table('transaction_approvers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', 'employee-registration')
                ->delete();

            $transaction_receivers = DB::table('transaction_receivers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', 'employee-registration')
                ->delete();

            // dd($datachange);
            
            $query->delete();

            // merit increase 
            DB::table('merit_increase_forms')
                ->where('employee_id', '=', $id)
                ->delete();

            // da forms
            DB::table('da_forms')
                ->where('employee_id', '=', $id)
                ->delete();

            // da evaluations
            DB::table('da_evaluations')
                ->where('employee_id', '=', $id)
                ->delete();

            // receivers
            DB::table('receivers')
                ->where('employee_id', '=', $id)
                ->delete();

            Helpers::LogActivity($employee->id, 'EMPLOYEE MANAGEMENT', 'DELETED ALL EMPLOYEE RECORD');
            return new EmployeeResources($employee);
        }
    }

    public function export() 
    {
        $query = DB::table('employees')->select(['id', 'first_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'employees-exportall.xlsx';
        $employee_export = new EmployeesExport($query);
        $employee_export->store('public/files/'.$filename);
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

            $query = DB::table('employees')->select(['id', 'first_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'employees-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $employee_export = new EmployeesExport($query);
                $employee_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'first_name' : 'created_at';            
            $employees = DB::table('employees')->select(['id', 'first_name', 'created_at'])->orderBy($field, $sort)->paginate(15);

            return EmployeeResources::collection($employees);
        }
    }

    public function getEmployees() 
    {
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

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
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
                'employees.manpower_id',
                'employees.manpower_details',
            ])
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', 'AGENCY');
        }

        $employees = $query
            ->orderBy('employees.first_name', 'desc')
            ->get();
            
        return EmployeeResources::collection($employees);
    }

    public function getEmployeeTenure(Request $request, $id)
    {
        $query = DB::table('employees')
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
            ->where('employees.id', '=', $id)
            ->first();
        
        $query = ($id) ? $query : [];

        return response()->json($query);
    }

    public function getEmployeesMinified() 
    {
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

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
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
            ])
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where('employees.current_status', '=', 'approved');

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        $employees = $query
            ->orderBy('employees.last_name', 'asc')
            ->get();
            
        return EmployeeMinified::collection($employees);
    }

    public function getEmployeesFiltered(Request $request) 
    {
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

        $filter_status = $request->input('filter_status');

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
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
            ])
            ->where('employees.current_status', '=', 'approved');

        if ($filter_status == 'ACTIVE') {
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

        if ($filter_status == 'INACTIVE') {
            $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                ->where(function ($query) {
                    $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                });
        }

        if (Helpers::checkPermission('agency_only')) {
            $employees = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
        }

        $employees = $query
            ->orderBy('employees.last_name', 'asc')
            ->get();
            
        return EmployeeMinified::collection($employees);
    }

    public function getEmployeeDetails(Request $request) 
    {
        $employee_id = $request->input('employee_id');

        $employee = DB::table('employees')
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
                'employees.created_at'
            ])
            ->where('employees.id', '=', $employee_id)
            ->first();
            
        // return EmployeeDetails::collection($employee);
        return response()->json($employee);
    }

    public function getEmployeesForApprover() 
    {
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

        $employees = DB::table('employees AS emp')
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = emp.id)')); 
            })
            ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'emp.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->select([
                'emp.id',
                'emp.prefix_id',
                'emp.id_number',
                'emp.first_name',
                'emp.middle_name',
                'emp.last_name',
                'emp.suffix',
                'emp.birthdate',
                'emp.religion',
                'emp.civil_status',
                'emp.gender',
                'emp.referrer_id',
                'emp.image',
                'emp.current_status_mark',
                'emp.remarks',
                'emp.created_at',
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'positions.position_name',
                'departments.department_name'
            ])
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->orderBy('emp.first_name', 'desc')
            ->get();
            
        return EmployeeApproverResources::collection($employees);
    }

    public function getProbi() 
    {
        $superior_id = Auth::user()->employee_id;
        // $superior_position_id = DB::table('employee_positions')
        //     ->select('position_id')
        //     ->where('employee_id', '=', $superior_id)
        //     ->get()
        //     ->first();

        // $superior_position_id = ($superior_position_id) ? $superior_position_id->position_id : '';

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

        $probies = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('monthly_evaluations', 'employees.id', '=', 'monthly_evaluations.employee_id')
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
                'employee_statuses.employment_type',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'positions.id AS position_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name'
            ])
            ->where('employees.current_status', '=', 'approved')
            ->where('employee_statuses.employment_type', '=', 'probationary')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            // ->where(function ($query) {
            //     $query->where('monthly_evaluations.is_fulfilled', '=', 'filed')
            //         ->where('monthly_evaluations.month', '=', 3)
            //         ->where('monthly_evaluations.current_status', '=', 'approved');
            // })
            ->where('positions.superior', '=', $superior_id)
            ->whereNotIn('employees.id', DB::table('probi_evaluations')->where('probi_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->get();
        
        return ProbationaryEmployeeResources::collection($probies);
    }

    public function getMo()
    {
        $superior_id = Auth::user()->employee_id;
        // $superior_position_id = DB::table('employee_positions')
        //     ->select('position_id')
        //     ->where('employee_id', '=', $superior_id)
        //     ->get()
        //     ->first();
        
        // $superior_position_id = ($superior_position_id) ? $superior_position_id->position_id : '';
        $target_date = Carbon::now()->subMonth(1);
        // $target_date = date('F d, Y', strtotime("-60 days"));
        // dd($target_date);

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

        $mon = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
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
                'employee_statuses.employment_type',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'positions.id AS position_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name',
                'employee_states.employee_state',
            ])
            ->where('employees.current_status', '=', 'approved')
            ->where('employee_statuses.employment_type', '!=', 'regular')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->whereDate('employee_statuses.reminder', '<=', $target_date)
            ->where('positions.superior', '=', $superior_id)
            // ->whereNotIn('employees.id', DB::table('monthly_evaluations')->where('monthly_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->whereNotIn('employees.id', DB::table('probi_evaluations')->pluck('employee_id'))
            ->where(function ($query) {
                $query->where(DB::raw("(SELECT MAX(monthly_evaluations.month) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<', 3)
                    ->orWhereNull(DB::raw("(SELECT MAX(monthly_evaluations.month) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<', 3);
            })
            // ->where(function ($query) use ($target_date) {
            //     $query->where(DB::raw("(SELECT MAX(monthly_evaluations.created_at) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<=', $target_date)
            //         ->orWhereNull(DB::raw("(SELECT MAX(monthly_evaluations.created_at) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<=', $target_date);
            // })
            ->get();
        
        return ProbationaryEmployeeResources::collection($mon);
    }

    public function getAnnual() 
    {
        $superior_id = Auth::user()->employee_id;
        // $superior_position_id = DB::table('employee_positions')
        //     ->select('position_id')
        //     ->where('employee_id', '=', $superior_id)
        //     ->get()
        //     ->first();

        // dd($superior_position_id);

        // $superior_position_id = ($superior_position_id) ? $superior_position_id->position_id : '';

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

        $annual = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
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
                'employee_statuses.employment_type',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'positions.id AS position_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name'
            ])
            ->where('current_status', '=', 'approved')
            ->where('employee_statuses.employment_type', '=', 'regular')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where('positions.superior', '=', $superior_id)
            ->whereNotIn('employees.id', DB::table('annual_evaluations')->where('annual_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->get();
        
        return ProbationaryEmployeeResources::collection($annual);
    }

    public function getEmployeeGeneralInfo($id) {
        $employees = DB::table('employees AS emp')
            ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
            ->select([
                'emp.id',
                'emp.prefix_id',
                'emp.id_number',
                'emp.first_name',
                'emp.middle_name',
                'emp.last_name',
                'emp.suffix',
                'emp.birthdate',
                'emp.religion',
                'emp.civil_status',
                'emp.gender',
                'emp.referrer_id',
                'emp.image',
                'emp.current_status_mark',
                'emp.remarks',
                'emp.created_at',
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'emp.manpower_id',
                'emp.manpower_details',
            ])
            ->where('emp.current_status', '=', 'approved')
            ->where('emp.id', '=', $id)
            ->get();

        return EmployeeResources::collection($employees);
    }

    public function reminderForEvaluation() {
        $reminders = [];
        $superior_id = Auth::user()->employee_id;
        $superior_position = DB::table('employee_positions')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employee_positions.position_id',
                'positions.subunit_id',
            ])
            ->where('employee_positions.employee_id', '=', $superior_id)
            ->get()
            ->first();

        $superior_position_id = ($superior_position) ? $superior_position->position_id : '';
        $subunit_id = ($superior_position) ? $superior_position->subunit_id : '';
        $da_max_level = DB::table('forms')
            ->where('form_type', '=', 'manpower-form')
            ->where('subunit_id', '=', $subunit_id)
            ->max('level');
        
        $da_max_level = ($da_max_level) ? $da_max_level : 0;

        $target_date = Carbon::now()->subMonth(1);
        $probi_target_date = Carbon::now()->subMonth(5);

        $monthly = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
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
                'employee_statuses.employment_type',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'positions.id AS position_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name',
                'employee_states.employee_state',
            ])
            ->where('employees.current_status', '=', 'approved')
            ->where('employee_statuses.employment_type', '!=', 'regular')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->whereDate('employee_statuses.reminder', '<=', $target_date)
            ->where('positions.superior', '=', $superior_id)
            ->whereNotIn('employees.id', DB::table('monthly_evaluations')->where('monthly_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->whereNotIn('employees.id', DB::table('probi_evaluations')->pluck('employee_id'))
            ->where(function ($query) {
                $query->where(DB::raw("(SELECT MAX(monthly_evaluations.month) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<', 3)
                    ->orWhereNull(DB::raw("(SELECT MAX(monthly_evaluations.month) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<', 3);
            })
            ->where(function ($query) use ($target_date) {
                $query->where(DB::raw("(SELECT MAX(monthly_evaluations.created_at) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<=', $target_date)
                    ->orWhereNull(DB::raw("(SELECT MAX(monthly_evaluations.created_at) FROM monthly_evaluations WHERE monthly_evaluations.employee_id = employees.id)"), '<=', $target_date);
            })
            ->get();

        $da = [];
        // if ($da_max_level) {
            $da = DB::table('employees')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('da_forms', 'employees.id', '=', 'da_forms.employee_id')
                ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->leftJoin('employee_states', function($join) { 
                    $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
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
                    'employee_statuses.employment_type',
                    'employee_statuses.employment_type_label',
                    'employee_statuses.employment_date_start',
                    'employee_statuses.employment_date_end',
                    'employee_statuses.regularization_date',
                    'employee_statuses.hired_date',
                    'positions.id as position_id',
                    'positions.position_name',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'da_forms.id as daform_id',
                    'da_forms.inclusive_date_start',
                    'da_forms.inclusive_date_end',
                    'datachange_forms.change_reason',
                    'datachange_forms.change_position_id',
                    'change_position.position_name as change_position_name',
                    'change_department.department_name as change_department_name',
                ])
                // ->where('da_forms.level', '>', $da_max_level)
                ->where('employees.current_status', '=', 'approved')
                ->where('da_forms.current_status', '=', 'approved')
                // ->where('da_forms.is_fulfilled', '!=', 'FILED')
                // ->where('employee_statuses.employment_type', '=', 'regular')
                ->where(function ($query) {
                    $query->where('employee_states.employee_state', '=', 'extended')
                        ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                        ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                        ->orWhereNull('employee_states.employee_state');
                })
                ->where('change_position.superior', '=', $superior_id)
                ->whereNotIn('employees.id', DB::table('da_evaluations')->where('da_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
                ->get();
        // }

        $probies = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            })
            ->leftJoin('monthly_evaluations', 'employees.id', '=', 'monthly_evaluations.employee_id')
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
                'employee_statuses.employment_type',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'positions.id AS position_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name'
            ])
            ->where('employees.current_status', '=', 'approved')
            ->where('employee_statuses.employment_type', '=', 'probationary')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where('positions.superior', '=', $superior_id)
            ->whereDate('employee_statuses.reminder', '<=', $probi_target_date)
            ->whereNotIn('employees.id', DB::table('probi_evaluations')->where('probi_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->get();

        $reminders = [
            // 'monthly' => ProbationaryEmployeeResources::collection($monthly),
            // 'da' => DaEvaluationEmployeeResources::collection($da),
            // 'probi' => ProbationaryEmployeeResources::collection($probies)
        ];
        
        return response()->json($reminders);
    }
}
