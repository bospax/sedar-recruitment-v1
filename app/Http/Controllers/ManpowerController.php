<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\DataChange as DataChangeResource;
use App\Http\Resources\DataChangeDetailed;
use App\Http\Resources\DevelopmentalAssignment as DevelopmentalAssignmentResource;
use App\Http\Resources\FormHistory as FormHistoryResources;
use App\Http\Resources\Manpower as ManpowerResource;
use App\Http\Resources\ManpowerEmployee;
use App\Http\Resources\ManpowerForRegistration;
use App\Http\Resources\ManpowerWithDataChangeDetails;
use App\Http\Resources\ManpowerWithExpectedSalary;
use App\Models\DaEvaluation;
use App\Models\DaForms;
use App\Models\DatachangeForms;
use App\Models\EmployeePosition;
use App\Models\FormHistory;
use App\Models\KPI;
use App\Models\ManpowerForms;
use App\Models\Position;
use App\Models\TransactionApprovers;
use App\Models\TransactionDatachanges;
use App\Models\TransactionReceivers;
use App\Models\TransactionStatuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManpowerController extends Controller
{
    public function getPositions() {
        $positions = [];
        $requestor_id = Auth::user()->employee_id;

        $department_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.department_id'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $department_id = ($department_id) ? $department_id->department_id : '';

        $subunit_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.subunit_id'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $subunit_id = ($subunit_id) ? $subunit_id->subunit_id : '';

        $order = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->select([
                'employees.id',
                'positions.subunit_id',
                'jobbands.jobband_name',
                'jobbands.order',
                'jobbands.subunit_bound'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $order_no = ($order) ? $order->order : '';

        if ($department_id) {
            $query = DB::table('positions')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('kpis', 'positions.id', '=', 'kpis.position_id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'positions.id', 
                    'positions.code',
                    'positions.department_id',
                    'positions.subunit_id',
                    'positions.jobband_id',
                    'positions.position_name',
                    'positions.payrate',
                    'positions.employment',
                    'positions.no_of_months',
                    'positions.schedule',
                    'positions.shift',
                    'positions.team',
                    'positions.job_profile',
                    'positions.attachments',
                    'positions.tools',
                    'positions.superior',
                    'superior.prefix_id AS s_prefix_id',
                    'superior.id_number AS s_id_number',
                    'superior.first_name AS s_first_name',
                    'superior.middle_name AS s_middle_name',
                    'superior.last_name AS s_last_name',
                    'superior.suffix AS s_suffix',
                    'positions.created_at',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'jobbands.jobband_name',
                    'kpis.measures',
                    'jobbands.order'
                ]);
                // ->where('positions.superior', '=', $requestor_id);
                

            if ($order && $order->subunit_bound) {
                $positions = $query
                    // ->where('positions.subunit_id', '=', $subunit_id)
                    // ->where('jobbands.order', '>', $order_no)
                    ->where('positions.status', '=', 'active')
                    ->distinct('positions.id')
                    ->get()
                    ->map(function ($positions) {
                        $positions->full_position_details = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team.' | '.$positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                        return $positions;
                    });
            } else {
                $positions = $query
                    ->where('positions.department_id', '=', $department_id)
                    // ->where('jobbands.order', '>', $order_no)
                    ->where('positions.status', '=', 'active')
                    ->distinct('positions.id')
                    ->get()
                    ->map(function ($positions) {
                        $positions->full_position_details = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team.' | '.$positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                        return $positions;
                    });
            }
        }

        // dd($positions);

        return response()->json($positions);
    }

    public function getSubordinateEmployees(Request $request) {
        $requestor_id = Auth::user()->employee_id;
        $position_id = $request->position_id;
        $subordinates = [];

        $department_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.department_id'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $department_id = ($department_id) ? $department_id->department_id : '';

        $subunit_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([ 
                'employees.id',
                'positions.subunit_id'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $subunit_id = ($subunit_id) ? $subunit_id->subunit_id : '';

        $order = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->select([
                'employees.id',
                'positions.subunit_id',
                'jobbands.jobband_name',
                'jobbands.order',
                'jobbands.subunit_bound'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $order_no = ($order) ? $order->order : '';

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
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
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
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'positions.subunit_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.order',
                'jobbands.subunit_bound',
                'jobbands.jobband_name',
                'employee_positions.position_id',
                'employee_positions.additional_rate',
                'employee_positions.jobrate_name',
                'employee_positions.salary_structure',
                'employee_positions.job_level',
                'employee_positions.allowance',
                'employee_positions.job_rate',
                'employee_positions.salary',
            ])
            // ->where('jobbands.order', '>', $order_no)
            // ->where('positions.department_id', '=', $department_id)
            ->where('positions.id', '=', $position_id)
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
            // ->whereNotIn('employees.id', DB::table('datachange_forms')->where('datachange_forms.current_status', '!=', 'approved')->pluck('employee_id'))
            // ->whereNotIn('employees.id', DB::table('merit_increase_forms')->where('merit_increase_forms.current_status', '!=', 'approved')->pluck('employee_id'))
            // ->whereNotIn('employees.id', DB::table('da_evaluations')->where('da_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->where('employees.current_status', '!=', 'for-approval');

        // if ($order && $order->subunit_bound) {
        //     $subordinates = $query->where('positions.subunit_id', '=', $subunit_id)->get();
        // } else {
        //     $subordinates = $query->get();
        // }

        $subordinates = $query->get();

        return ManpowerEmployee::collection($subordinates);
    }

    public function getEmployeeReplacement(Request $request) {
        $position_id = $request->position_id;
        $requisition_type = $request->requisition_type;

        $replacement = [];

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
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
                'ref.prefix_id AS r_prefix_id',
                'ref.id_number AS r_id_number',
                'ref.first_name AS r_first_name',
                'ref.middle_name AS r_middle_name',
                'ref.last_name AS r_last_name',
                'ref.suffix AS r_suffix',
                'positions.subunit_id',
                'positions.position_name',
                'departments.department_name',
                'jobbands.order',
                'jobbands.subunit_bound',
                'jobbands.jobband_name',
                'employee_positions.position_id',
                'employee_states.employee_state',
                'subunits.subunit_name',
                'employee_positions.additional_rate',
                'employee_positions.jobrate_name',
                'employee_positions.salary_structure',
                'employee_positions.job_level',
                'employee_positions.allowance',
                'employee_positions.job_rate',
                'employee_positions.salary',
            ])
            ->where('employee_positions.position_id', '=', $position_id)
            // ->where(function ($query) {
            //     $query->where('employee_states.employee_state', '=', 'extended')
            //         ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
            //         ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
            //         ->orWhereNull('employee_states.employee_state');
            // })
            ->whereNotIn('employees.id', DB::table('manpower_forms')->whereNotNull('replacement_for')->pluck('replacement_for'))
            ->where('employees.current_status', '!=', 'for-approval');

        if ($requisition_type == 'replacement_resignation') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'resigned');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        if ($requisition_type == 'replacement_endo') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'endo');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        if ($requisition_type == 'replacement_termination') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'terminated');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        if ($requisition_type == 'replacement_awol') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'awol');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        if ($requisition_type == 'replacement_return') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'returned');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        if ($requisition_type == 'replacement_blacklisted') {
            $replacement = $query->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'blacklisted')
                    ->orWhere('employee_states.employee_state', '=', 'dismissed')
                    ->orWhere('employee_states.employee_state', '=', 'deceased')
                    ->orWhere('employee_states.employee_state', '=', 'backout');
                    // ->orWhereNull('employee_states.employee_state');
            });
        }

        $replacement = $query->get();

        return ManpowerEmployee::collection($replacement);
    }

    public function getPositionsChange(Request $request) {
        $positions = [];
        $requestor_id = $request->employee_id;
        $reason = $request->change_reason;

        $department_id = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.id',
                'positions.department_id',
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        $department_id = ($department_id) ? $department_id->department_id : '';

        $order = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->select([
                'employees.id',
                'employee_positions.position_id',
                'positions.subunit_id',
                'jobbands.jobband_name',
                'jobbands.order',
                'jobbands.subunit_bound',
                'positions.position_name'
            ])
            ->where('employee_positions.employee_id', '=', $requestor_id)
            ->get()
            ->first();

        if ($department_id) {
            $query = DB::table('positions')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('kpis', 'positions.id', '=', 'kpis.position_id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'positions.id', 
                    'positions.code',
                    'positions.department_id',
                    'positions.subunit_id',
                    'positions.jobband_id',
                    'positions.position_name',
                    'positions.payrate',
                    'positions.employment',
                    'positions.no_of_months',
                    'positions.schedule',
                    'positions.shift',
                    'positions.team',
                    'positions.job_profile',
                    'positions.attachments',
                    'positions.tools',
                    'positions.superior',
                    'superior.prefix_id AS s_prefix_id',
                    'superior.id_number AS s_id_number',
                    'superior.first_name AS s_first_name',
                    'superior.middle_name AS s_middle_name',
                    'superior.last_name AS s_last_name',
                    'superior.suffix AS s_suffix',
                    'positions.created_at',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'jobbands.jobband_name',
                    'kpis.measures',
                    'jobbands.order'
                ]);
                

            if ($reason == 'promotion') {
                $positions = $query
                    ->where('positions.department_id', '=', $department_id)
                    ->where('positions.status', '!=', 'inactive')
                    // ->where('jobbands.order', '<', $order->order)
                    // ->where('positions.position_name', '!=', $order->position_name)
                    ->where('positions.id', '!=', $order->position_id)
                    ->get()
                    ->map(function ($positions) {
                        $positions->full_position_details = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team.' | '.$positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                        return $positions;
                    });
            } elseif ($reason == 'transfer' || $reason == 'promotion_transfer') {
                $positions = $query
                    ->where('positions.id', '!=', $order->position_id)
                    ->where('positions.status', '!=', 'inactive')
                    // ->where('positions.position_name', '!=', $order->position_name)
                    ->get()
                    ->map(function ($positions) {
                        $positions->full_position_details = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team.' | '.$positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                        return $positions;
                    });
            }
        }

        return response()->json($positions);
    }

    public function storeManpower(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $requestor_id = Auth::user()->employee_id;

        $rules = [
            'position_id' => ['required'],
            // 'jobrate_id' => ['required'],
            'manpower_count' => ['required'],
            'employment_type' => ['required'],
            'salary_structure' => ['required'],
            'expected_salary' => ['required']
        ];

        if ($request->requisition_type == 'additional') {
            $rules = array_merge($rules, [
                'justification' => ['required'],
            ]);
        }

        if ($request->requisition_type == 'replacement_resignation' || $request->requisition_type == 'replacement_termination' || $request->requisition_type == 'replacement_awol' || $request->requisition_type == 'replacement_endo' || $request->requisition_type == 'replacement_blacklisted' || $request->requisition_type == 'replacement_return') {
            $rules = array_merge($rules, [
                'replacement_for' => ['required'],
            ]);
        }

        // if ($request->requisition_type == 'replacement_movement') {
        //     $rules = array_merge($rules, [
        //         'datachange_salary_structure' => ['required'],
        //         'job_rate' => ['required'],
        //         'salary' => ['required'],
        //     ]);
        // }

        if ($request->for_da == 'true') {
            $rules = array_merge($rules, [
                'inclusive_date_start' => ['required'],
                'inclusive_date_end' => ['required'],
            ]);
        }

        $this->validate($request, $rules);

        $now = strtotime(date('F d, Y'));
        $date_start = strtotime($request->input('inclusive_date_start'));

        if ($now > $date_start) {
            throw ValidationException::withMessages([
                'inclusive_date_start' => ['The date you selected already passed.']
            ]);
        }

        $manpower = new ManpowerForms();
        $manpower->code = Helpers::generateCodeNewVersion('manpower_forms', 'MRF');
        $manpower->position_id = $request->input('position_id');
        $manpower->jobrate_id = $request->input('jobrate_id');

        $manpower->expected_salary = $request->input('expected_salary');
        $structures = explode('|', $request->input('salary_structure'));
        $job_level = trim($structures[0]);
        $salary_structure = trim($structures[1]);
        $jobrate_name = trim($structures[2]);

        $manpower->jobrate_name = $jobrate_name;
        $manpower->salary_structure = $salary_structure;
        $manpower->job_level = $job_level;

        $manpower->manpower_count = $request->input('manpower_count');
        $manpower->employment_type = $request->input('employment_type');
        $manpower->employment_type_label = $request->input('employment_type_label');
        $manpower->requisition_type = $request->input('requisition_type');
        $manpower->requisition_type_mark = $request->input('requisition_type_mark');
        
        $filenames = [];

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/manpower_attachments', $fileNameToStore);
                $path = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $manpower->attachment = implode(',', $filenames);
            }
        }

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;
        $approvers = [];

        if ($isLoggedIn) { 
            $current_user = Auth::user();

            $requestor_position = DB::table('employee_positions')
                ->select(['position_id'])
                ->where('employee_id', '=', $current_user->employee_id)
                ->get()
                ->first();

            $data = DB::table('positions')
                ->select(['id', 'subunit_id'])
                ->where('positions.id', '=', $requestor_position->position_id)
                ->first();

            $approver = DB::table('forms')
                ->select(['id'])
                ->where('form_type', '=', 'manpower-form')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            $approvers = DB::table('forms')
                ->select([
                    'form_type',
                    'batch',
                    'label',
                    'subunit_id',
                    'employee_id',
                    'action',
                    'level',
                    'receiver_id'
                ])
                ->where('form_type', '=', 'manpower-form')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            $receivers = DB::table('receivers')
                ->select([
                    'form_type',
                    'batch',
                    'label',
                    'subunit_id',
                    'employee_id',
                ])
                ->where('form_type', '=', 'manpower-form')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        // dd($receivers);

        $manpower->justification = ($request->input('justification')) ? $request->input('justification') : '';
        $manpower->replacement_for = $request->input('replacement_for');
        $manpower->form_type = 'manpower-form';
        $manpower->level = ($isApprover) ? 2 : 1;
        $manpower->current_status = 'for-approval';
        $manpower->current_status_mark = 'FOR APPROVAL';
        $manpower->requestor_id = $requestor_id;
        $manpower->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $manpower->is_fulfilled = 'PENDING';

        // dd($manpower);
        
        if ($manpower->save()) {
            Helpers::LogActivity($manpower->id, 'FORM REQUISITION - MANPOWER FORM', 'REQUESTED A NEW MANPOWER FORM');

            $form_history = new FormHistory();
            $form_history->form_id = $manpower->id;
            $form_history->code = $manpower->code;
            $form_history->form_type = $manpower->form_type;
            $form_history->form_data = $manpower->toJson();
            $form_history->status = ($isApprover) ? 'approved' : $manpower->current_status;
            $form_history->status_mark = ($isApprover) ? 'APPROVED' : $manpower->current_status_mark;
            $form_history->reviewer_id = $manpower->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $manpower->requestor_remarks;
            $form_history->level = ($isApprover) ? 2 : 1;
            $form_history->requestor_id = $manpower->requestor_id;
            $form_history->is_fulfilled = $manpower->is_fulfilled;
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->description = 'REQUESTING FOR MANPOWER APPROVAL';
            $form_history->save();

            $transaction_statuses = new TransactionStatuses();
            $transaction_statuses->form_id = $manpower->id;
            $transaction_statuses->code = $manpower->code;
            $transaction_statuses->form_type = $manpower->form_type;
            $transaction_statuses->form_data = $manpower->toJson();
            $transaction_statuses->status = ($isApprover) ? 'approved' : $manpower->current_status;
            $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $manpower->current_status_mark;
            $transaction_statuses->reviewer_id = $manpower->requestor_id;
            $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
            $transaction_statuses->reviewer_action = 'assess';
            $transaction_statuses->remarks = $manpower->requestor_remarks;
            $transaction_statuses->level = ($isApprover) ? 2 : 1;
            $transaction_statuses->requestor_id = $manpower->requestor_id;
            $transaction_statuses->is_fulfilled = $manpower->is_fulfilled;
            $transaction_statuses->reviewer_attachment = implode(',', $filenames);
            $transaction_statuses->description = 'REQUESTING FOR MANPOWER APPROVAL';
            $transaction_statuses->save();

            $data_approvers = [];
            $approvers = $approvers->toArray();

            foreach ($approvers as $form) {
                $data_approvers[] = [
                    'transaction_id' => $manpower->id,
                    'form_type' => $form->form_type,
                    'batch' => $form->batch,
                    'label' => $form->label,
                    'subunit_id' => $form->subunit_id,
                    'employee_id' => $form->employee_id,
                    'action' => $form->action,
                    'level' => $form->level,
                    'receiver_id' => $form->receiver_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TransactionApprovers::insert($data_approvers);

            $data_receivers = [];
            $receivers = $receivers->toArray();

            foreach ($receivers as $form) {
                $data_receivers[] = [
                    'transaction_id' => $manpower->id,
                    'form_type' => $form->form_type,
                    'batch' => $form->batch,
                    'label' => $form->label,
                    'subunit_id' => $form->subunit_id,
                    'employee_id' => $form->employee_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            TransactionReceivers::insert($data_receivers);

            if ($request->input('requisition_type') == 'replacement_movement') {
                $datachange = new DatachangeForms();
                $datachange->employee_id = $request->input('employee_id');
                $datachange->change_position_id = $request->input('change_position_id');
                $datachange->change_reason = $request->input('change_reason');
                $datachange->for_da = $request->input('for_da');
                $datachange->manpower_id = $manpower->id;
                $datachange->form_type = 'datachange-form';
                $datachange->level = ($isApprover) ? 2 : 1;
                $datachange->current_status = 'for-approval';
                $datachange->current_status_mark = 'FOR APPROVAL';
                $datachange->requestor_id = $manpower->requestor_id;
                $datachange->requestor_remarks = $request->input('requestor_remarks');
                $datachange->is_fulfilled = 'PENDING';

                // $structures = explode('|', $request->input('datachange_salary_structure'));
                // $job_level = trim($structures[0]);
                // $salary_structure = trim($structures[1]);
                // $jobrate_name = trim($structures[2]);
                // $additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
                // $allowance = (float) str_replace(',', '', $request->input('allowance'));
                // $job_rate = (float) str_replace(',', '', $request->input('job_rate'));
                // $salary = (float) str_replace(',', '', $request->input('salary'));

                // $datachange->jobrate_name = $jobrate_name;
                // $datachange->salary_structure = $salary_structure;
                // $datachange->job_level = $job_level;
                $datachange->additional_rate = 0;
                $datachange->allowance = 0;
                $datachange->job_rate = 0;
                $datachange->salary = 0;

                if ($datachange->save()) {
                    $current_employee_data = EmployeePosition::where('employee_id', $datachange->employee_id)->first();
                    $current_position_data = Position::findOrFail($current_employee_data->position_id);

                    $transaction_datachanges = new TransactionDatachanges();
                    $transaction_datachanges->transaction_id = $datachange->id;
                    $transaction_datachanges->form_type = $datachange->form_type;
                    $transaction_datachanges->employee_id = $datachange->employee_id;
                    $transaction_datachanges->position_id = $current_position_data->id;
                    $transaction_datachanges->department_id = $current_position_data->department_id;
                    $transaction_datachanges->subunit_id = $current_position_data->subunit_id;
                    $transaction_datachanges->division_id = $current_employee_data->division_id;
                    $transaction_datachanges->division_cat_id = $current_employee_data->division_cat_id;
                    $transaction_datachanges->company_id = $current_employee_data->company_id;
                    $transaction_datachanges->location_id = $current_employee_data->location_id;
                    $transaction_datachanges->schedule = $current_employee_data->schedule;
                    $transaction_datachanges->jobrate_name = $current_employee_data->jobrate_name;
                    $transaction_datachanges->salary_structure = $current_employee_data->salary_structure;
                    $transaction_datachanges->job_level = $current_employee_data->job_level;
                    $transaction_datachanges->additional_rate = $current_employee_data->additional_rate;
                    $transaction_datachanges->allowance = $current_employee_data->allowance;
                    $transaction_datachanges->job_rate = $current_employee_data->job_rate;
                    $transaction_datachanges->salary = $current_employee_data->salary;

                    $transaction_datachanges->save();

                    if ($request->input('for_da') == 'true') {
                        $kpi_metrics = KPI::where('position_id', $request->input('change_position_id'))->first()->measures;
    
                        $da = new DaForms();
                        $da->code = Helpers::generateCodeNewVersion('da_forms', 'DA');
                        $da->employee_id = $request->input('employee_id');
                        $da->datachange_id = $datachange->id;
                        $da->inclusive_date_start = Carbon::parse($request->input('inclusive_date_start'));
                        $da->inclusive_date_end = Carbon::parse($request->input('inclusive_date_end'));
                        $da->measures = json_encode($kpi_metrics);
                        $da->form_type = 'da-form';
                        $da->level = ($isApprover) ? 2 : 1;
                        $da->current_status = 'for-approval';
                        $da->current_status_mark = 'FOR APPROVAL';
                        $da->requestor_id = $manpower->requestor_id;
                        $da->requestor_remarks = $request->input('requestor_remarks');
                        $da->is_confirmed = 'PENDING';
                        $da->is_fulfilled = 'PENDING';
                        $da->save();
                    }
                }
            }

            return response()->json($manpower);
        }
    }

    public function updateManpower(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $rules = [];
        $requestor_id = Auth::user()->employee_id;

        if ($request->section == 'manpower') {
            $rules = [
                'position_id' => ['required'],
                // 'jobrate_id' => ['required'],
                'manpower_count' => ['required', 'lte:15'],
                'employment_type' => ['required'],
                'salary_structure' => ['required'],
                'expected_salary' => ['required']
            ];
    
            if ($request->requisition_type == 'additional') {
                $rules = array_merge($rules, [
                    'justification' => ['required'],
                ]);
            }
    
            if ($request->requisition_type == 'replacement_resignation' || $request->requisition_type == 'replacement_termination' || $request->requisition_type == 'replacement_awol' || $request->requisition_type == 'replacement_endo') {
                $rules = array_merge($rules, [
                    'replacement_for' => ['required'],
                ]);
            }
        }

        // if ($request->section == 'datachange') {
        //     $rules = array_merge($rules, [
        //         'datachange_salary_structure' => ['required'],
        //         'salary' => ['required'],
        //         'job_rate' => ['required'],
        //     ]);
        // }
        

        if ($request->section == 'da') {
            $rules = array_merge($rules, [
                'inclusive_date_start' => ['required'],
                'inclusive_date_end' => ['required'],
            ]);
        }

        $this->validate($request, $rules);

        $now = strtotime(date('F d, Y'));
        $date_start = strtotime($request->input('inclusive_date_start'));

        if ($now > $date_start && $request->section == 'da') {
            throw ValidationException::withMessages([
                'inclusive_date_start' => ['The date you selected already passed.']
            ]);
        }

        if ($request->section == 'manpower') {
            $manpower = ManpowerForms::findOrFail($request->input('active_data_id'));
            $manpower->position_id = $request->input('position_id');
            $manpower->jobrate_id = $request->input('jobrate_id');
            $manpower->manpower_count = $request->input('manpower_count');
            $manpower->expected_salary = $request->input('expected_salary');
            $manpower->employment_type = $request->input('employment_type');
            $manpower->employment_type_label = $request->input('employment_type_label');

            $structures = explode('|', $request->input('salary_structure'));
            $manpower->job_level = trim($structures[0]);
            $manpower->salary_structure = trim($structures[1]);
            $manpower->jobrate_name = trim($structures[2]);
            
            $filenames = [];

            if ($request->hasFile('attachments')) {
                $files = $request->attachments;

                foreach ($files as $file) {
                    $filenameWithExt = $file->getClientOriginalName();
                    $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                    $path = $file->storeAs('public/manpower_attachments', $fileNameToStore);
                    $filenames[] = str_replace(',', '', $fileNameToStore);
                }

                if ($filenames) {
                    $manpower->attachment = implode(',', $filenames);
                }
            }

            $manpower->justification = ($request->input('justification')) ? $request->input('justification') : '';
            $manpower->replacement_for = $request->input('replacement_for');
            $manpower->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';

            if ($manpower->save()) {
                Helpers::LogActivity($manpower->id, 'FORM REQUISITION - MANPOWER FORM', 'UPDATED MANPOWER FORM DATA');
            }
        }

        if ($request->section == 'datachange') {
            $datachange = DatachangeForms::findOrFail($request->input('active_data_id'));
            $datachange->employee_id = $request->input('employee_id');
            $datachange->change_position_id = $request->input('change_position_id');
            $datachange->change_reason = $request->input('change_reason');

            // $structures = explode('|', $request->input('datachange_salary_structure'));
            // $job_level = trim($structures[0]);
            // $salary_structure = trim($structures[1]);
            // $jobrate_name = trim($structures[2]);

            // $datachange->jobrate_name = $jobrate_name;
            // $datachange->salary_structure = $salary_structure;
            // $datachange->job_level = $job_level;
            // $datachange->additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
            // $datachange->allowance = (float) str_replace(',', '', $request->input('allowance'));
            // $datachange->job_rate = (float) str_replace(',', '', $request->input('job_rate'));
            // $datachange->salary = (float) str_replace(',', '', $request->input('salary'));

            $datachange->additional_rate = 0;
            $datachange->allowance = 0;
            $datachange->job_rate = 0;
            $datachange->salary = 0;

            if ($datachange->save()) {
                Helpers::LogActivity($datachange->id, 'FORM REQUISITION - MANPOWER FORM', 'UPDATED MANPOWER DATA - DATA CHANGE FORM SECTION');
            }
        }

        if ($request->section == 'da') {
            $da = DaForms::findOrFail($request->input('active_data_id'));
            $da->inclusive_date_start = Carbon::parse($request->input('inclusive_date_start'));
            $da->inclusive_date_end = Carbon::parse($request->input('inclusive_date_end'));
            
            if ($da->save()) {
                Helpers::LogActivity($da->id, 'FORM REQUISITION - MANPOWER FORM', 'UPDATED MANPOWER DATA - DA FORM SECTION');
            }
        }
    }

    public function cancelManpower(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $form_type = 'manpower-form';
        $form_id = $id;

        $filenames = [];

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }
        }

        if ($form_type == 'manpower-form') {
            $review = 'cancelled';
            $review_mark = 'CANCELLED';
            $remarks = '';

            $manpower = ManpowerForms::findOrFail($form_id);
            $manpower->level = 1;
            $manpower->current_status = $review;
            $manpower->current_status_mark = $review_mark;
            $manpower->is_fulfilled = 'CANCELLED';

            if ($manpower->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR MANPOWER FORM - STATUS: '.$review_mark;
                Helpers::LogActivity($manpower->id, 'FORM REQUEST - MANPOWER FORM', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $manpower->id;
                $form_history->code = $manpower->code;
                $form_history->form_type = $manpower->form_type;
                $form_history->form_data = $manpower->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $manpower->level;
                $form_history->requestor_id = $manpower->requestor_id;
                $form_history->employee_id = $manpower->employee_id;
                $form_history->is_fulfilled = $manpower->is_fulfilled;
                $form_history->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $manpower->id;
                $transaction_statuses->code = $manpower->code;
                $transaction_statuses->form_type = $manpower->form_type;
                $transaction_statuses->form_data = $manpower->toJson();
                $transaction_statuses->status = $review;
                $transaction_statuses->status_mark = $review_mark;
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $manpower->level;
                $transaction_statuses->requestor_id = $manpower->requestor_id;
                $transaction_statuses->employee_id = $manpower->employee_id;
                $transaction_statuses->is_fulfilled = $manpower->is_fulfilled;
                $transaction_statuses->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();

                $datachange = DatachangeForms::where('manpower_id', '=', $manpower->id)->first();
                
                if ($datachange) {
                    $datachange->level = 1;
                    $datachange->current_status = $review;
                    $datachange->current_status_mark = $review_mark;
                    $datachange->is_fulfilled = 'CANCELLED';
                    $datachange->save();
                    
                    $da = DaForms::where('datachange_id', '=', $datachange->id)->first();
                    
                    if ($da) {
                        $da->level = 1;
                        $da->current_status = $review;
                        $da->current_status_mark = $review_mark;
                        $da->is_fulfilled = 'CANCELLED';
                        $da->save();

                        $evaluation = DB::table('da_evaluations')
                            ->where('daform_id', '=', $da->id);
                        
                        $evaluation_count = $evaluation->get()->count();

                        if ($evaluation_count) {
                            $de = $evaluation->get()->first();

                            $review = 'cancelled';
                            $review_mark = 'CANCELLED';

                            $evaluation = DaEvaluation::findOrFail($de->id);
                            $evaluation->level = 1;
                            $evaluation->current_status = $review;
                            $evaluation->current_status_mark = $review_mark;

                            if ($evaluation->save()) {
                                $activity = 'REVIEWED FORM REQUEST FOR DA EVALUATION FORM - STATUS: '.$review_mark;
                                Helpers::LogActivity($evaluation->id, 'FORM REQUEST - DA EVALUATION FORM', $activity);

                                $form_history = new FormHistory();
                                $form_history->form_id = $evaluation->id;
                                $form_history->code = $evaluation->code;
                                $form_history->form_type = $evaluation->form_type;
                                $form_history->form_data = $evaluation->toJson();
                                $form_history->status = $review;
                                $form_history->status_mark = $review_mark;
                                $form_history->reviewer_id = Auth::user()->employee_id;
                                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                                $form_history->remarks = $remarks;
                                $form_history->level = $evaluation->level;
                                $form_history->requestor_id = $evaluation->requestor_id;
                                $form_history->employee_id = $evaluation->employee_id;
                                $form_history->is_fulfilled = $evaluation->is_fulfilled;
                                $form_history->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                                $form_history->reviewer_attachment = implode(',', $filenames);
                                $form_history->created_at = now()->addMinutes(3)->toDateTimeString();
                                $form_history->updated_at = now()->addMinutes(3)->toDateTimeString();
                                $form_history->save();

                                $transaction_statuses = new TransactionStatuses();
                                $transaction_statuses->form_id = $evaluation->id;
                                $transaction_statuses->code = $evaluation->code;
                                $transaction_statuses->form_type = $evaluation->form_type;
                                $transaction_statuses->form_data = $evaluation->toJson();
                                $transaction_statuses->status = $review;
                                $transaction_statuses->status_mark = $review_mark;
                                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                                $transaction_statuses->remarks = $remarks;
                                $transaction_statuses->level = $evaluation->level;
                                $transaction_statuses->requestor_id = $evaluation->requestor_id;
                                $transaction_statuses->employee_id = $evaluation->employee_id;
                                $transaction_statuses->is_fulfilled = $evaluation->is_fulfilled;
                                $transaction_statuses->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                                $transaction_statuses->created_at = now()->addMinutes(3)->toDateTimeString();
                                $transaction_statuses->updated_at = now()->addMinutes(3)->toDateTimeString();
                                $transaction_statuses->save();
                            }
                        }
                    }
                }
            }

            return response()->json($manpower);
        }
    }

    public function validateManpower(Request $request) {
        $rules = [
            'position_id' => ['required'],
            // 'jobrate_id' => ['required'],
            'manpower_count' => ['required'],
            'employment_type' => ['required'],
            'expected_salary' => ['required']
        ];

        if ($request->requisition_type == 'additional') {
            $rules = array_merge($rules, [
                'justification' => ['required'],
            ]);
        }

        if ($request->requisition_type == 'replacement_resignation' || $request->requisition_type == 'replacement_termination' || $request->requisition_type == 'replacement_awol' || $request->requisition_type == 'replacement_endo') {
            $rules = array_merge($rules, [
                'replacement_for' => ['required'],
            ]);
        }

        $this->validate($request, $rules);
    }

    public function getManpowers(Request $request) {
        $manpowers = [];
        $keyword = request('keyword');
        $requestor_id = Auth::user()->employee_id;

        $query = DB::table('manpower_forms')
            ->leftJoin('positions', 'manpower_forms.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'manpower_forms.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'requestor.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
            ->leftJoin('departments as requestor_department', 'requestor_position.department_id', '=', 'requestor_department.id')
            ->leftJoin('subunits as requestor_subunit', 'requestor_position.subunit_id', '=', 'requestor_subunit.id')
            ->leftJoin('employees as replacement', 'manpower_forms.replacement_for', '=', 'replacement.id')
            ->leftJoin('departments as position_department', 'positions.department_id', '=', 'position_department.id')
            ->leftJoin('employees as tobehired', 'manpower_forms.tobe_hired', '=', 'tobehired.id')
            ->leftJoin('employee_positions as employee_positions_tobehired', 'tobehired.id', '=', 'employee_positions_tobehired.employee_id')
            ->leftJoin('positions as position_tobehired', 'employee_positions_tobehired.position_id', '=', 'position_tobehired.id')
            ->leftJoin('departments as department_tobehired', 'position_tobehired.department_id', '=', 'department_tobehired.id')
            ->leftJoin('subunits as subunit_tobehired', 'position_tobehired.subunit_id', '=', 'subunit_tobehired.id')

            ->leftJoin('datachange_forms', 'manpower_forms.id', '=', 'datachange_forms.manpower_id')
            ->leftJoin('da_forms', 'datachange_forms.id', '=', 'da_forms.datachange_id')
            ->leftJoin('da_evaluations', 'da_forms.id', '=', 'da_evaluations.daform_id')

            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type)')); 
            })
            ->select([
                'manpower_forms.id',
                'manpower_forms.code',
                'manpower_forms.position_id',
                'manpower_forms.jobrate_id',
                'manpower_forms.manpower_count',
                'manpower_forms.expected_salary',
                'manpower_forms.salary_structure',
                'manpower_forms.jobrate_name',
                'manpower_forms.job_level',
                'manpower_forms.employment_type',
                'manpower_forms.employment_type_label',
                'manpower_forms.requisition_type',
                'manpower_forms.requisition_type_mark',
                'manpower_forms.attachment',
                'manpower_forms.justification',
                'manpower_forms.replacement_for',
                'manpower_forms.form_type',
                'manpower_forms.level',
                'manpower_forms.current_status',
                'manpower_forms.current_status_mark',
                'manpower_forms.requestor_id',
                'manpower_forms.requestor_remarks',
                'manpower_forms.is_fulfilled',
                'manpower_forms.date_fulfilled',
                'manpower_forms.tobe_hired',
                'manpower_forms.created_at',
                'position_department.department_name as position_department',
                'positions.position_name',
                'positions.payrate',
                'positions.shift',
                'positions.schedule',
                'positions.team',
                'positions.jobband_id',
                // 'jobrates.jobrate_name',
                // 'jobrates.job_level',
                // 'jobrates.salary_structure',
                'jobbands.jobband_name',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
                'requestor.first_name',
                'requestor.last_name',
                'requestor_position.position_name as requestor_position',
                'requestor_department.department_name as requestor_department',
                'requestor_subunit.subunit_name as requestor_subunit',
                'replacement.id as replacement_id',
                'replacement.prefix_id as replacement_prefix_id',
                'replacement.id_number as replacement_id_number',
                'replacement.first_name as replacement_first_name',
                'replacement.middle_name as replacement_middle_name',
                'replacement.last_name as replacement_last_name',
                'replacement.suffix as replacement_suffix',
                'tobehired.first_name as tobehired_firstname',
                'tobehired.last_name as tobehired_lastname',
                'position_tobehired.position_name as position_name_tobehired',
                'department_tobehired.department_name as department_name_tobehired',
                'subunit_tobehired.subunit_name as subunit_name_tobehired',
                'form_history.status_mark',
                'form_history.review_date',
                'da_evaluations.code as daevaluation_code',
                'datachange_forms.for_da'
            ]);

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $manpowers = $query
                    ->where('manpower_forms.code', 'LIKE', $value)
                    ->where('manpower_forms.requestor_id', '=', $requestor_id)
                    ->orderBy('manpower_forms.created_at', 'desc')
                    ->paginate(100);
            } else {
                $manpowers = $query
                    ->where('manpower_forms.requestor_id', '=', $requestor_id)
                    ->orderBy('manpower_forms.created_at', 'desc')
                    ->paginate(100);
            }

        // return response()->json($manpowers);
        return ManpowerWithExpectedSalary::collection($manpowers);
    }

    public function getManpowerData(Request $request) {
        $manpower_id = $request->id;
        $section = $request->section;

        switch ($section) {
            case 'manpower':
                $manpower = DB::table('manpower_forms')
                    ->leftJoin('positions', 'manpower_forms.position_id', '=', 'positions.id')
                    ->leftJoin('jobrates', 'manpower_forms.jobrate_id', '=', 'jobrates.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                    ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions', 'requestor.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
                    ->leftJoin('departments as requestor_department', 'requestor_position.department_id', '=', 'requestor_department.id')
                    ->leftJoin('subunits as requestor_subunit', 'requestor_position.subunit_id', '=', 'requestor_subunit.id')
                    ->leftJoin('employees as replacement', 'manpower_forms.replacement_for', '=', 'replacement.id')
                    ->leftJoin('departments as position_department', 'positions.department_id', '=', 'position_department.id')
                    ->leftJoin('employees as tobehired', 'manpower_forms.tobe_hired', '=', 'tobehired.id')
                    ->leftJoin('employee_positions as employee_positions_tobehired', 'tobehired.id', '=', 'employee_positions_tobehired.employee_id')
                    ->leftJoin('positions as position_tobehired', 'employee_positions_tobehired.position_id', '=', 'position_tobehired.id')
                    ->leftJoin('departments as department_tobehired', 'position_tobehired.department_id', '=', 'department_tobehired.id')
                    ->leftJoin('subunits as subunit_tobehired', 'position_tobehired.subunit_id', '=', 'subunit_tobehired.id')

                    ->leftJoin('datachange_forms', 'manpower_forms.id', '=', 'datachange_forms.manpower_id')
                    ->leftJoin('da_forms', 'datachange_forms.id', '=', 'da_forms.datachange_id')
                    ->leftJoin('da_evaluations', 'da_forms.id', '=', 'da_evaluations.daform_id')
                    
                    ->leftJoin('form_history', function($join) { 
                        $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type)')); 
                    })
                    ->select([
                        'manpower_forms.id',
                        'manpower_forms.code',
                        'manpower_forms.position_id',
                        'manpower_forms.jobrate_id',
                        'manpower_forms.manpower_count',
                        'manpower_forms.employment_type',
                        'manpower_forms.employment_type_label',
                        'manpower_forms.requisition_type',
                        'manpower_forms.requisition_type_mark',
                        'manpower_forms.attachment',
                        'manpower_forms.justification',
                        'manpower_forms.replacement_for',
                        'manpower_forms.form_type',
                        'manpower_forms.level',
                        'manpower_forms.current_status',
                        'manpower_forms.current_status_mark',
                        'manpower_forms.requestor_id',
                        'manpower_forms.requestor_remarks',
                        'manpower_forms.is_fulfilled',
                        'manpower_forms.date_fulfilled',
                        'manpower_forms.tobe_hired',
                        'manpower_forms.created_at',
                        'manpower_forms.expected_salary',
                        'manpower_forms.salary_structure',
                        'manpower_forms.jobrate_name',
                        'manpower_forms.job_level',
                        'position_department.department_name as position_department',
                        'positions.position_name',
                        'positions.payrate',
                        'positions.shift',
                        'positions.schedule',
                        'positions.team',
                        'positions.jobband_id',
                        // 'jobrates.jobrate_name',
                        // 'jobrates.job_level',
                        // 'jobrates.salary_structure',
                        'jobbands.jobband_name',
                        'superior.prefix_id AS s_prefix_id',
                        'superior.id_number AS s_id_number',
                        'superior.first_name AS s_first_name',
                        'superior.middle_name AS s_middle_name',
                        'superior.last_name AS s_last_name',
                        'superior.suffix AS s_suffix',
                        'requestor.first_name',
                        'requestor.last_name',
                        'requestor_position.position_name as requestor_position',
                        'requestor_department.department_name as requestor_department',
                        'requestor_subunit.subunit_name as requestor_subunit',
                        'replacement.id as replacement_id',
                        'replacement.prefix_id as replacement_prefix_id',
                        'replacement.id_number as replacement_id_number',
                        'replacement.first_name as replacement_first_name',
                        'replacement.middle_name as replacement_middle_name',
                        'replacement.last_name as replacement_last_name',
                        'replacement.suffix as replacement_suffix',
                        'tobehired.first_name as tobehired_firstname',
                        'tobehired.last_name as tobehired_lastname',
                        'position_tobehired.position_name as position_name_tobehired',
                        'department_tobehired.department_name as department_name_tobehired',
                        'subunit_tobehired.subunit_name as subunit_name_tobehired',
                        'form_history.status_mark',
                        'form_history.review_date',
                        'da_evaluations.code as daevaluation_code',
                        'datachange_forms.for_da',
                        'datachange_forms.additional_rate as new_additional_rate',
                        'datachange_forms.jobrate_name as new_jobrate_name',
                        'datachange_forms.salary_structure as new_salary_structure',
                        'datachange_forms.job_level as new_job_level',
                        'datachange_forms.allowance as new_allowance',
                        'datachange_forms.job_rate as new_job_rate',
                        'datachange_forms.salary as new_salary',
                    ])
                    ->where('manpower_forms.id', '=', $manpower_id)
                    ->get();

                return ManpowerWithDataChangeDetails::collection($manpower);
                break;

            case 'datachange':
                
                $datachange_form = DatachangeForms::where('manpower_id', '=', $manpower_id)->first();
                $da_form = DaForms::where('datachange_id', '=', $datachange_form->id)->first();

                if ($da_form) {
                    $da_evaluation = DaEvaluation::where('daform_id', '=', $da_form->id)->first();
                }

                // if ($da_form && $da_evaluation && $da_evaluation->is_fulfilled == 'FILED') {
                //     $datachange = DB::table('datachange_forms')
                //         ->leftJoin('employees', 'datachange_forms.employee_id', '=', 'employees.id')
                //         ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                //         ->leftJoin('positions', 'datachange_forms.prev_position_id', '=', 'positions.id')
                //         ->leftJoin('departments', 'datachange_forms.prev_department_id', '=', 'departments.id')
                //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                //         ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                //         ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                //         ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                //         ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
                //         ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')
                //         ->select([
                //             'datachange_forms.id',
                //             'datachange_forms.employee_id',
                //             'datachange_forms.change_position_id',
                //             'datachange_forms.change_reason',
                //             'datachange_forms.for_da',
                //             'datachange_forms.manpower_id',
                //             'datachange_forms.form_type',
                //             'datachange_forms.level',
                //             'datachange_forms.current_status',
                //             'datachange_forms.current_status_mark',
                //             'datachange_forms.requestor_id',
                //             'datachange_forms.requestor_remarks',
                //             'datachange_forms.is_fulfilled',
                //             'datachange_forms.date_fulfilled',
                //             'datachange_forms.created_at',
                //             'datachange_forms.additional_rate',
                //             'datachange_forms.jobrate_name',
                //             'datachange_forms.salary_structure',
                //             'datachange_forms.job_level',
                //             'datachange_forms.allowance',
                //             'datachange_forms.job_rate',
                //             'datachange_forms.salary',
                //             'employees.prefix_id',
                //             'employees.id_number',
                //             'employees.first_name',
                //             'employees.middle_name',
                //             'employees.last_name',
                //             'employees.suffix',
                //             'positions.position_name',
                //             'departments.department_name',
                //             'subunits.subunit_name',
                //             'jobbands.jobband_name',
                //             'change_position.position_name as change_position_name',
                //             'change_department.department_name as change_department_name',
                //             'change_subunit.subunit_name as change_subunit_name',
                //             'change_jobband.jobband_name as change_jobband_name',
                //             'employee_positions.additional_rate as prev_additional_rate',
                //             'employee_positions.jobrate_name as prev_jobrate_name',
                //             'employee_positions.salary_structure as prev_salary_structure',
                //             'employee_positions.job_level as prev_job_level',
                //             'employee_positions.allowance as prev_allowance',
                //             'employee_positions.job_rate as prev_job_rate',
                //             'employee_positions.salary as prev_salary',
                            
                //         ])
                //         ->where('manpower_id', '=', $manpower_id)
                //         ->get();
                // } else {
                    $datachange = DB::table('datachange_forms')
                        ->leftJoin('employees', 'datachange_forms.employee_id', '=', 'employees.id')

                        ->leftJoin('transaction_datachanges', function($join) { 
                            $join->on('datachange_forms.form_type', '=', 'transaction_datachanges.form_type'); 
                            $join->on('datachange_forms.id', '=', 'transaction_datachanges.transaction_id'); 
                        })

                        // ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'transaction_datachanges.position_id', '=', 'positions.id')
                        ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                        ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                        ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                        ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                        ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                        ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
                        ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')
                        ->select([
                            'datachange_forms.id',
                            'datachange_forms.employee_id',
                            'datachange_forms.change_position_id',
                            'datachange_forms.change_reason',
                            'datachange_forms.for_da',
                            'datachange_forms.manpower_id',
                            'datachange_forms.form_type',
                            'datachange_forms.level',
                            'datachange_forms.current_status',
                            'datachange_forms.current_status_mark',
                            'datachange_forms.requestor_id',
                            'datachange_forms.requestor_remarks',
                            'datachange_forms.is_fulfilled',
                            'datachange_forms.date_fulfilled',
                            'datachange_forms.created_at',
                            'datachange_forms.additional_rate',
                            'datachange_forms.jobrate_name',
                            'datachange_forms.salary_structure',
                            'datachange_forms.job_level',
                            'datachange_forms.allowance',
                            'datachange_forms.job_rate',
                            'datachange_forms.salary',
                            'employees.prefix_id',
                            'employees.id_number',
                            'employees.first_name',
                            'employees.middle_name',
                            'employees.last_name',
                            'employees.suffix',
                            'positions.position_name',
                            'departments.department_name',
                            'subunits.subunit_name',
                            'jobbands.jobband_name',
                            'change_position.position_name as change_position_name',
                            'change_department.department_name as change_department_name',
                            'change_subunit.subunit_name as change_subunit_name',
                            'change_jobband.jobband_name as change_jobband_name',
                            'transaction_datachanges.additional_rate as prev_additional_rate',
                            'transaction_datachanges.jobrate_name as prev_jobrate_name',
                            'transaction_datachanges.salary_structure as prev_salary_structure',
                            'transaction_datachanges.job_level as prev_job_level',
                            'transaction_datachanges.allowance as prev_allowance',
                            'transaction_datachanges.job_rate as prev_job_rate',
                            'transaction_datachanges.salary as prev_salary',
                        ])
                        ->where('datachange_forms.manpower_id', '=', $manpower_id)
                        ->get();
                // }

                return DataChangeDetailed::collection($datachange);
                break;

            case 'da':
                $datachange = DB::table('datachange_forms')
                    ->select(['id'])
                    ->where('manpower_id', '=', $manpower_id)
                    ->get()
                    ->first();

                $da_form = DaForms::where('datachange_id', '=', $datachange->id)->first();
                $da_evaluation = DaEvaluation::where('daform_id', '=', $da_form->id)->first();

                if ($da_evaluation && $da_evaluation->is_fulfilled == 'FILED') {
                    $da = DB::table('da_forms')
                        ->leftJoin('employees', 'da_forms.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                        ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                        ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                        ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                        ->leftJoin('kpis', 'change_position.id', '=', 'kpis.position_id')
                        ->leftJoin('da_evaluations', 'da_forms.id', '=', 'da_evaluations.daform_id')
                        ->select([
                            'da_forms.id',
                            'da_forms.employee_id',
                            'da_forms.datachange_id',
                            'da_forms.inclusive_date_start',
                            'da_forms.inclusive_date_end',
                            'da_forms.form_type',
                            'da_forms.level',
                            'da_forms.current_status',
                            'da_forms.current_status_mark',
                            'da_forms.requestor_id',
                            'da_forms.requestor_remarks',
                            'da_forms.is_fulfilled',
                            'da_forms.date_fulfilled',
                            'da_forms.created_at',
                            'employees.prefix_id',
                            'employees.id_number',
                            'employees.first_name',
                            'employees.middle_name',
                            'employees.last_name',
                            'employees.suffix',
                            'positions.position_name',
                            'departments.department_name',
                            'change_position.position_name as change_position_name',
                            'change_department.department_name as change_department_name',
                            'da_evaluations.prev_measures as measures',
                            'da_evaluations.total_grade',
                            'da_evaluations.total_target',
                            'da_evaluations.assessment',
                            'da_evaluations.assessment_mark',
                        ])
                        ->where('datachange_id', '=', $datachange->id)
                        ->get();
                } else {
                    $da = DB::table('da_forms')
                        ->leftJoin('employees', 'da_forms.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                        ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                        ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                        ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                        ->leftJoin('kpis', 'change_position.id', '=', 'kpis.position_id')
                        ->select([
                            'da_forms.id',
                            'da_forms.employee_id',
                            'da_forms.datachange_id',
                            'da_forms.measures',
                            'da_forms.inclusive_date_start',
                            'da_forms.inclusive_date_end',
                            'da_forms.form_type',
                            'da_forms.level',
                            'da_forms.current_status',
                            'da_forms.current_status_mark',
                            'da_forms.requestor_id',
                            'da_forms.requestor_remarks',
                            'da_forms.is_fulfilled',
                            'da_forms.date_fulfilled',
                            'da_forms.created_at',
                            'employees.prefix_id',
                            'employees.id_number',
                            'employees.first_name',
                            'employees.middle_name',
                            'employees.last_name',
                            'employees.suffix',
                            'positions.position_name',
                            'departments.department_name',
                            'change_position.position_name as change_position_name',
                            'change_department.department_name as change_department_name',
                            // 'kpis.measures',
                        ])
                        ->where('datachange_id', '=', $datachange->id)
                        ->get();
                }
                
                return DevelopmentalAssignmentResource::collection($da);
                break;

            case 'timeline':
                $form_id = $request->input('id');
                $form_type = 'manpower-form';

                $history = DB::table('form_history')
                    ->leftJoin('employees as reviewer', 'form_history.reviewer_id', '=', 'reviewer.id')
                    ->leftJoin('employee_positions', 'form_history.reviewer_id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->select([
                        'form_history.id',
                        'form_history.form_id',
                        'form_history.form_type',
                        'form_history.form_data',
                        'form_history.status',
                        'form_history.status_mark',
                        'form_history.reviewer_id',
                        'form_history.review_date',
                        'form_history.reviewer_action',
                        'form_history.reviewer_attachment',
                        'form_history.remarks',
                        'form_history.level',
                        'form_history.requestor_id',
                        'form_history.employee_id',
                        'form_history.is_fulfilled',
                        'form_history.date_fulfilled',
                        'form_history.description',
                        'form_history.created_at',
                        'reviewer.prefix_id',
                        'reviewer.id_number',
                        'reviewer.first_name',
                        'reviewer.middle_name',
                        'reviewer.last_name',
                        'reviewer.suffix',
                        'positions.position_name as reviewer_position',
                        'departments.department_name as reviewer_department'
                    ])
                    ->where('form_history.form_id', '=', $form_id)
                    ->where('form_history.form_type', '=', $form_type)
                    ->orderBy('form_history.id', 'desc')
                    ->get();

                return FormHistoryResources::collection($history);
                break;

            case 'approver':
                $form_id = $request->input('id');

                $approver = DB::table('manpower_forms')
                    ->leftJoin('employees', 'manpower_forms.requestor_id', '=', 'employees.id')
                    ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('transaction_approvers', function($join) { 
                        // $join->on('positions.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                        $join->on('manpower_forms.form_type', '=', 'transaction_approvers.form_type'); 
                        $join->on('manpower_forms.id', '=', 'transaction_approvers.transaction_id'); 
                    })
                    ->leftJoin('employees as approvers', 'transaction_approvers.employee_id', '=', 'approvers.id')
                    ->leftJoin('transaction_statuses', function($join) { 
                        $join->on('transaction_approvers.form_type', '=', 'transaction_statuses.form_type'); 
                        $join->on('manpower_forms.id', '=', 'transaction_statuses.form_id'); 
                        $join->on('approvers.id', '=', 'transaction_statuses.reviewer_id')->groupBy('transaction_statuses.reviewer_id'); 
                    })
                    ->leftJoin('employees as receivers', 'transaction_approvers.receiver_id', '=', 'receivers.id')
                    ->select([
                        'transaction_statuses.form_id',
                        'approvers.first_name as approver_first_name',
                        'approvers.last_name as approver_last_name',
                        'transaction_approvers.level',
                        'transaction_approvers.employee_id',
                        'receivers.first_name as receiver_first_name',
                        'receivers.last_name as receiver_last_name',
                        'transaction_statuses.status_mark',
                        'transaction_statuses.status',
                        'manpower_forms.is_fulfilled'
                    ])
                    ->where('manpower_forms.id', '=', $form_id)
                    ->get()
                    ->map(function ($approver) {
                        $approver->full_name = $approver->approver_first_name.' '.$approver->approver_last_name;
                        $approver->receiver_full_name = $approver->receiver_first_name.' '.$approver->receiver_last_name;
                        $approver->status_mark = ($approver->status_mark) ? $approver->status_mark : 'PENDING';
                        return $approver;
                    });

                    $approvers = [];

                    foreach ($approver as $value) {
                        $approvers[$value->employee_id] = $value;
                    }

                return response()->json($approvers);

                break;
            default: 
        }
    }

    public function deleteManpower($id) {
        $manpower = ManpowerForms::findOrFail($id);
        
        if ($manpower->delete()) {

            Helpers::LogActivity($manpower->id, 'FORM REQUISITION - MANPOWER FORM', 'DELETED MANPOWER FORM REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', $manpower->form_type)
                ->delete();

            $transaction_statuses = DB::table('transaction_statuses')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', $manpower->form_type)
                ->delete();

            $transaction_approvers = DB::table('transaction_approvers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $manpower->form_type)
                ->delete();

            $transaction_receivers = DB::table('transaction_receivers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $manpower->form_type)
                ->delete();

            $datachange_query = DB::table('datachange_forms')
                ->where('manpower_id', '=', $manpower->id);
            
            $count = $datachange_query->get()->count();

            if ($count) {
                $dc = $datachange_query->get()->first();

                $da_form = DB::table('da_forms')
                    ->where('datachange_id', '=', $dc->id)
                    ->get()
                    ->first();

                $da_form_count = DB::table('da_forms')->where('datachange_id', '=', $dc->id)->get()->count();

                if ($da_form_count) {
                    $evaluation = DB::table('da_evaluations')
                    ->where('daform_id', '=', $da_form->id);
                
                    $evaluation_count = $evaluation->get()->count();

                    if ($evaluation_count) {
                        $de = $evaluation->get()->first();
                        $da_evaluation = DaEvaluation::findOrFail($de->id);

                        if ($da_evaluation->delete()) {
                            $form_history = DB::table('form_history')
                                ->where('form_id', $da_evaluation->id)
                                ->where('form_type', $da_evaluation->form_type)
                                ->delete();

                            $transaction_statuses = DB::table('transaction_statuses')
                                ->where('form_id', '=', $da_evaluation->id)
                                ->where('form_type', '=', $da_evaluation->form_type)
                                ->delete();

                            $transaction_approvers = DB::table('transaction_approvers')
                                ->where('transaction_id', '=', $da_evaluation->id)
                                ->where('form_type', '=', $da_evaluation->form_type)
                                ->delete();

                            $transaction_receivers = DB::table('transaction_receivers')
                                ->where('transaction_id', '=', $da_evaluation->id)
                                ->where('form_type', '=', $da_evaluation->form_type)
                                ->delete();
                        }
                    }
                }

                $da = DB::table('da_forms')
                    ->where('datachange_id', '=', $dc->id)
                    ->delete();

                $transaction_datachanges = DB::table('transaction_datachanges')
                    ->where('transaction_id', '=', $dc->id)
                    ->where('form_type', '=', 'datachange-form')
                    ->delete();
                    
                $datachange_query->delete();
            }
                
            return response()->json($form_history);
        }
    }

    public function resubmitManpower(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');
        
        $manpower = ManpowerForms::findOrFail($id);

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;

        if ($isLoggedIn) { 
            $current_user = Auth::user();

            $data = DB::table('positions')
                ->select(['id', 'subunit_id'])
                ->where('positions.id', '=', $manpower->position_id)
                ->first();

            $approver = DB::table('forms')
                ->select(['id'])
                ->where('form_type', '=', 'manpower-form')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        if ($manpower->current_status == 'rejected' || $manpower->current_status == 'cancelled') {
            $manpower->level = ($isApprover) ? 2 : 1;
            $manpower->current_status = 'for-approval';
            $manpower->current_status_mark = 'FOR APPROVAL';
            $manpower->is_fulfilled = 'PENDING';

            if ($manpower->save()) {
                Helpers::LogActivity($manpower->id, 'FORM REQUISITION - MANPOWER FORM', 'RESUBMITTED MANPOWER FORM REQUEST');

                $form_history = new FormHistory();
                $form_history->form_id = $manpower->id;
                $form_history->code = $manpower->code;
                $form_history->form_type = $manpower->form_type;
                $form_history->form_data = $manpower->toJson();
                $form_history->level = $manpower->level;
                $form_history->status = ($isApprover) ? 'approved' : $manpower->current_status;
                $form_history->status_mark = ($isApprover) ? 'APPROVED' : $manpower->current_status_mark;
                $form_history->reviewer_id = $manpower->requestor_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'assess';
                $form_history->remarks = $manpower->remarks;
                $form_history->requestor_id = $manpower->requestor_id;
                $form_history->employee_id = $manpower->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'RESUBMISSION OF MANPOWER REQUEST FORM';
                $form_history->save();

                $delete_transaction_statuses = DB::table('transaction_statuses')
                    ->where('form_id', '=', $manpower->id)
                    ->where('form_type', '=', $manpower->form_type)
                    ->delete();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $manpower->id;
                $transaction_statuses->code = $manpower->code;
                $transaction_statuses->form_type = $manpower->form_type;
                $transaction_statuses->form_data = $manpower->toJson();
                $transaction_statuses->level = $manpower->level;
                $transaction_statuses->status = ($isApprover) ? 'approved' : $manpower->current_status;
                $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $manpower->current_status_mark;
                $transaction_statuses->reviewer_id = $manpower->requestor_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'assess';
                $transaction_statuses->remarks = $manpower->remarks;
                $transaction_statuses->requestor_id = $manpower->requestor_id;
                $transaction_statuses->employee_id = $manpower->id;
                $transaction_statuses->is_fulfilled = 'PENDING';
                $transaction_statuses->description = 'RESUBMISSION OF MANPOWER REQUEST FORM';
                $transaction_statuses->save();

                $datachange = DatachangeForms::where('manpower_id', '=', $manpower->id)->first();
                
                if ($datachange) {
                    $datachange->current_status = 'for-approval';
                    $datachange->current_status_mark = 'FOR APPROVAL';
                    $datachange->is_fulfilled = 'PENDING';
                    $datachange->save();
                    
                    $da = DaForms::where('datachange_id', '=', $datachange->id)->first();
                    
                    if ($da) {
                        $da->current_status = 'for-approval';
                        $da->current_status_mark = 'FOR APPROVAL';
                        $da->is_fulfilled = 'PENDING';
                        $da->save();
                    }
                }
            }
        }
    }

    public function checkForms(Request $request) {
        $forms = [
            'manpower' => 0,
            'datachange' => 0,
            'da' => 0,
        ];
        $da = 0;
        $manpower_id = $request->manpower_id;

        $manpower = DB::table('manpower_forms')
            ->select(['id'])
            ->where('manpower_forms.id', '=', $manpower_id)
            ->get()
            ->count();

        $datachange = DB::table('datachange_forms')
            ->select(['id'])
            ->where('datachange_forms.manpower_id', '=', $manpower_id)
            ->get();

        if ($datachange->count()) {
            $da = DB::table('da_forms')
                ->select(['id'])
                ->where('da_forms.datachange_id', '=', $datachange->first()->id)
                ->get()
                ->count();
        }

        $forms['manpower'] = $manpower;
        $forms['datachange'] = $datachange->count();
        $forms['da'] = $da;

        return response()->json($forms);
    }
    
    public function getManpowerForRegistration() {
        $manpowers = [];
        $keyword = request('keyword');
        $requestor_id = Auth::user()->employee_id;

        $query = DB::table('manpower_forms')
            ->leftJoin('positions', 'manpower_forms.position_id', '=', 'positions.id')
            // ->leftJoin('forms', function($join) { 
            //     $join->on('manpower_forms.form_type', '=', 'forms.form_type'); 
            //     $join->on('positions.subunit_id', '=', 'forms.subunit_id'); 
            //     $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = positions.subunit_id AND forms.form_type = manpower_forms.form_type)'));
            // })
            ->leftJoin('jobrates', 'manpower_forms.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'requestor.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
            ->leftJoin('departments as requestor_department', 'requestor_position.department_id', '=', 'requestor_department.id')
            ->leftJoin('subunits as requestor_subunit', 'requestor_position.subunit_id', '=', 'requestor_subunit.id')
            ->leftJoin('employees as replacement', 'manpower_forms.replacement_for', '=', 'replacement.id')
            ->leftJoin('departments as position_department', 'positions.department_id', '=', 'position_department.id')
            ->leftJoin('subunits as position_subunit', 'positions.subunit_id', '=', 'position_subunit.id')
            ->leftJoin('employees as tobehired', 'manpower_forms.tobe_hired', '=', 'tobehired.id')
            ->leftJoin('employee_positions as employee_positions_tobehired', 'tobehired.id', '=', 'employee_positions_tobehired.employee_id')
            ->leftJoin('positions as position_tobehired', 'employee_positions_tobehired.position_id', '=', 'position_tobehired.id')
            ->leftJoin('departments as department_tobehired', 'position_tobehired.department_id', '=', 'department_tobehired.id')
            ->leftJoin('subunits as subunit_tobehired', 'position_tobehired.subunit_id', '=', 'subunit_tobehired.id')
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type)')); 
            })
            ->select([
                'manpower_forms.id',
                'manpower_forms.code',
                'manpower_forms.position_id',
                'manpower_forms.jobrate_id',
                'manpower_forms.manpower_count',
                'manpower_forms.expected_salary',
                'manpower_forms.salary_structure',
                'manpower_forms.jobrate_name',
                'manpower_forms.job_level',
                'manpower_forms.employment_type',
                'manpower_forms.employment_type_label',
                'manpower_forms.requisition_type',
                'manpower_forms.requisition_type_mark',
                'manpower_forms.attachment',
                'manpower_forms.justification',
                'manpower_forms.replacement_for',
                'manpower_forms.form_type',
                'manpower_forms.level',
                'manpower_forms.current_status',
                'manpower_forms.current_status_mark',
                'manpower_forms.requestor_id',
                'manpower_forms.requestor_remarks',
                'manpower_forms.is_fulfilled',
                'manpower_forms.date_fulfilled',
                'manpower_forms.tobe_hired',
                'manpower_forms.created_at',
                'position_department.department_name as position_department',
                'position_subunit.subunit_name as position_subunit',
                'positions.position_name',
                'positions.payrate',
                'positions.shift',
                'positions.schedule',
                'positions.team',
                'positions.jobband_id',
                'jobbands.jobband_name',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
                'requestor.first_name',
                'requestor.last_name',
                'requestor_position.position_name as requestor_position',
                'requestor_department.department_name as requestor_department',
                'requestor_subunit.subunit_name as requestor_subunit',
                'replacement.id as replacement_id',
                'replacement.prefix_id as replacement_prefix_id',
                'replacement.id_number as replacement_id_number',
                'replacement.first_name as replacement_first_name',
                'replacement.middle_name as replacement_middle_name',
                'replacement.last_name as replacement_last_name',
                'replacement.suffix as replacement_suffix',
                'tobehired.first_name as tobehired_firstname',
                'tobehired.last_name as tobehired_lastname',
                'position_tobehired.position_name as position_name_tobehired',
                'department_tobehired.department_name as department_name_tobehired',
                'subunit_tobehired.subunit_name as subunit_name_tobehired',
                'form_history.status_mark',
                'form_history.review_date',
            ]);

        $manpowers = $query->whereNotIn('manpower_forms.id', DB::table('employees')->whereNotNull('manpower_id')->pluck('manpower_id'));

        $manpowers = $query->get();

        return ManpowerForRegistration::collection($manpowers);
    }
}
