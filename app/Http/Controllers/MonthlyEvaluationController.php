<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\MonthlyEvaluation as ResourcesMonthlyEvaluation;
use App\Models\FormHistory;
use App\Models\MonthlyEvaluation;
use App\Models\TransactionApprovers;
use App\Models\TransactionReceivers;
use App\Models\TransactionStatuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyEvaluationController extends Controller
{
    public function index(Request $request)
    {   
        $requestor_id = Auth::user()->employee_id;
        $keyword = request('keyword');

        $role = DB::table('users')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select([
                'users.id',
                'roles.key'
            ])
            ->where('employee_id', '=', $requestor_id)
            ->get()
            ->first();
        
        $key = ($role) ? $role->key : '';

        $query = DB::table('monthly_evaluations')
            ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            })
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = monthly_evaluations.id AND form_history.form_type = monthly_evaluations.form_type)')); 
            }) 
            ->select([
                'monthly_evaluations.id',
                'monthly_evaluations.code',
                'monthly_evaluations.employee_id',
                'monthly_evaluations.month',
                'monthly_evaluations.measures',
                'monthly_evaluations.development_plan',
                'monthly_evaluations.development_area',
                'monthly_evaluations.developmental_plan',
                'monthly_evaluations.total_grade',
                'monthly_evaluations.total_target',
                'monthly_evaluations.attachment',
                'monthly_evaluations.assessment',
                'monthly_evaluations.form_type',
                'monthly_evaluations.level',
                'monthly_evaluations.current_status',
                'monthly_evaluations.current_status_mark',
                'monthly_evaluations.requestor_id',
                'monthly_evaluations.requestor_remarks',
                'monthly_evaluations.date_evaluated',
                'monthly_evaluations.is_fulfilled',
                'monthly_evaluations.date_fulfilled',
                'monthly_evaluations.created_at',
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
                'form_history.status_mark',
                'form_history.review_date',
            ]);

        $monthly_evaluations = $query->where('monthly_evaluations.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $monthly_evaluations = $query
                ->where('monthly_evaluations.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('monthly_evaluations.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return ResourcesMonthlyEvaluation::collection($monthly_evaluations);
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required'],
            'developmental_plan' => ['required']
        ]);

        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'employees.first_name',
                'positions.position_name',
                'positions.id as position_id',
                'positions.department_id',
                'positions.subunit_id',
                'employee_positions.location_id',
                'employee_positions.company_id',
                'employee_positions.division_id',
                'employee_positions.division_cat_id',
                'employee_positions.job_level',
                'employee_positions.salary_structure',
                'employee_positions.jobrate_name',
                'employee_positions.allowance',
                'employee_positions.job_rate',
                'employee_positions.salary',
                'employee_positions.additional_rate',
            ])
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
                ->where('form_type', '=', 'monthly-evaluation')
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
                ->where('form_type', '=', 'monthly-evaluation')
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
                ->where('form_type', '=', 'monthly-evaluation')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        // check month 
        $month = DB::table('monthly_evaluations')
            ->select([
                'employee_id',
                'month'
            ])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->max('month');
        
        $month = ($month) ? $month + 1 : 1;

        $evaluation_count = DB::table('monthly_evaluations')
            ->select('id')
            ->where('employee_id', '=', $request->input('employee_id'))
            ->get()
            ->count();

        if ($evaluation_count >= 4) {
            throw ValidationException::withMessages([
                'employee_id' => ['Monthly Evaluation can only be done up to 4th Month.']
            ]);
        }

        $latest_evaluation = DB::table('monthly_evaluations')
            ->select('created_at')
            ->where('employee_id', '=', $request->input('employee_id'))
            ->latest()
            ->first();

        // if ($latest_evaluation) {
        //     $latest_evaluation_date = ($latest_evaluation) ? $latest_evaluation->created_at : '';
        //     $months = Carbon::now()->diffInMonths($latest_evaluation_date);
            
        //     if ($months < 1) {
        //         throw ValidationException::withMessages([
        //             'employee_id' => ['Evaluation is only allowed in monthly interval.']
        //         ]);
        //     }
        // }

        $monthly_evaluation = new MonthlyEvaluation();
        $monthly_evaluation->code = Helpers::generateCodeNewVersion('monthly_evaluations', 'MEF');
        $monthly_evaluation->employee_id = $request->input('employee_id');
        $monthly_evaluation->month = $month;
        $monthly_evaluation->measures = json_decode($request->input('measures'));
        $monthly_evaluation->development_plan = json_decode($request->input('development_plan'));
        $monthly_evaluation->development_area = json_decode($request->input('development_area'));
        $monthly_evaluation->developmental_plan = $request->input('developmental_plan');
        $monthly_evaluation->total_grade = $request->input('total_grade');
        $monthly_evaluation->total_target = $request->input('total_target');
        $monthly_evaluation->form_type = 'monthly-evaluation';
        $monthly_evaluation->level = ($isApprover) ? 2 : 1;
        $monthly_evaluation->current_status = 'for-approval';
        $monthly_evaluation->current_status_mark = 'FOR APPROVAL';
        $monthly_evaluation->requestor_id = Auth::user()->employee_id;
        $monthly_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $monthly_evaluation->date_evaluated = Carbon::now()->format('M d, Y h:i a');
        $monthly_evaluation->is_confirmed = 'PENDING';
        $monthly_evaluation->is_fulfilled = 'PENDING';

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/probi_attachments', $fileNameToStore);
        //     $monthly_evaluation->attachment = $fileNameToStore;
        // }

        $filenames = [];

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/monthly_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $monthly_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($monthly_evaluation->save()) {
            Helpers::LogActivity($monthly_evaluation->id, 'EVALUATION - MONTHLY EVALUATION', 'REQUESTED A NEW MONTHLY EVALUATION FORM');

            $form_history = new FormHistory();
            $form_history->code = $monthly_evaluation->code;
            $form_history->form_id = $monthly_evaluation->id;
            $form_history->form_type = $monthly_evaluation->form_type;
            $form_history->form_data = $monthly_evaluation->toJson();
            $form_history->status = ($isApprover) ? 'approved' : $monthly_evaluation->current_status;
            $form_history->status_mark = ($isApprover) ? 'APPROVED' : $monthly_evaluation->current_status_mark;
            $form_history->reviewer_id = $monthly_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $monthly_evaluation->requestor_remarks;
            $form_history->level = ($isApprover) ? 2 : 1;
            $form_history->requestor_id = $monthly_evaluation->requestor_id;
            $form_history->employee_id = $monthly_evaluation->employee_id;
            $form_history->is_fulfilled = $monthly_evaluation->is_fulfilled;
            $form_history->description = 'REQUESTING FOR MONTHLY EVALUATION APPROVAL';
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();

            $transaction_statuses = new TransactionStatuses();
            $transaction_statuses->code = $monthly_evaluation->code;
            $transaction_statuses->form_id = $monthly_evaluation->id;
            $transaction_statuses->form_type = $monthly_evaluation->form_type;
            $transaction_statuses->form_data = $monthly_evaluation->toJson();
            $transaction_statuses->status = ($isApprover) ? 'approved' : $monthly_evaluation->current_status;
            $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $monthly_evaluation->current_status_mark;
            $transaction_statuses->reviewer_id = $monthly_evaluation->requestor_id;
            $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
            $transaction_statuses->reviewer_action = 'assess';
            $transaction_statuses->remarks = $monthly_evaluation->requestor_remarks;
            $transaction_statuses->level = ($isApprover) ? 2 : 1;
            $transaction_statuses->requestor_id = $monthly_evaluation->requestor_id;
            $transaction_statuses->employee_id = $monthly_evaluation->employee_id;
            $transaction_statuses->is_fulfilled = $monthly_evaluation->is_fulfilled;
            $transaction_statuses->description = 'REQUESTING FOR MONTHLY EVALUATION APPROVAL';
            $transaction_statuses->reviewer_attachment = implode(',', $filenames);
            $transaction_statuses->save();

            $data_approvers = [];
            $approvers = $approvers->toArray();

            foreach ($approvers as $form) {
                $data_approvers[] = [
                    'transaction_id' => $monthly_evaluation->id,
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
                    'transaction_id' => $monthly_evaluation->id,
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
            
            return response()->json($monthly_evaluation);
        }
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required'],
            'developmental_plan' => ['required']
        ]);

        $duplicate_entry = DB::table('monthly_evaluations')
            ->select(['employee_id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('current_status', '!=', 'rejected')
            ->where('id', '!=', $id);

        // if ($duplicate_entry->count()) {
        //     throw ValidationException::withMessages([
        //         'employee_id' => ['Evaluation has been already filed for this employee.']
        //     ]);
        // }

        $monthly_evaluation = MonthlyEvaluation::findOrFail($id);
        $monthly_evaluation->employee_id = $request->input('employee_id');
        $monthly_evaluation->measures = json_decode($request->input('measures'));
        $monthly_evaluation->development_plan = json_decode($request->input('development_plan'));
        $monthly_evaluation->development_area = json_decode($request->input('development_area'));
        $monthly_evaluation->developmental_plan = $request->input('developmental_plan');
        $monthly_evaluation->total_grade = $request->input('total_grade');
        $monthly_evaluation->requestor_id = Auth::user()->employee_id;
        $monthly_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';

        $resubmit = false;

        if ($resubmit) {
            $monthly_evaluation->current_status = 'for-approval';
            $monthly_evaluation->current_status_mark = 'FOR APPROVAL';
        }

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/probi_attachments', $fileNameToStore);
        //     $monthly_evaluation->attachment = $fileNameToStore;
        // }

        $filenames = [];

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/monthly_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $monthly_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($monthly_evaluation->save()) {
            Helpers::LogActivity($monthly_evaluation->id, 'EVALUATION - MONTHLY EVALUATION', 'UPDATED MONTHLY EVALUATION FORM DATA');

            $form_history = FormHistory::where('form_id', $monthly_evaluation->id)
                ->where('form_type', 'monthly-evaluation')
                ->first();

            if ($resubmit) {
                $form_history = new FormHistory();
            }

            $form_history->form_id = $monthly_evaluation->id;
            $form_history->code = $monthly_evaluation->code;
            $form_history->form_type = $monthly_evaluation->form_type;
            $form_history->form_data = $monthly_evaluation->toJson();
            $form_history->status = $monthly_evaluation->current_status;
            $form_history->status_mark = $monthly_evaluation->current_status_mark;
            $form_history->reviewer_id = $monthly_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $monthly_evaluation->requestor_remarks;
            $form_history->level = $monthly_evaluation->level;
            $form_history->requestor_id = $monthly_evaluation->requestor_id;
            $form_history->employee_id = $monthly_evaluation->employee_id;
            $form_history->is_fulfilled = $monthly_evaluation->is_fulfilled;
            $form_history->description = ($resubmit) ? 'RESUBMISSION OF EVALUATION REVISION' : 'REQUESTING FOR EVALUATION APPROVAL';
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();

            $monthly_evaluations = DB::table('monthly_evaluations')
                ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('form_history', function($join) { 
                    $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = monthly_evaluations.id AND form_history.form_type = monthly_evaluations.form_type)')); 
                }) 
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->select([
                    'monthly_evaluations.id',
                    'monthly_evaluations.code',
                    'monthly_evaluations.employee_id',
                    'monthly_evaluations.month',
                    'monthly_evaluations.measures',
                    'monthly_evaluations.development_plan',
                    'monthly_evaluations.development_area',
                    'monthly_evaluations.developmental_plan',
                    'monthly_evaluations.total_grade',
                    'monthly_evaluations.total_target',
                    'monthly_evaluations.attachment',
                    'monthly_evaluations.assessment',
                    'monthly_evaluations.form_type',
                    'monthly_evaluations.level',
                    'monthly_evaluations.current_status',
                    'monthly_evaluations.current_status_mark',
                    'monthly_evaluations.requestor_id',
                    'monthly_evaluations.requestor_remarks',
                    'monthly_evaluations.date_evaluated',
                    'monthly_evaluations.is_fulfilled',
                    'monthly_evaluations.date_fulfilled',
                    'monthly_evaluations.created_at',
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

                    'form_history.status_mark',
                    'form_history.review_date',
                ])
                ->where('monthly_evaluations.id', '=', $monthly_evaluation->id)
                ->get();
            
            return ResourcesMonthlyEvaluation::collection($monthly_evaluations);
        }
    }

    public function destroy($id)
    {
        $monthly_evaluation = MonthlyEvaluation::findOrFail($id);

        if ($monthly_evaluation->delete()) {
            Helpers::LogActivity($monthly_evaluation->id, 'EVALUATION - MONTHLY EVALUATION', 'DELETED MONTHLY EVALUATION REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $monthly_evaluation->form_type)
                ->delete();

            $transaction_statuses = DB::table('transaction_statuses')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', $monthly_evaluation->form_type)
                ->delete();

            $transaction_approvers = DB::table('transaction_approvers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $monthly_evaluation->form_type)
                ->delete();

            $transaction_receivers = DB::table('transaction_receivers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $monthly_evaluation->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }

    public function resubmitMonthlyEvaluation(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $monthly_evaluation = MonthlyEvaluation::findOrFail($id);

        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select(['positions.subunit_id', 'positions.position_name'])
            ->where('employees.id', '=', $monthly_evaluation->employee_id)
            ->get()
            ->first();

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;

        if ($isLoggedIn) { 
            $current_user = Auth::user();

            $approver = DB::table('forms')
                ->select(['id'])
                ->where('form_type', '=', 'monthly-evaluation')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $employee->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        if ($monthly_evaluation->current_status == 'rejected' || $monthly_evaluation->current_status == 'cancelled') {
            $monthly_evaluation->level = ($isApprover) ? 2 : 1;
            $monthly_evaluation->current_status = 'for-approval';
            $monthly_evaluation->current_status_mark = 'FOR APPROVAL';
            $monthly_evaluation->is_fulfilled = 'PENDING';

            if ($monthly_evaluation->save()) {
                Helpers::LogActivity($monthly_evaluation->id, 'FORM REQUISITION - MONTHLY EVALUATION FORM', 'RE-SUBMITTED MONTHLY EVALUATION FORM REQUEST');

                $form_history = new FormHistory();
                $form_history->form_id = $monthly_evaluation->id;
                $form_history->code = $monthly_evaluation->code;
                $form_history->form_type = $monthly_evaluation->form_type;
                $form_history->form_data = $monthly_evaluation->toJson();
                $form_history->status = ($isApprover) ? 'approved' : $monthly_evaluation->current_status;
                $form_history->status_mark = ($isApprover) ? 'APPROVED' : $monthly_evaluation->current_status_mark;
                $form_history->reviewer_id = $monthly_evaluation->requestor_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'assess';
                $form_history->remarks = $monthly_evaluation->remarks;
                $form_history->level = ($isApprover) ? 2 : 1;
                $form_history->requestor_id = $monthly_evaluation->requestor_id;
                $form_history->employee_id = $monthly_evaluation->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'RESUBMISSION OF MONTHLY EVALUATION REVISION';
                $form_history->save();

                $delete_transaction_statuses = DB::table('transaction_statuses')
                    ->where('form_id', '=', $monthly_evaluation->id)
                    ->where('form_type', '=', $monthly_evaluation->form_type)
                    ->delete();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $monthly_evaluation->id;
                $transaction_statuses->code = $monthly_evaluation->code;
                $transaction_statuses->form_type = $monthly_evaluation->form_type;
                $transaction_statuses->form_data = $monthly_evaluation->toJson();
                $transaction_statuses->status = ($isApprover) ? 'approved' : $monthly_evaluation->current_status;
                $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $monthly_evaluation->current_status_mark;
                $transaction_statuses->reviewer_id = $monthly_evaluation->requestor_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'assess';
                $transaction_statuses->remarks = $monthly_evaluation->remarks;
                $transaction_statuses->level = ($isApprover) ? 2 : 1;
                $transaction_statuses->requestor_id = $monthly_evaluation->requestor_id;
                $transaction_statuses->employee_id = $monthly_evaluation->id;
                $transaction_statuses->is_fulfilled = 'PENDING';
                $transaction_statuses->description = 'RESUBMISSION OF MONTHLY EVALUATION REVISION';
                $transaction_statuses->save();
            }
        }
    }
}
