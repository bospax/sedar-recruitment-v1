<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\AnnualEvaluation as ResourcesAnnualEvaluation;
use App\Models\FormHistory;
use App\Models\AnnualEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnualEvaluationController extends Controller
{
    public function index()
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

        $query = DB::table('annual_evaluations')
            ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', function($join) { 
                $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            }) 
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = annual_evaluations.id AND form_history.form_type = annual_evaluations.form_type)')); 
            })
            ->select([
                'annual_evaluations.id',
                'annual_evaluations.code',
                'annual_evaluations.employee_id',
                'annual_evaluations.measures',
                'annual_evaluations.assessment',
                'annual_evaluations.assessment_mark',
                'annual_evaluations.total_grade',
                'annual_evaluations.performance_discussion',
                'annual_evaluations.attachment',
                'annual_evaluations.form_type',
                'annual_evaluations.level',
                'annual_evaluations.current_status',
                'annual_evaluations.current_status_mark',
                'annual_evaluations.requestor_id',
                'annual_evaluations.requestor_remarks',
                'annual_evaluations.date_evaluated',
                'annual_evaluations.is_fulfilled',
                'annual_evaluations.date_fulfilled',
                'annual_evaluations.created_at',
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
        //     $annual_evaluations = $query->get();
        // } else {
        //     $annual_evaluations = $query->where('annual_evaluations.requestor_id', '=', $requestor_id)->get();
        // }

        $annual_evaluations = $query->where('annual_evaluations.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $annual_evaluations = $query
                ->where('annual_evaluations.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('annual_evaluations.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return ResourcesAnnualEvaluation::collection($annual_evaluations);
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('annual_evaluations')
            ->select(['employee_id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->where('current_status', '!=', 'rejected');

        // if ($duplicate_entry->count()) {
        //     throw ValidationException::withMessages([
        //         'employee_id' => ['Evaluation has been already filed for this employee.']
        //     ]);
        // }

        $annual_evaluation = new AnnualEvaluation();
        $annual_evaluation->code = Helpers::generateCodeNewVersion('annual_evaluations', 'AEF');
        $annual_evaluation->employee_id = $request->input('employee_id');
        $annual_evaluation->measures = json_decode($request->input('measures'));
        $annual_evaluation->assessment = $request->input('assessment');
        $annual_evaluation->assessment_mark = $request->input('assessment_mark');
        $annual_evaluation->total_grade = $request->input('total_grade');
        $annual_evaluation->performance_discussion = json_decode($request->input('performance_discussion'));
        $annual_evaluation->form_type = 'annual-evaluation';
        $annual_evaluation->level = 1;
        $annual_evaluation->current_status = 'for-approval';
        $annual_evaluation->current_status_mark = 'FOR APPROVAL';
        $annual_evaluation->requestor_id = Auth::user()->employee_id;
        $annual_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $annual_evaluation->date_evaluated = Carbon::now()->format('M d, Y h:i a');
        // $annual_evaluation->created_at = Carbon::now()->format('M d, Y H:i a');
        // $annual_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');
        $annual_evaluation->is_fulfilled = 'PENDING';

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/annual_attachments', $fileNameToStore);
        //     $annual_evaluation->attachment = $fileNameToStore;
        // }

        $filenames = [];
        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/annual_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $annual_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($annual_evaluation->save()) {
            Helpers::LogActivity($annual_evaluation->id, 'EVALUATION - ANNUAL EVALUATION', 'REQUESTED A NEW ANNUAL EVALUATION FORM');

            $form_history = new FormHistory();
            $form_history->code = $annual_evaluation->code;
            $form_history->form_id = $annual_evaluation->id;
            $form_history->form_type = $annual_evaluation->form_type;
            $form_history->form_data = $annual_evaluation->toJson();
            $form_history->status = $annual_evaluation->current_status;
            $form_history->status_mark = $annual_evaluation->current_status_mark;
            $form_history->reviewer_id = $annual_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $annual_evaluation->requestor_remarks;
            $form_history->level = $annual_evaluation->level;
            $form_history->requestor_id = $annual_evaluation->requestor_id;
            $form_history->employee_id = $annual_evaluation->employee_id;
            $form_history->is_fulfilled = $annual_evaluation->is_fulfilled;
            $form_history->description = 'REQUESTING FOR EVALUATION APPROVAL';
            // $form_history->created_at = Carbon::now()->format('M d, Y H:i a');
            // $form_history->updated_at = Carbon::now()->format('M d, Y H:i a');
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();

            return response()->json($annual_evaluation);
        }
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $duplicate_entry = DB::table('annual_evaluations')
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
        $annual_evaluation = AnnualEvaluation::findOrFail($id);
        $annual_evaluation->employee_id = $request->input('employee_id');
        $annual_evaluation->measures = json_decode($request->input('measures'));
        $annual_evaluation->assessment = $request->input('assessment');
        $annual_evaluation->assessment_mark = $request->input('assessment_mark');
        $annual_evaluation->total_grade = $request->input('total_grade');
        $annual_evaluation->performance_discussion = json_decode($request->input('performance_discussion'));
        $annual_evaluation->requestor_id = Auth::user()->employee_id;
        $annual_evaluation->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        // $annual_evaluation->updated_at = Carbon::now()->format('M d, Y H:i a');

        $resubmit = ($annual_evaluation->current_status == 'rejected') ? true : false;

        if ($resubmit) {
            $annual_evaluation->current_status = 'for-approval';
            $annual_evaluation->current_status_mark = 'FOR APPROVAL';
        }

        // if ($request->hasFile('attachment')) {
        //     $filenameWithExt = $request->file('attachment')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('attachment')->storeAs('public/annual_attachments', $fileNameToStore);
        //     $annual_evaluation->attachment = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/annual_attachments', $fileNameToStore);
                $path2 = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $annual_evaluation->attachment = implode(',', $filenames);
            }
        }

        if ($annual_evaluation->save()) {
            Helpers::LogActivity($annual_evaluation->id, 'EVALUATION - ANNUAL EVALUATION', 'UPDATED ANNUAL EVALUATION FORM DATA');

            $form_history = FormHistory::where('form_id', $annual_evaluation->id)
            ->where('form_type', 'annual-evaluation')
            ->first();

            if ($resubmit) {
                $form_history = new FormHistory();
            }

            $form_history->form_id = $annual_evaluation->id;
            $form_history->form_type = $annual_evaluation->form_type;
            $form_history->form_data = $annual_evaluation->toJson();
            $form_history->status = $annual_evaluation->current_status;
            $form_history->status_mark = $annual_evaluation->current_status_mark;
            $form_history->reviewer_id = $annual_evaluation->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->remarks = $annual_evaluation->requestor_remarks;
            $form_history->level = $annual_evaluation->level;
            $form_history->requestor_id = $annual_evaluation->requestor_id;
            $form_history->employee_id = $annual_evaluation->employee_id;
            $form_history->is_fulfilled = $annual_evaluation->is_fulfilled;
            $form_history->description = ($resubmit) ? 'RESUBMISSION OF EVALUATION REVISION' : 'REQUESTING FOR EVALUATION APPROVAL';
            // $form_history->updated_at = Carbon::now()->format('M d, Y H:i a');
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();

            $annual_evaluations = DB::table('annual_evaluations')
                ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->leftJoin('form_history', function($join) { 
                    $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = annual_evaluations.id)')); 
                }) 
                ->select([
                    'annual_evaluations.id',
                    'annual_evaluations.code',
                    'annual_evaluations.employee_id',
                    'annual_evaluations.measures',
                    'annual_evaluations.total_grade',
                    'annual_evaluations.performance_discussion',
                    'annual_evaluations.attachment',
                    'annual_evaluations.assessment',
                    'annual_evaluations.assessment_mark',
                    'annual_evaluations.form_type',
                    'annual_evaluations.level',
                    'annual_evaluations.current_status',
                    'annual_evaluations.current_status_mark',
                    'annual_evaluations.requestor_id',
                    'annual_evaluations.requestor_remarks',
                    'annual_evaluations.date_evaluated',
                    'annual_evaluations.is_fulfilled',
                    'annual_evaluations.date_fulfilled',
                    'annual_evaluations.created_at',
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
                ->where('annual_evaluations.id', '=', $annual_evaluation->id)
                ->get();
            
            return ResourcesAnnualEvaluation::collection($annual_evaluations);
        }
    }

    public function destroy($id)
    {
        $annual_evaluation = AnnualEvaluation::findOrFail($id);

        if ($annual_evaluation->delete()) {
            Helpers::LogActivity($annual_evaluation->id, 'EVALUATION - ANNUAL EVALUATION', 'DELETED ANNUAL EVALUATION REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $annual_evaluation->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }
}
