<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\EmployeeState as ResourcesEmployeeState;
use App\Http\Resources\EmployeeStateAttachment as ResourcesEmployeeStateAttachment;
use App\Models\Employee;
use App\Models\EmployeeState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeStateController extends Controller
{
    public function index() {
        
    }

    public function show($id) {

    }

    public function store(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => 'required',
            'employee_state' => 'required'
        ]);

        $state = $request->input('employee_state');

        if ($state && ($state != 'extended' && $state != 'suspended' && $state != 'maternity')) {
            $this->validate($request, [
                'state_date' => 'required'
            ]);
        }

        if ($state && ($state == 'extended' || $state == 'suspended' && $state == 'maternity')) {
            $this->validate($request, [
                'state_date_start' => 'required',
                'state_date_end' => 'required',
            ]);
        }

        $latest_employment = DB::table('employee_statuses')
            ->select([
                'employee_statuses.id',
                'employee_statuses.employee_id',
                'employee_statuses.employment_type',
                'employee_statuses.employment_date_start',
                'employee_statuses.employment_date_end',
                'employee_statuses.regularization_date'
            ])
            ->where('employee_id', '=', $request->employee_id)
            ->latest('created_at')
            ->first();

        $latest_state = DB::table('employee_states')
            ->select([
                'employee_states.id',
                'employee_states.employee_id',
                'employee_states.employee_state',
                'employee_states.state_date_start',
                'employee_states.state_date_end',
                'employee_states.state_date'
            ])
            ->where('employee_id', '=', $request->employee_id)
            ->latest('created_at')
            ->first();

        if ($latest_state && $latest_state->employee_state != 'extended' && $latest_state->employee_state != 'suspended' && $latest_state->employee_state != 'maternity' && $latest_state->employee_state != 'under_evaluation' && $latest_state->employee_state != 'returned' && $latest_state->employee_state != 'evaluated_regular') {
            throw ValidationException::withMessages([
                'employee_state' => ['Employee is not available for Extension / Suspension or any other status.']
            ]);
        }

        if ($latest_employment->employment_type == 'regular' && $state == 'extended') {
            throw ValidationException::withMessages([
                'employee_state' => ['REGULAR (Employment Type) Employee cannot be extended.']
            ]);
        }

        if ($latest_state && $state == 'extended') {
            $latest_state_date_end = ($latest_state->state_date_end) ? $latest_state->state_date_end : $latest_state->state_date;
            $latest_state_date_end_converted = strtotime($latest_state_date_end);
            $request_date_start = strtotime($request->state_date_start);
    
            if ($latest_state_date_end_converted > $request_date_start) {
                throw ValidationException::withMessages([
                    'state_date_start' => ['There was a conflict in employee record. Date start should NOT be behind: '.$latest_state_date_end]
                ]);
            }
        }

        if ($latest_state && ($state != 'extended' && $state != 'suspended' && $state != 'maternity')) {
            $latest_state_date_end = ($latest_state->state_date_start) ? $latest_state->state_date_start : $latest_state->state_date;
            $latest_state_date_end_converted = strtotime($latest_state_date_end);
            $request_date = strtotime($request->state_date);
    
            if ($latest_state_date_end_converted > $request_date) {
                throw ValidationException::withMessages([
                    'state_date' => ['There was a conflict in employee record. Date should NOT be behind: '.$latest_state_date_end]
                ]);
            }
        }

        if (!$latest_state && $state == 'extended') {
            $latest_employment_date_end = ($latest_employment->employment_date_end) ? $latest_employment->employment_date_end : $latest_employment->regularization_date;
            $latest_employment_date_end_converted = strtotime($latest_employment_date_end);
            $request_date_start = strtotime($request->state_date_start);
    
            if ($latest_employment_date_end_converted > $request_date_start) {
                throw ValidationException::withMessages([
                    'state_date_start' => ['There was a conflict in employee record. Date start should NOT be behind: '.$latest_employment_date_end]
                ]);
            }
        }

        if (!$latest_state && ($state != 'extended' && $state != 'suspended' && $state != 'maternity')) {
            $latest_employment_date_end = ($latest_employment->employment_date_start) ? $latest_employment->employment_date_start : $latest_employment->regularization_date;
            $latest_employment_date_end_converted = strtotime($latest_employment_date_end);
            $request_date = strtotime($request->state_date);
    
            if ($latest_employment_date_end_converted > $request_date) {
                throw ValidationException::withMessages([
                    'state_date' => ['There was a conflict in employee record. Date should NOT be behind: '.$latest_employment_date_end]
                ]);
            }
        }

        // if ($latest_state && $state == 'suspended') {
        //     $latest_state_date_end = date('F d, Y');
        //     $latest_state_date_end_converted = strtotime($latest_state_date_end);
        //     $request_date_start = strtotime($request->state_date_start);
    
        //     if ($latest_state_date_end_converted > $request_date_start) {
        //         throw ValidationException::withMessages([
        //             'state_date_start' => ['There was a conflict in employee record. Date start should NOT be behind: '.$latest_state_date_end]
        //         ]);
        //     }
        // }

        // if (!$latest_state && $state == 'suspended') {
        //     $latest_employment_date_end = date('F d, Y');
        //     $latest_employment_date_end_converted = strtotime($latest_employment_date_end);
        //     $request_date_start = strtotime($request->state_date_start);
    
        //     if ($latest_employment_date_end_converted > $request_date_start) {
        //         throw ValidationException::withMessages([
        //             'state_date_start' => ['There was a conflict in employee record. Date start should NOT be behind: '.$latest_employment_date_end]
        //         ]);
        //     }
        // }

        if ($state == 'extended') {
            $range_date_start = Carbon::parse($request->state_date_start);
            $range_date_end = Carbon::parse($request->state_date_end);
            $months = $range_date_end->diffInMonths($range_date_start);

            if ($months > 3) {
                throw ValidationException::withMessages([
                    'state_date_end' => ['Maximum of 3 months extension is allowed.']
                ]);
            }

            if ($months < 1) {
                throw ValidationException::withMessages([
                    'state_date_end' => ['Minimum of 1 month extension is allowed.']
                ]);
            }
        }

        $employee_state = new EmployeeState();

        $filenames = [];
        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/status_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $employee_state->attachment = implode(',', $filenames);
            }
        }

        $employee_state->employee_id = $request->input('employee_id');
        $employee_state->employee_state_label = $request->input('employee_state_label');
        $employee_state->employee_state = $request->input('employee_state');
        $employee_state->state_date_start = $request->input('state_date_start');
        $employee_state->state_date_end = $request->input('state_date_end');
        $employee_state->state_date = ($state == 'extended' || $state == 'suspended' || $state == 'maternity') ? $request->input('state_date_start') : $request->input('state_date');
        $employee_state->status_remarks = ($request->input('status_remarks')) ? $request->input('status_remarks') : '';

        if ($employee_state->save()) {
            Helpers::LogActivity($employee_state->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - EMPLOYEE STATUS SECTION');

            $employee_data = Employee::findOrFail($employee_state->employee_id);
            $reference_id = $employee_data->code;
            $record_id = $employee_state->id;
            $record_type = 'employee-status';
            $employee_id = $employee_state->employee_id;
            $details = '';

            $details = 'EMPLOYEE STATUS: '.$employee_state->employee_state_label.' | EFFECTIVITY DATE: '.$employee_state->state_date;

            if ($employee_state->employee_state == 'extended' || $employee_state->employee_state == 'suspended' || $employee_state->employee_state == 'maternity') {
                $details = 'EMPLOYEE STATUS: '.$employee_state->employee_state_label.' | DATE START: '.$employee_state->state_date_start.' | DATE END: '.$employee_state->state_date_end;
            }

            Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);

            return new ResourcesEmployeeState($employee_state);
        }
    }

    public function update(Request $request, $id) {
        
    }

    public function destroy($id) {
        $state = EmployeeState::findOrFail($id);

        if ($state->delete()) {
            Helpers::LogActivity($state->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - EMPLOYEE STATUS SECTION');

            DB::table('job_history')
                ->where('record_id', '=', $state->id)
                ->where('record_type', '=', 'employee-status')
                ->where('employee_id', '=', $state->employee_id)
                ->delete();

            return new ResourcesEmployeeState($state);
        }
    }

    public function getStates($employee_id) {
        $states = DB::table('employee_states')
            ->leftJoin('employees', 'employee_states.employee_id', '=', 'employees.id')
            ->select([
                'employee_states.id',
                'employee_states.employee_id',
                'employee_states.employee_state_label',
                'employee_states.employee_state',
                'employee_states.state_date_start',
                'employee_states.state_date_end',
                'employee_states.state_date',
                'employee_states.created_at',
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
            ->orderBy('employee_states.created_at', 'desc')
            ->get();

        return ResourcesEmployeeState::collection($states);
    }

    public function getStatesWithAttachment($employee_id) {
        $states = DB::table('employee_states')
            ->leftJoin('employees', 'employee_states.employee_id', '=', 'employees.id')
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
                'employee_states.created_at',
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
            ->orderBy('employee_states.created_at', 'desc')
            ->get();

        return ResourcesEmployeeStateAttachment::collection($states);
    }
}
