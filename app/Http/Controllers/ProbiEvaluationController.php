<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\ProbiEvaluation as ResourcesProbiEvaluation;
use App\Http\Resources\EmployeeState as ResourcesEmployeeState;
use App\Models\FormHistory;
use App\Models\ProbiEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProbiEvaluationController extends Controller
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

        $query = DB::table('probi_evaluations')
            ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            }) 
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = probi_evaluations.id AND form_history.form_type = probi_evaluations.form_type)')); 
            })
            ->select([
                'probi_evaluations.id',
                'probi_evaluations.code',
                'probi_evaluations.employee_id',
                'probi_evaluations.measures',
                'probi_evaluations.assessment',
                'probi_evaluations.assessment_mark',
                'probi_evaluations.total_grade',
                'probi_evaluations.attachment',
                'probi_evaluations.form_type',
                'probi_evaluations.level',
                'probi_evaluations.current_status',
                'probi_evaluations.current_status_mark',
                'probi_evaluations.requestor_id',
                'probi_evaluations.requestor_remarks',
                'probi_evaluations.date_evaluated',
                'probi_evaluations.is_fulfilled',
                'probi_evaluations.date_fulfilled',
                'probi_evaluations.created_at',
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
                'subunits.subunit_name'
            ]);
        
        // if (!empty($key) && $key == 'lrEdpvD0lbljzpVcdi5z') {
        //     $probi_evaluations = $query->get();
        // } else {
        //     $probi_evaluations = $query->where('probi_evaluations.requestor_id', '=', $requestor_id)->get();
        // }

        $probi_evaluations = $query->where('probi_evaluations.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $probi_evaluations = $query
                ->where('probi_evaluations.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('probi_evaluations.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return ResourcesProbiEvaluation::collection($probi_evaluations);
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('probi_evaluations')
            ->select(['employee_id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('current_status', '!=', 'rejected');

        // if ($duplicate_entry->count()) {
        //     throw ValidationException::withMessages([
        //         'employee_id' => ['Evaluation has been already filed for this employee.']
        //     ]);
        // }

        $filenames = [];
        $probi_evaluation = new ProbiEvaluation();
        $probi_evaluation->code = Helpers::generateCodeNewVersion('probi_evaluations', 'PEF');
        $probi_evaluation->employee_id = $request->input('employee_id');
        $probi_evaluation->measures = json_decode($request->input('measures'));
        $probi_evaluation->assessment = $request->input('assessment');
        $probi_evaluation->assessment_mark = $request->input('assessment_mark');
        $probi_evaluation->total_grade = $request->input('total_grade');
        $probi_evaluation->form_type = 'probi-evaluation';
        $probi_evaluation->level = 1;
        $probi_evaluation->current_status = 'for-approval';
        $probi_evaluation->current_status_mark = 'FOR APPROVAL';
        $probi_evaluation->requestor_id = Auth::user()->employee_id;
        $probi_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $probi_evaluation->date_evaluated = Carbon::now()->format('M d, Y h:i a');
        // $probi_evaluation->created_at = Carbon::now()->format('M d, Y H:i a');
        // $probi_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');
        $probi_evaluation->is_confirmed = 'PENDING';
        $probi_evaluation->is_fulfilled = 'PENDING';

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/probi_attachments', $fileNameToStore);
        //     $probi_evaluation->attachment = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/probi_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $probi_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($probi_evaluation->save()) {
            Helpers::LogActivity($probi_evaluation->id, 'EVALUATION - PROBATIONARY EVALUATION', 'REQUESTED A NEW PROBATIONARY EVALUATION FORM');

            $form_history = new FormHistory();
            $form_history->code = $probi_evaluation->code;
            $form_history->form_id = $probi_evaluation->id;
            $form_history->form_type = $probi_evaluation->form_type;
            $form_history->form_data = $probi_evaluation->toJson();
            $form_history->status = $probi_evaluation->current_status;
            $form_history->status_mark = $probi_evaluation->current_status_mark;
            $form_history->reviewer_id = $probi_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $probi_evaluation->requestor_remarks;
            $form_history->level = $probi_evaluation->level;
            $form_history->requestor_id = $probi_evaluation->requestor_id;
            $form_history->employee_id = $probi_evaluation->employee_id;
            $form_history->is_fulfilled = $probi_evaluation->is_fulfilled;
            $form_history->description = 'REQUESTING FOR EVALUATION APPROVAL';
            // $form_history->created_at = Carbon::now()->format('M d, Y H:i a');
            // $form_history->updated_at = Carbon::now()->format('M d, Y H:i a');
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();

            return response()->json($probi_evaluation);
        }
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('probi_evaluations')
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
        $probi_evaluation = ProbiEvaluation::findOrFail($id);
        $probi_evaluation->employee_id = $request->input('employee_id');
        $probi_evaluation->measures = json_decode($request->input('measures'));
        $probi_evaluation->assessment = $request->input('assessment');
        $probi_evaluation->assessment_mark = $request->input('assessment_mark');
        $probi_evaluation->total_grade = $request->input('total_grade');
        $probi_evaluation->requestor_id = Auth::user()->employee_id;
        $probi_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        // $probi_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');

        $resubmit = ($probi_evaluation->current_status == 'rejected') ? true : false;

        if ($resubmit) {
            $probi_evaluation->current_status = 'for-approval';
            $probi_evaluation->current_status_mark = 'FOR APPROVAL';
        }

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/probi_attachments', $fileNameToStore);
        //     $probi_evaluation->attachment = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/probi_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $probi_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($probi_evaluation->save()) {
            Helpers::LogActivity($probi_evaluation->id, 'EVALUATION - PROBATIONARY EVALUATION', 'UPDATED PROBATIONARY EVALUATION FORM DATA');

            $form_history = FormHistory::where('form_id', $probi_evaluation->id)
            ->where('form_type', 'probi-evaluation')
            ->first();

            if ($resubmit) {
                $form_history = new FormHistory();
            }

            $form_history->form_id = $probi_evaluation->id;
            $form_history->code = $probi_evaluation->code;
            $form_history->form_type = $probi_evaluation->form_type;
            $form_history->form_data = $probi_evaluation->toJson();
            $form_history->status = $probi_evaluation->current_status;
            $form_history->status_mark = $probi_evaluation->current_status_mark;
            $form_history->reviewer_id = $probi_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->remarks = $probi_evaluation->requestor_remarks;
            $form_history->level = $probi_evaluation->level;
            $form_history->requestor_id = $probi_evaluation->requestor_id;
            $form_history->employee_id = $probi_evaluation->employee_id;
            $form_history->is_fulfilled = $probi_evaluation->is_fulfilled;
            $form_history->description = ($resubmit) ? 'RESUBMISSION OF EVALUATION REVISION' : 'REQUESTING FOR EVALUATION APPROVAL';
            // $form_history->updated_at = Carbon::now()->format('M d, Y H:i a');
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();

            $probi_evaluations = DB::table('probi_evaluations')
                ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->leftJoin('form_history', function($join) { 
                    $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = probi_evaluations.id)')); 
                }) 
                ->select([
                    'probi_evaluations.id',
                    'probi_evaluations.code',
                    'probi_evaluations.employee_id',
                    'probi_evaluations.measures',
                    'probi_evaluations.total_grade',
                    'probi_evaluations.attachment',
                    'probi_evaluations.assessment',
                    'probi_evaluations.assessment_mark',
                    'probi_evaluations.form_type',
                    'probi_evaluations.level',
                    'probi_evaluations.current_status',
                    'probi_evaluations.current_status_mark',
                    'probi_evaluations.requestor_id',
                    'probi_evaluations.requestor_remarks',
                    'probi_evaluations.date_evaluated',
                    'probi_evaluations.is_fulfilled',
                    'probi_evaluations.date_fulfilled',
                    'probi_evaluations.created_at',
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
                    'subunits.subunit_name'
                ])
                ->where('probi_evaluations.id', '=', $probi_evaluation->id)
                ->get();
            
            return ResourcesProbiEvaluation::collection($probi_evaluations);
        }
    }

    public function destroy($id)
    {
        $probi_evaluation = ProbiEvaluation::findOrFail($id);

        if ($probi_evaluation->delete()) {
            Helpers::LogActivity($probi_evaluation->id, 'EVALUATION - PROBATIONARY EVALUATION', 'DELETED PROBATIONARY EVALUATION REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $probi_evaluation->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }

    public function checkEmployeeStatus(Request $request) 
    {
        $employee_id = $request->employee_id;

        $query = DB::table('employees')
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
            ->where('employees.current_status', '=', 'approved')
            ->where('employees.id', '=', $employee_id)
            ->first();

        return response()->json($query);
    }
}
