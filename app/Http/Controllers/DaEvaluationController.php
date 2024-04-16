<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\DaEvaluation as DaEvaluationResources;
use App\Http\Resources\DaEvaluationDetailRevisedII;
use App\Http\Resources\DaEvaluationEmployee as DaEvaluationEmployeeResources;
use App\Http\Resources\DaEvaluationEmployeeWithMeasures;
use App\Models\DaEvaluation;
use App\Models\DaForms;
use App\Models\FormHistory;
use App\Models\TransactionApprovers;
use App\Models\TransactionReceivers;
use App\Models\TransactionStatuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DAEvaluationController extends Controller
{
    public function getDAEmployees() {
        $da = [];
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
                    'da_forms.measures',
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

        return DaEvaluationEmployeeWithMeasures::collection($da);
    }

    public function index()
    { 
        $requestor_id = Auth::user()->employee_id;
        $keyword = request('keyword');

        $query = DB::table('da_evaluations')
            ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
            // ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            
            ->leftJoin('da_forms', 'da_evaluations.daform_id', '=', 'da_forms.id')
            ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')

            ->leftJoin('transaction_datachanges', function($join) { 
                $join->on('datachange_forms.form_type', '=', 'transaction_datachanges.form_type'); 
                $join->on('datachange_forms.id', '=', 'transaction_datachanges.transaction_id'); 
            })

            ->leftJoin('positions', 'transaction_datachanges.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')

            ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
            ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
            ->leftJoin('positions as prev_position', 'datachange_forms.prev_position_id', '=', 'prev_position.id')
            ->leftJoin('departments as prev_department', 'datachange_forms.prev_department_id', '=', 'prev_department.id')
            ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
            ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            }) 
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = da_evaluations.id AND form_history.form_type = da_evaluations.form_type)')); 
            }) 
            ->select([
                'da_evaluations.id',
                'da_evaluations.code',
                'da_evaluations.employee_id',
                'da_evaluations.daform_id',
                'da_evaluations.measures',
                'da_evaluations.assessment',
                'da_evaluations.assessment_mark',
                'da_evaluations.total_grade',
                'da_evaluations.total_target',
                'da_evaluations.attachment',
                'da_evaluations.form_type',
                'da_evaluations.level',
                'da_evaluations.current_status',
                'da_evaluations.current_status_mark',
                'da_evaluations.requestor_id',
                'da_evaluations.requestor_remarks',
                'da_evaluations.date_evaluated',
                'da_evaluations.is_fulfilled',
                'da_evaluations.date_fulfilled',
                'da_evaluations.created_at',
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
                'da_forms.id as daform_id',
                'da_forms.inclusive_date_start',
                'da_forms.inclusive_date_end',
                'datachange_forms.change_position_id',
                'datachange_forms.change_reason',
                'change_position.position_name as change_position_name',
                'change_department.department_name as change_department_name',
                'change_subunit.subunit_name as change_subunit_name',
                'change_jobband.jobband_name as change_jobband_name',
                'datachange_forms.additional_rate',
                'datachange_forms.jobrate_name',
                'datachange_forms.salary_structure',
                'datachange_forms.job_level',
                'datachange_forms.allowance',
                'datachange_forms.job_rate',
                'datachange_forms.salary',
                'transaction_datachanges.additional_rate as prev_additional_rate',
                'transaction_datachanges.jobrate_name as prev_jobrate_name',
                'transaction_datachanges.salary_structure as prev_salary_structure',
                'transaction_datachanges.job_level as prev_job_level',
                'transaction_datachanges.allowance as prev_allowance',
                'transaction_datachanges.job_rate as prev_job_rate',
                'transaction_datachanges.salary as prev_salary',
                'form_history.status_mark',
                'form_history.review_date',
            ]);

        $da_evaluations = $query->where('da_evaluations.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $da_evaluations = $query
                ->where('da_evaluations.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('da_evaluations.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return DaEvaluationDetailRevisedII::collection($da_evaluations);
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('da_evaluations')
            ->select(['employee_id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('current_status', '!=', 'rejected');

        // if ($duplicate_entry->count()) {
        //     throw ValidationException::withMessages([
        //         'employee_id' => ['Evaluation has been already filed for this employee.']
        //     ]);
        // }

        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select(['positions.subunit_id', 'positions.position_name'])
            ->where('employees.id', '=', $request->input('employee_id'))
            ->get()
            ->first();

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;

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
                ->where('form_type', '=', 'da-evaluation')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $employee->subunit_id)
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
                ->where('form_type', '=', 'da-evaluation')
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
                ->where('form_type', '=', 'da-evaluation')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        $filenames = [];
        $da_evaluation = new DaEvaluation();
        $da_evaluation->code = Helpers::generateCodeNewVersion('da_evaluations', 'DAF');
        $da_evaluation->employee_id = $request->input('employee_id');
        $da_evaluation->daform_id = $request->input('daform_id');

        $daform = DaForms::findOrFail($request->input('daform_id'));
        $da_evaluation->prev_measures = json_decode($daform->measures);

        $da_evaluation->measures = json_decode($request->input('measures'));
        $da_evaluation->assessment = $request->input('assessment');
        $da_evaluation->assessment_mark = $request->input('assessment_mark');
        $da_evaluation->total_grade = $request->input('total_grade');
        $da_evaluation->total_target = $request->input('total_target');
        $da_evaluation->form_type = 'da-evaluation';
        $da_evaluation->level = ($isApprover) ? 2 : 1;
        $da_evaluation->current_status = 'for-approval';
        $da_evaluation->current_status_mark = 'FOR APPROVAL';
        $da_evaluation->requestor_id = Auth::user()->employee_id;
        $da_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $da_evaluation->date_evaluated = Carbon::now()->format('M d, Y h:i a');
        // $da_evaluation->created_at = Carbon::now()->format('M d, Y H:i a');
        // $da_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');
        $da_evaluation->is_fulfilled = 'PENDING';

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/annual_attachments', $fileNameToStore);
        //     $da_evaluation->attachment = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/da_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $da_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($da_evaluation->save()) {
            Helpers::LogActivity($da_evaluation->id, 'EVALUATION - DA EVALUATION', 'REQUESTED A NEW DA EVALUATION FORM');

            $form_history = new FormHistory();
            $form_history->code = $da_evaluation->code;
            $form_history->form_id = $da_evaluation->id;
            $form_history->form_type = $da_evaluation->form_type;
            $form_history->form_data = $da_evaluation->toJson();
            $form_history->status = ($isApprover) ? 'approved' : $da_evaluation->current_status;
            $form_history->status_mark = ($isApprover) ? 'APPROVED' : $da_evaluation->current_status_mark;
            $form_history->reviewer_id = $da_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $da_evaluation->requestor_remarks;
            $form_history->level = ($isApprover) ? 2 : 1;
            $form_history->requestor_id = $da_evaluation->requestor_id;
            $form_history->employee_id = $da_evaluation->employee_id;
            $form_history->is_fulfilled = $da_evaluation->is_fulfilled;
            $form_history->description = 'REQUESTING FOR EVALUATION APPROVAL';
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();

            $transaction_statuses = new TransactionStatuses();
            $transaction_statuses->code = $da_evaluation->code;
            $transaction_statuses->form_id = $da_evaluation->id;
            $transaction_statuses->form_type = $da_evaluation->form_type;
            $transaction_statuses->form_data = $da_evaluation->toJson();
            $transaction_statuses->status = ($isApprover) ? 'approved' : $da_evaluation->current_status;
            $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $da_evaluation->current_status_mark;
            $transaction_statuses->reviewer_id = $da_evaluation->requestor_id;
            $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
            $transaction_statuses->reviewer_action = 'assess';
            $transaction_statuses->remarks = $da_evaluation->requestor_remarks;
            $transaction_statuses->level = ($isApprover) ? 2 : 1;
            $transaction_statuses->requestor_id = $da_evaluation->requestor_id;
            $transaction_statuses->employee_id = $da_evaluation->employee_id;
            $transaction_statuses->is_fulfilled = $da_evaluation->is_fulfilled;
            $transaction_statuses->description = 'REQUESTING FOR EVALUATION APPROVAL';
            $transaction_statuses->reviewer_attachment = implode(',', $filenames);
            $transaction_statuses->save();

            $data_approvers = [];
            $approvers = $approvers->toArray();

            foreach ($approvers as $form) {
                $data_approvers[] = [
                    'transaction_id' => $da_evaluation->id,
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
                    'transaction_id' => $da_evaluation->id,
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

            return response()->json($da_evaluation);
        }
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('da_evaluations')
            ->select(['employee_id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('current_status', '!=', 'rejected')
            ->where('id', '!=', $id);

        // if ($duplicate_entry->count()) {
        //     throw ValidationException::withMessages([
        //         'employee_id' => ['Evaluation has been already filed for this employee.']
        //     ]);
        // }

        $filenames = [];
        $da_evaluation = DaEvaluation::findOrFail($id);
        $da_evaluation->employee_id = $request->input('employee_id');
        $da_evaluation->measures = json_decode($request->input('measures'));
        $da_evaluation->assessment = $request->input('assessment');
        $da_evaluation->assessment_mark = $request->input('assessment_mark');
        $da_evaluation->total_grade = $request->input('total_grade');
        $da_evaluation->total_target = $request->input('total_target');
        $da_evaluation->requestor_id = Auth::user()->employee_id;
        $da_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        // $da_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');

        // $resubmit = ($da_evaluation->current_status == 'rejected') ? true : false;

        // if ($resubmit) {
        //     $da_evaluation->current_status = 'for-approval';
        //     $da_evaluation->current_status_mark = 'FOR APPROVAL';
        // }

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/annual_attachments', $fileNameToStore);
        //     $da_evaluation->attachment = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/da_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $da_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($da_evaluation->save()) {
            Helpers::LogActivity($da_evaluation->id, 'EVALUATION - DA EVALUATION', 'UPDATED DA EVALUATION FORM DATA');

            $form_history = FormHistory::where('form_id', $da_evaluation->id)
                ->where('form_type', 'da-evaluation')
                ->first();

            // $form_history->form_id = $da_evaluation->id;
            // $form_history->form_type = $da_evaluation->form_type;
            $form_history->form_data = $da_evaluation->toJson();
            // $form_history->status = $da_evaluation->current_status;
            // $form_history->status_mark = $da_evaluation->current_status_mark;
            $form_history->reviewer_id = $da_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            // $form_history->remarks = $da_evaluation->requestor_remarks;
            // $form_history->level = $da_evaluation->level;
            // $form_history->requestor_id = $da_evaluation->requestor_id;
            // $form_history->employee_id = $da_evaluation->employee_id;
            // $form_history->is_fulfilled = $da_evaluation->is_fulfilled;
            // $form_history->description = 'REQUESTING FOR EVALUATION APPROVAL';
            // $form_history->updated_at = Carbon::now()->format('M d, Y H:i a');
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();

            $da_evaluations = DB::table('da_evaluations')
                ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('da_forms', 'da_evaluations.daform_id', '=', 'da_forms.id')
                ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                ->leftJoin('positions as prev_position', 'datachange_forms.prev_position_id', '=', 'prev_position.id')
                ->leftJoin('departments as prev_department', 'datachange_forms.prev_department_id', '=', 'prev_department.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                }) 
                ->leftJoin('form_history', function($join) { 
                    $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = da_evaluations.id AND form_history.form_type = da_evaluations.form_type)')); 
                })
                ->select([
                    'da_evaluations.id',
                    'da_evaluations.code',
                    'da_evaluations.employee_id',
                    'da_evaluations.daform_id',
                    'da_evaluations.measures',
                    'da_evaluations.assessment',
                    'da_evaluations.assessment_mark',
                    'da_evaluations.total_grade',
                    'da_evaluations.total_target',
                    'da_evaluations.attachment',
                    'da_evaluations.form_type',
                    'da_evaluations.level',
                    'da_evaluations.current_status',
                    'da_evaluations.current_status_mark',
                    'da_evaluations.requestor_id',
                    'da_evaluations.requestor_remarks',
                    'da_evaluations.date_evaluated',
                    'da_evaluations.is_fulfilled',
                    'da_evaluations.date_fulfilled',
                    'da_evaluations.created_at',
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
                    'da_forms.id as daform_id',
                    'da_forms.inclusive_date_start',
                    'da_forms.inclusive_date_end',
                    'datachange_forms.change_reason',
                    'change_position.position_name as change_position_name',
                    'change_department.department_name as change_department_name',
                    'prev_position.position_name as prev_position_name',
                    'prev_department.department_name as prev_department_name',
                    'form_history.status_mark',
                    'form_history.review_date',
                ])
                ->where('da_evaluations.id', '=', $da_evaluation->id)
                ->get();
            
            return DaEvaluationResources::collection($da_evaluations);
        }
    }

    public function resubmitEvaluation(Request $request, $id) 
    {
        date_default_timezone_set('Asia/Manila');

        $evaluation = DaEvaluation::findOrFail($id);

        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select(['positions.subunit_id', 'positions.position_name'])
            ->where('employees.id', '=', $evaluation->employee_id)
            ->get()
            ->first();

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;

        if ($isLoggedIn) { 
            $current_user = Auth::user();

            $approver = DB::table('forms')
                ->select(['id'])
                ->where('form_type', '=', 'da-evaluation')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $employee->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        if ($evaluation->current_status == 'rejected' || $evaluation->current_status == 'cancelled') {
            $evaluation->level = ($isApprover) ? 2 : 1;
            $evaluation->current_status = 'for-approval';
            $evaluation->current_status_mark = 'FOR APPROVAL';
            $evaluation->is_fulfilled = 'PENDING';

            if ($evaluation->save()) {
                Helpers::LogActivity($evaluation->id, 'FORM REQUISITION - DA EVALUATION FORM', 'RE-SUBMITTED DA EVALUATION FORM REQUEST');

                $form_history = new FormHistory();
                $form_history->form_id = $evaluation->id;
                $form_history->code = $evaluation->code;
                $form_history->form_type = $evaluation->form_type;
                $form_history->form_data = $evaluation->toJson();
                $form_history->status = ($isApprover) ? 'approved' : $evaluation->current_status;
                $form_history->status_mark = ($isApprover) ? 'APPROVED' : $evaluation->current_status_mark;
                $form_history->reviewer_id = $evaluation->requestor_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'assess';
                $form_history->remarks = $evaluation->remarks;
                $form_history->level = ($isApprover) ? 2 : 1;
                $form_history->requestor_id = $evaluation->requestor_id;
                $form_history->employee_id = $evaluation->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'RESUBMISSION OF EVALUATION REVISION';
                $form_history->save();

                $delete_transaction_statuses = DB::table('transaction_statuses')
                    ->where('form_id', '=', $evaluation->id)
                    ->where('form_type', '=', $evaluation->form_type)
                    ->delete();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $evaluation->id;
                $transaction_statuses->code = $evaluation->code;
                $transaction_statuses->form_type = $evaluation->form_type;
                $transaction_statuses->form_data = $evaluation->toJson();
                $transaction_statuses->status = ($isApprover) ? 'approved' : $evaluation->current_status;
                $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $evaluation->current_status_mark;
                $transaction_statuses->reviewer_id = $evaluation->requestor_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'assess';
                $transaction_statuses->remarks = $evaluation->remarks;
                $transaction_statuses->level = ($isApprover) ? 2 : 1;
                $transaction_statuses->requestor_id = $evaluation->requestor_id;
                $transaction_statuses->employee_id = $evaluation->id;
                $transaction_statuses->is_fulfilled = 'PENDING';
                $transaction_statuses->description = 'RESUBMISSION OF EVALUATION REVISION';
                $transaction_statuses->save();
            }
        }
    }

    public function destroy($id)
    {
        $da_evaluation = DaEvaluation::findOrFail($id);

        if ($da_evaluation->delete()) {
            Helpers::LogActivity($da_evaluation->id, 'EVALUATION - DA EVALUATION', 'DELETED DA EVALUATION REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $da_evaluation->form_type)
                ->delete();

            $transaction_statuses = DB::table('transaction_statuses')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', $da_evaluation->form_type)
                ->delete();

            $transaction_approvers = DB::table('transaction_approvers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $da_evaluation->form_type)
                ->delete();

            $transaction_receivers = DB::table('transaction_receivers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $da_evaluation->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }

    public function cancelDAEvaluation(Request $request, $id) 
    {
        date_default_timezone_set('Asia/Manila');

        $form_type = 'da-evaluation';
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

        if ($form_type == 'da-evaluation') {
            $review = 'cancelled';
            $review_mark = 'CANCELLED';
            $remarks = '';

            $evaluation = DaEvaluation::findOrFail($form_id);
            $evaluation->level = 1;
            $evaluation->current_status = $review;
            $evaluation->current_status_mark = $review_mark;
            $evaluation->is_fulfilled = 'CANCELLED';

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
                $transaction_statuses->save();
            }

            return response()->json($evaluation);
        }
    }
}
