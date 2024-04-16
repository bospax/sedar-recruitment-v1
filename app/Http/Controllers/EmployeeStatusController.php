<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\EmployeeStatus as ResourcesEmployeeStatus;
use App\Models\Employee;
use App\Models\EmployeeState;
use App\Models\EmployeeStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeStatusController extends Controller
{
    public function index() {
        
    }

    public function show($id) {

    }

    public function store(Request $request) {
        date_default_timezone_set('Asia/Manila');
        
        $this->validate($request, [
            'employee_id' => 'required',
            'employment_type' => 'required'
        ]);

        $employee_status = new EmployeeStatus();
        $employment_type = $request->input('employment_type');

        $duplicate = DB::table('employee_statuses')->select(['employment_type'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('employment_type', '=', $request->input('employment_type'))
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'employment_type' => ['The employment type cannot be duplicated.']
            ]);
        }

        if ($employment_type == 'regular') {
            $this->validate($request, [
                'regularization_date' => 'required'
            ]);

            $employee_status->employee_id = $request->input('employee_id');
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('regularization_date');
            $employee_status->employment_date_end = '--';
            $employee_status->regularization_date = $request->input('regularization_date');
            $employee_status->hired_date = $request->input('hired_date');
            $employee_status->hired_date_fix = Carbon::parse($request->input('hired_date'));
            $employee_status->reminder = Carbon::parse($request->input('hired_date'));

            if (empty($request->input('hired_date'))) {
                $employee_status->hired_date = $request->input('regularization_date');
                $employee_status->hired_date_fix = Carbon::parse($request->input('regularization_date'));
            }
        } else {
            $check_status = DB::table('employee_statuses')->select(['employment_type'])
                ->where('employee_id', '=', $request->input('employee_id'))
                ->get();

            if ($check_status->count()) {
                throw ValidationException::withMessages([
                    'employment_type' => ['Conflict with employment status. Please check the status history.']
                ]);
            }
            
            $this->validate($request, [
                'employment_date_start' => 'required',
                'employment_date_end' => 'required'
            ]);

            $validate_date = DB::table('employee_statuses')
                ->where('employee_id', '=', $request->input('employee_id'))
                ->latest('employment_date_end')->first();

            if ($validate_date) {
                $mindate = $validate_date->employment_date_end;
                $mindate = date('MM-dd-yyyy', strtotime($mindate));
                $date_to_validate = date('MM-dd-yyyy', strtotime($request->input('employment_date_start')));

                if ($date_to_validate > $mindate) {
                    throw ValidationException::withMessages([
                        'employment_date_start' => ['Conflict with date started. Please check the status history.']
                    ]);
                }
            }

            $employee_status->employee_id = $request->input('employee_id');
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('employment_date_start');
            $employee_status->employment_date_end = $request->input('employment_date_end');
            $employee_status->regularization_date = '--';
            $employee_status->hired_date = $request->input('employment_date_start');
            $employee_status->hired_date_fix = Carbon::parse($request->input('employment_date_start'));
            $employee_status->reminder = Carbon::parse($request->input('employment_date_start'));
        }

        if ($employee_status->save()) {
            Helpers::LogActivity($employee_status->id, 'EMPLOYEE MANAGEMENT', 'ADDED EMPLOYEE DATA - EMPLOYMENT TYPE SECTION');

            $employee_data = Employee::findOrFail($employee_status->employee_id);
            $reference_id = $employee_data->code;
            $record_id = $employee_status->id;
            $record_type = 'employment-type';
            $employee_id = $employee_status->employee_id;
            $details = '';

            if ($employee_status->employment_type == 'probationary') {
                $details = 'UNDER PROBATIONARY EVALUATION | DATE START: '.$employee_status->employment_date_start.' | DATE END: '.$employee_status->employment_date_end;
            }

            if ($employee_status->employment_type == 'regular') {
                $details = 'EMPLOYMENT UPDATED TO REGULAR | REGULARIZATION DATE: '.$employee_status->regularization_date;
            }

            if ($employee_status->employment_type != 'regular' && $employee_status->employment_type != 'probationary') {
                $details = 'EMPLOYMENT TYPE: '.$employee_status->employment_type_label.' | DATE START: '.$employee_status->employment_date_start.' | DATE END: '.$employee_status->employment_date_end;
            }

            Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);

            if ($employee_status->employment_type == 'probationary') {
                $employee_state = new EmployeeState();
                $employee_state->employee_id = $employee_id;
                $employee_state->employee_state_label = 'UNDER EVALUATION (PROBATIONARY)';
                $employee_state->employee_state = 'under_evaluation';
                $employee_state->state_date_start = $employee_status->employment_date_start;
                $employee_state->state_date_end = $employee_status->employment_date_end;
                $employee_state->state_date = $employee_status->employment_date_start;
                $employee_state->save();
            }

            if ($employee_status->employment_type == 'regular') {
                $employee_state = new EmployeeState();
                $employee_state->employee_id = $employee_id;
                $employee_state->employee_state_label = 'EVALUATED TO REGULAR';
                $employee_state->employee_state = 'evaluated_regular';
                $employee_state->state_date_start = $employee_status->regularization_date;
                $employee_state->state_date_end = '';
                $employee_state->state_date = $employee_status->regularization_date;
                $employee_state->save();
            }

            return new ResourcesEmployeeStatus($employee_status);
        }
    }

    public function update(Request $request, $id) {
        $this->validate($request, [
            'employee_id' => 'required',
            'employment_type' => 'required'
        ]);

        $employee_status = EmployeeStatus::findOrFail($id);
        $employment_type = $request->input('employment_type');

        $duplicate = DB::table('employee_statuses')->select(['employment_type'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('employment_type', '=', $request->input('employment_type'))
            ->where('id', '!=', $id)
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'employment_type' => ['The employment type cannot be duplicated.']
            ]);
        }

        if ($employment_type == 'regular') {
            $this->validate($request, [
                'regularization_date' => 'required'
            ]);

            $employee_status->employee_id = $request->input('employee_id');
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('regularization_date');
            $employee_status->employment_date_end = '--';
            $employee_status->regularization_date = $request->input('regularization_date');
            $employee_status->hired_date = $request->input('hired_date');
            $employee_status->hired_date_fix = Carbon::parse($request->input('hired_date'));
            $employee_status->reminder = Carbon::parse($request->input('hired_date'));

            if (empty($request->input('hired_date'))) {
                $employee_status->hired_date = $request->input('regularization_date');
                $employee_status->hired_date_fix = Carbon::parse($request->input('regularization_date'));
            }
        } else {
            $check_status = DB::table('employee_statuses')->select(['id', 'employment_type'])
                ->where('employee_id', '=', $request->input('employee_id'))
                ->where('employment_type', '!=', 'regular')
                ->where('id', '!=', $id)
                ->get();

            if ($check_status->count()) {
                throw ValidationException::withMessages([
                    'employment_type' => ['Conflict with employment status. Please check the status history.']
                ]);
            }
            
            $this->validate($request, [
                'employment_date_start' => 'required',
                'employment_date_end' => 'required'
            ]);

            $validate_date = DB::table('employee_statuses')
                ->where('employee_id', '=', $request->input('employee_id'))
                ->where('id', '!=', $id)
                ->latest('employment_date_end')->first();

            if ($validate_date) {
                $mindate = $validate_date->employment_date_end;
                $mindate = date('MM-dd-yyyy', strtotime($mindate));
                $date_to_validate = date('MM-dd-yyyy', strtotime($request->input('employment_date_start')));

                if ($date_to_validate > $mindate) {
                    throw ValidationException::withMessages([
                        'employment_date_start' => ['Conflict with date started. Please check the status history.']
                    ]);
                }
            }

            $employee_status->employee_id = $request->input('employee_id');
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('employment_date_start');
            $employee_status->employment_date_end = $request->input('employment_date_end');
            $employee_status->regularization_date = '--';
            $employee_status->hired_date = $request->input('employment_date_start');
            $employee_status->hired_date_fix = Carbon::parse($request->input('employment_date_start'));
            $employee_status->reminder = Carbon::parse($request->input('employment_date_start'));
        }

        if ($employee_status->save()) {
            Helpers::LogActivity($employee_status->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - EMPLOYMENT TYPE SECTION');

            $status = DB::table('employee_statuses')
                ->leftJoin('employees', 'employee_statuses.employee_id', '=', 'employees.id')
                ->select([
                    'employee_statuses.id',
                    'employee_statuses.employee_id',
                    'employee_statuses.employment_type_label',
                    'employee_statuses.employment_type',
                    'employee_statuses.employment_date_start',
                    'employee_statuses.employment_date_end',
                    'employee_statuses.regularization_date',
                    'employee_statuses.hired_date',
                    'employee_statuses.created_at',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image'
                ])
                ->where('employee_statuses.id', '=', $employee_status->id)
                ->get();

            return ResourcesEmployeeStatus::collection($status);
        }
    }

    public function destroy($id) {
        $status = EmployeeStatus::findOrFail($id);

        if ($status->delete()) {
            Helpers::LogActivity($status->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - EMPLOYMENT TYPE SECTION');

            DB::table('job_history')
                ->where('record_id', '=', $status->id)
                ->where('record_type', '=', 'employment-type')
                ->where('employee_id', '=', $status->employee_id)
                ->delete();

            if ($status->employment_type == 'probationary') {
                DB::table('employee_states')
                    ->where('employee_state_label', '=', 'UNDER EVALUATION (PROBATIONARY)')
                    ->where('employee_id', '=', $status->employee_id)
                    ->delete();
            }

            if ($status->employment_type == 'regular') {
                DB::table('employee_states')
                    ->where('employee_state', '=', 'evaluated_regular')
                    ->where('employee_id', '=', $status->employee_id)
                    ->delete();
            }

            return new ResourcesEmployeeStatus($status);
        }
    }

    public function getStatus($employee_id) {
        $status = DB::table('employee_statuses')
            ->leftJoin('employees', 'employee_statuses.employee_id', '=', 'employees.id')
            ->select([
                'employee_statuses.id',
                'employee_statuses.employee_id',
                'employee_statuses.employment_type_label',
                'employee_statuses.employment_type',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date',
                'employee_statuses.hired_date',
                'employee_statuses.created_at',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image'
            ])
            ->where('employee_id', '=', $employee_id)
            ->orderBy('employee_statuses.created_at', 'desc')
            ->get();

        return ResourcesEmployeeStatus::collection($status);
    }

    public function getDateHired($employee_id) 
    {
        if ($employee_id == 'null') {
            throw ValidationException::withMessages([
                'employee_id' => ['The employee id field is required.']
            ]);
        }

        $status = DB::table('employee_statuses')
            ->select(['hired_date'])
            ->where('employee_id', '=', $employee_id)
            ->latest('created_at')
            ->first();

        return response()->json($status);
    }
}
