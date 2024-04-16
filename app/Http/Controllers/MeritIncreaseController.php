<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\MeritIncreaseEmployeee as MeritIncreaseEmployeeeResource;
use App\Http\Resources\MeritIncrease as MeritIncreaseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\JobRate as JobRateResources;
use App\Models\EmployeePosition;
use App\Models\FormHistory;
use App\Models\JobRate;
use App\Models\MeritIncreaseForm;
use App\Models\Position;
use Carbon\Carbon;

class MeritIncreaseController extends Controller
{
    public function getSubordinateEmployees(Request $request) {
        $requestor_id = Auth::user()->employee_id;
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
            'BACK OUT'
        ];

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
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
                'jobrates.job_level',
                'jobrates.salary_structure',
                'jobrates.jobrate_name'
            ])
            ->where('jobbands.order', '>', $order_no)
            // ->where('positions.department_id', '=', $department_id)
            // ->where('employee_statuses.employment_type', '=', 'regular')
            ->where(function ($query) {
                $query->where('employee_states.employee_state', '=', 'extended')
                    ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
                    ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
                    ->orWhere('employee_states.employee_state', '=', 'returned')
                    ->orWhereNull('employee_states.employee_state');
            })
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->whereNotIn('employees.id', DB::table('merit_increase_forms')->where('merit_increase_forms.current_status', '!=', 'approved')->pluck('employee_id'))
            ->whereNotIn('employees.id', DB::table('da_evaluations')->where('da_evaluations.current_status', '!=', 'approved')->pluck('employee_id'))
            ->where('employees.current_status', '!=', 'for-approval');

        // if ($order && $order->subunit_bound) {
        //     $subordinates = $query
        //         ->where('positions.superior', '=', $requestor_id)->get();
        //         // ->where('positions.subunit_id', '=', $subunit_id)->get();
        // } else {
        //     $subordinates = $query->get();
        // }

        $subordinates = $query->where('positions.superior', '=', $requestor_id)->get();

        return MeritIncreaseEmployeeeResource::collection($subordinates);
    }

    public function getJobrates(Request $request) {
        $position_id = $request->position_id;
        $employee_id = $request->employee_id;
        $employee = EmployeePosition::where('employee_id', '=', $employee_id)->first();
        $position = Position::findOrFail($position_id);
        $position_title = ($position) ? $position->position_name : '';
        $current_rate = JobRate::findOrFail($employee->jobrate_id);
        $current_rate = (int) $current_rate->job_rate;

        $jobrate = DB::table('jobrates')
            ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
            ->select([
                'jobrates.id', 
                'jobrates.code',
                'jobrates.position_id', 
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.allowance',
                'jobrates.salary_structure', 
                'jobrates.jobrate_name', 
                'jobrates.position_title',
                'jobrates.status',
                'jobrates.status_description',
                'jobrates.created_at',
                'positions.position_name'
            ])
            ->where('jobrates.position_title', '=', $position_title)
            ->where('jobrates.id', '!=', $employee->jobrate_id)
            // ->where('jobrates.job_rate', '>', $current_rate->job_rate)
            ->get()
            ->map(function ($jobrate) {
                $rate = str_replace(',', '', $jobrate->job_rate);
                $jobrate->rate = (int) $rate;
                return $jobrate;
            })
            ->where('rate', '>', $current_rate);

        return JobRateResources::collection($jobrate);
    }

    public function getMeritIncrease(Request $request) {
        $requestor_id = Auth::user()->employee_id;
        $keyword = request('keyword');

        $query = DB::table('merit_increase_forms')
            ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = merit_increase_forms.id AND form_history.form_type = merit_increase_forms.form_type)')); 
            })
            ->select([
                'merit_increase_forms.id',
                'merit_increase_forms.code',
                'merit_increase_forms.employee_id',
                'merit_increase_forms.attachment',
                'merit_increase_forms.form_type',
                'merit_increase_forms.level',
                'merit_increase_forms.current_status',
                'merit_increase_forms.current_status_mark',
                'merit_increase_forms.requestor_id',
                'merit_increase_forms.requestor_remarks',
                'merit_increase_forms.is_fulfilled',
                'merit_increase_forms.date_fulfilled',
                'merit_increase_forms.created_at',
                'form_history.status',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'employees.referrer_id',
                'referrer.prefix_id AS r_prefix_id',
                'referrer.id_number AS r_id_number',
                'referrer.first_name AS r_first_name',
                'referrer.middle_name AS r_middle_name',
                'referrer.last_name AS r_last_name',
                'referrer.suffix AS r_suffix',
                'requestor.prefix_id AS req_prefix_id',
                'requestor.id_number AS req_id_number',
                'requestor.first_name AS req_first_name',
                'requestor.middle_name AS req_middle_name',
                'requestor.last_name AS req_last_name',
                'requestor.suffix AS req_suffix',
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
                'jobbands.jobband_name',
                'jobrates.id as jobrate_id',
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.salary_structure', 
                'jobrates.jobrate_name',
                'proposed_jobrate.id as proposed_jobrate_id',
                'proposed_jobrate.job_level as proposed_job_level',
                'proposed_jobrate.job_rate as proposed_job_rate',
                'proposed_jobrate.salary_structure as proposed_salary_structure',
                'proposed_jobrate.jobrate_name as proposed_jobrate_name',
            ]);

        $merit_increase = $query->where('merit_increase_forms.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $merit_increase = $query
                ->where('merit_increase_forms.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('merit_increase_forms.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return MeritIncreaseResource::collection($merit_increase);
    }

    public function storeMeritIncrease(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $requestor_id = Auth::user()->employee_id;

        $rules = [
            'employee_id' => ['required'],
            'jobrate_id' => ['required'],
        ];

        $this->validate($request, $rules);
        
        $merit_increase = new MeritIncreaseForm();
        $merit_increase->code = Helpers::generateCodeNewVersion('merit_increase_forms', 'DCF');
        $merit_increase->employee_id = $request->employee_id;
        $merit_increase->jobrate_id = $request->jobrate_id;

        $filenames = [];

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/datachange_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $merit_increase->attachment = implode(',', $filenames);
            }
        }

        $merit_increase->form_type = 'merit-increase-form';
        $merit_increase->level = 1;
        $merit_increase->current_status = 'for-approval';
        $merit_increase->current_status_mark = 'FOR APPROVAL';
        $merit_increase->requestor_id = $requestor_id;
        $merit_increase->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $merit_increase->is_fulfilled = 'PENDING';
        
        if ($merit_increase->save()) {
            Helpers::LogActivity($merit_increase->id, 'FORM REQUISITION - DATA CHANGE FORM (MERIT INCREASE)', 'REQUESTED A NEW DATA CHANGE (MERIT INCREASE) FORM');

            $form_history = new FormHistory();
            $form_history->form_id = $merit_increase->id;
            $form_history->code = $merit_increase->code;
            $form_history->form_type = $merit_increase->form_type;
            $form_history->form_data = $merit_increase->toJson();
            $form_history->status = $merit_increase->current_status;
            $form_history->status_mark = $merit_increase->current_status_mark;
            $form_history->reviewer_id = $merit_increase->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $merit_increase->requestor_remarks;
            $form_history->level = $merit_increase->level;
            $form_history->requestor_id = $merit_increase->requestor_id;
            $form_history->employee_id = $merit_increase->employee_id;
            $form_history->is_fulfilled = $merit_increase->is_fulfilled;
            $form_history->description = 'REQUESTING FOR DATA CHANGE APPROVAL';
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();
        }
    }

    public function updateMeritIncrease(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $rules = [
            'employee_id' => ['required'],
            'jobrate_id' => ['required'],
        ];

        $this->validate($request, $rules);

        $filenames = [];
        $merit_increase = MeritIncreaseForm::findOrFail($id);
        $merit_increase->employee_id = $request->employee_id;
        $merit_increase->jobrate_id = $request->jobrate_id;
        $merit_increase->requestor_id = Auth::user()->employee_id;
        $merit_increase->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';

        $resubmit = ($merit_increase->current_status == 'rejected') ? true : false;

        if ($resubmit) {
            $merit_increase->current_status = 'for-approval';
            $merit_increase->current_status_mark = 'FOR APPROVAL';
        }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/datachange_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $merit_increase->attachment = implode(',', $filenames);
            }
        }

        if ($merit_increase->save()) {
            Helpers::LogActivity($merit_increase->id, 'FORM REQUISITION - DATA CHANGE FORM (MERIT INCREASE)', 'UPDATED DATA CHANGE (MERIT INCREASE) FORM DATA');

            $form_history = FormHistory::where('form_id', $merit_increase->id)
                ->where('form_type', 'merit-increase-form')
                ->first();

            if ($resubmit) {
                $form_history = new FormHistory();
            }

            $form_history->form_id = $merit_increase->id;
            $form_history->code = $merit_increase->code;
            $form_history->form_type = $merit_increase->form_type;
            $form_history->form_data = $merit_increase->toJson();
            $form_history->status = $merit_increase->current_status;
            $form_history->status_mark = $merit_increase->current_status_mark;
            $form_history->reviewer_id = $merit_increase->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->remarks = $merit_increase->requestor_remarks;
            $form_history->level = $merit_increase->level;
            $form_history->requestor_id = $merit_increase->requestor_id;
            $form_history->employee_id = $merit_increase->employee_id;
            $form_history->is_fulfilled = $merit_increase->is_fulfilled;
            $form_history->description = ($resubmit) ? 'RESUBMISSION OF DATA CHANGE REVISION' : 'REQUESTING FOR DATA CHANGE APPROVAL';
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();
        }
    }

    public function deleteMeritIncrease($id) {
        $merit_increase = MeritIncreaseForm::findOrFail($id);

        if ($merit_increase->delete()) {
            Helpers::LogActivity($merit_increase->id, 'FORM REQUISITION - DATA CHANGE FORM (MERIT INCREASE)', 'DELETED DATA CHANGE (MERIT INCREASE) REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $merit_increase->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }
}
