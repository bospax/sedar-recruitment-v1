<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\EmployeeDataChange;
use App\Http\Resources\EmployeeDataChangeDetail;
use App\Models\EmployeeDataChange as ModelsEmployeeDataChange;
use App\Models\FormHistory;
use App\Models\TransactionApprovers;
use App\Models\TransactionReceivers;
use App\Models\TransactionStatuses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeDataChangeController extends Controller
{
    public function index()
    {
        $requestor_id = Auth::user()->employee_id;
        $keyword = request('keyword');

        $query = DB::table('employee_datachanges')
            ->leftJoin('employees', 'employee_datachanges.employee_id', '=', 'employees.id')
            ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
            ->leftJoin('employees AS requestor', 'employee_datachanges.requestor_id', '=', 'requestor.id')

            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_datachanges.current_position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_datachanges.current_location_id', '=', 'locations.id')
            ->leftJoin('companies', 'employee_datachanges.current_company_id', '=', 'companies.id')
            ->leftJoin('divisions', 'employee_datachanges.current_division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_datachanges.current_division_cat_id', '=', 'division_categories.id')

            ->leftJoin('positions as new_positions', 'employee_datachanges.new_position_id', '=', 'new_positions.id')
            ->leftJoin('departments as new_departments', 'employee_datachanges.new_department_id', '=', 'new_departments.id')
            ->leftJoin('subunits as new_subunits', 'employee_datachanges.new_subunit_id', '=', 'new_subunits.id')
            ->leftJoin('locations as new_locations', 'employee_datachanges.new_location_id', '=', 'new_locations.id')
            ->leftJoin('companies as new_companies', 'employee_datachanges.new_company_id', '=', 'new_companies.id')
            ->leftJoin('divisions as new_divisions', 'employee_datachanges.new_division_id', '=', 'new_divisions.id')
            ->leftJoin('division_categories as new_division_categories', 'employee_datachanges.new_division_cat_id', '=', 'new_division_categories.id')
            
            ->leftJoin('form_history', function($join) { 
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = employee_datachanges.id AND form_history.form_type = employee_datachanges.form_type)')); 
            }) 
            ->select([
                'employee_datachanges.id',
                'employee_datachanges.code',
                'employee_datachanges.employee_id',
                'employee_datachanges.attachment',
                'employee_datachanges.change_reason',
                'employee_datachanges.form_type',
                'employee_datachanges.level',
                'employee_datachanges.current_status',
                'employee_datachanges.current_status_mark',
                'employee_datachanges.requestor_id',
                'employee_datachanges.requestor_remarks',
                'employee_datachanges.is_fulfilled',
                'employee_datachanges.date_fulfilled',
                'employee_datachanges.created_at',
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
                
                'employee_positions.position_id',
                'positions.subunit_id',
                'positions.department_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.order',
                'jobbands.subunit_bound',
                'jobbands.jobband_name',
                'locations.location_name',
                'companies.company_name',
                'divisions.division_name',
                'division_categories.category_name',
                'locations.id as location_id',
                'companies.id as company_id',
                'divisions.id as division_id',
                'division_categories.id as category_id',

                'new_positions.position_name as new_position_name',
                'new_departments.department_name as new_department_name',
                'new_subunits.subunit_name as new_subunit_name',
                'new_locations.location_name as new_location_name',
                'new_companies.company_name as new_company_name',
                'new_divisions.division_name as new_division_name',
                'new_division_categories.category_name as new_category_name',

                'employee_datachanges.new_position_id',
                'employee_datachanges.new_department_id',
                'employee_datachanges.new_subunit_id',
                'employee_datachanges.new_location_id',
                'employee_datachanges.new_company_id',
                'employee_datachanges.new_division_id',
                'employee_datachanges.new_division_cat_id',

                'employee_datachanges.current_job_level',
                'employee_datachanges.current_salary_structure',
                'employee_datachanges.current_jobrate_name',
                'employee_datachanges.current_allowance',
                'employee_datachanges.current_job_rate',
                'employee_datachanges.current_salary',
                'employee_datachanges.current_additional_rate',

                'employee_datachanges.new_job_level',
                'employee_datachanges.new_salary_structure',
                'employee_datachanges.new_jobrate_name',
                'employee_datachanges.new_allowance',
                'employee_datachanges.new_job_rate',
                'employee_datachanges.new_salary',
                'employee_datachanges.new_additional_rate',

                'form_history.status_mark',
                'form_history.review_date',
            ]);

        $employee_datachanges = $query->where('employee_datachanges.requestor_id', '=', $requestor_id)->paginate(100);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $employee_datachanges = $query
                ->where('employee_datachanges.requestor_id', '=', $requestor_id)
                ->where(function($query) use ($value){
                    $query->where('employees.last_name', 'LIKE', $value)
                        ->orWhere('employees.middle_name', 'LIKE', $value)
                        ->orWhere('employees.first_name', 'LIKE', $value)
                        ->orWhere('employee_datachanges.code', 'LIKE', $value);
                })
                ->paginate(100);
        }
        
        return EmployeeDataChangeDetail::collection($employee_datachanges);
    }

    public function show($id)
    {
        
    }

    public function store(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
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
                ->where('form_type', '=', 'employee-datachange')
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
                ->where('form_type', '=', 'employee-datachange')
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
                ->where('form_type', '=', 'employee-datachange')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        $current_position_id = $employee->position_id;
        $current_department_id = $employee->department_id;
        $current_subunit_id = $employee->subunit_id;
        $current_location_id = $employee->location_id;
        $current_company_id = $employee->company_id;
        $current_division_id = $employee->division_id;
        $current_division_cat_id = $employee->division_cat_id;

        $new_position_id = $request->input('new_position_id');
        $new_department_id = $request->input('new_department_id');
        $new_subunit_id = $request->input('new_subunit_id');
        $new_location_id = $request->input('new_location_id');
        $new_company_id = $request->input('new_company_id');
        $new_division_id = $request->input('new_division_id');
        $new_division_cat_id = $request->input('new_division_cat_id');

        $filenames = [];
        $employee_datachange = new ModelsEmployeeDataChange();
        $employee_datachange->code = Helpers::generateCodeNewVersion('employee_datachanges', 'DCF');
        $employee_datachange->employee_id = $request->input('employee_id');

        $employee_datachange->current_position_id = $current_position_id;
        $employee_datachange->current_department_id = $current_department_id;
        $employee_datachange->current_subunit_id = $current_subunit_id;
        $employee_datachange->current_location_id = $current_location_id;
        $employee_datachange->current_company_id = $current_company_id;
        $employee_datachange->current_division_id = $current_division_id;
        $employee_datachange->current_division_cat_id = $current_division_cat_id;
        $employee_datachange->new_position_id = $new_position_id;
        $employee_datachange->new_department_id = $new_department_id;
        $employee_datachange->new_subunit_id = $new_subunit_id;
        $employee_datachange->new_location_id = $new_location_id;
        $employee_datachange->new_company_id = $new_company_id;
        $employee_datachange->new_division_id = $new_division_id;
        $employee_datachange->new_division_cat_id = $new_division_cat_id;

        $new_structures = explode('|', $request->input('new_salary_structure'));
        $new_job_level = ($request->input('new_salary_structure')) ? trim($new_structures[0]) : '';
        $new_salary_structure = ($request->input('new_salary_structure')) ? trim($new_structures[1]) : '';
        $new_jobrate_name = ($request->input('new_salary_structure')) ? trim($new_structures[2]) : '';

        $employee_datachange->new_jobrate_name = $new_jobrate_name;
        $employee_datachange->new_salary_structure = $new_salary_structure;
        $employee_datachange->new_job_level = $new_job_level;
        $employee_datachange->new_additional_rate = (float) str_replace(',', '', $request->input('new_additional_rate'));
        $employee_datachange->new_allowance = (float) str_replace(',', '', $request->input('new_allowance'));
        $employee_datachange->new_job_rate = (float) str_replace(',', '', $request->input('new_job_rate'));
        $employee_datachange->new_salary = (float) str_replace(',', '', $request->input('new_salary'));

        $employee_datachange->current_jobrate_name = $employee->jobrate_name;
        $employee_datachange->current_salary_structure = $employee->salary_structure;
        $employee_datachange->current_job_level = $employee->job_level;
        $employee_datachange->current_additional_rate = $employee->additional_rate;
        $employee_datachange->current_allowance = $employee->allowance;
        $employee_datachange->current_job_rate = $employee->job_rate;
        $employee_datachange->current_salary = $employee->salary;

        $employee_datachange->change_reason = $request->input('change_reason');
        $employee_datachange->form_type = 'employee-datachange';
        $employee_datachange->level = ($isApprover) ? 2 : 1;
        $employee_datachange->current_status = 'for-approval';
        $employee_datachange->current_status_mark = 'FOR APPROVAL';
        $employee_datachange->requestor_id = Auth::user()->employee_id;
        $employee_datachange->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';
        $employee_datachange->is_fulfilled = 'PENDING';

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
                $employee_datachange->attachment = implode(',', $filenames);
            }
        }

        if ($employee_datachange->save()) {
            Helpers::LogActivity($employee_datachange->id, 'EMPLOYEE DATACHANGE', 'REQUESTED A NEW EMPLOYEE DATACHANGE FORM');

            $form_history = new FormHistory();
            $form_history->code = $employee_datachange->code;
            $form_history->form_id = $employee_datachange->id;
            $form_history->form_type = $employee_datachange->form_type;
            $form_history->form_data = $employee_datachange->toJson();
            $form_history->status = ($isApprover) ? 'approved' : $employee_datachange->current_status;
            $form_history->status_mark = ($isApprover) ? 'APPROVED' : $employee_datachange->current_status_mark;
            $form_history->reviewer_id = $employee_datachange->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $employee_datachange->requestor_remarks;
            $form_history->level = ($isApprover) ? 2 : 1;
            $form_history->requestor_id = $employee_datachange->requestor_id;
            $form_history->employee_id = $employee_datachange->employee_id;
            $form_history->is_fulfilled = $employee_datachange->is_fulfilled;
            $form_history->description = 'REQUESTING FOR DATACHANGE APPROVAL';
            $form_history->reviewer_attachment = implode(',', $filenames);
            $form_history->save();

            $transaction_statuses = new TransactionStatuses();
            $transaction_statuses->code = $employee_datachange->code;
            $transaction_statuses->form_id = $employee_datachange->id;
            $transaction_statuses->form_type = $employee_datachange->form_type;
            $transaction_statuses->form_data = $employee_datachange->toJson();
            $transaction_statuses->status = ($isApprover) ? 'approved' : $employee_datachange->current_status;
            $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $employee_datachange->current_status_mark;
            $transaction_statuses->reviewer_id = $employee_datachange->requestor_id;
            $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
            $transaction_statuses->reviewer_action = 'assess';
            $transaction_statuses->remarks = $employee_datachange->requestor_remarks;
            $transaction_statuses->level = ($isApprover) ? 2 : 1;
            $transaction_statuses->requestor_id = $employee_datachange->requestor_id;
            $transaction_statuses->employee_id = $employee_datachange->employee_id;
            $transaction_statuses->is_fulfilled = $employee_datachange->is_fulfilled;
            $transaction_statuses->description = 'REQUESTING FOR DATACHANGE APPROVAL';
            $transaction_statuses->reviewer_attachment = implode(',', $filenames);
            $transaction_statuses->save();

            $data_approvers = [];
            $approvers = $approvers->toArray();

            foreach ($approvers as $form) {
                $data_approvers[] = [
                    'transaction_id' => $employee_datachange->id,
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
                    'transaction_id' => $employee_datachange->id,
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

            return response()->json($employee_datachange);
        }
    }

    public function update(Request $request, $id)
    {
        date_default_timezone_set('Asia/Manila');

        $this->validate($request, [
            'employee_id' => ['required']
        ]);

        $new_position_id = $request->input('new_position_id');
        $new_department_id = $request->input('new_department_id');
        $new_subunit_id = $request->input('new_subunit_id');
        $new_location_id = $request->input('new_location_id');
        $new_company_id = $request->input('new_company_id');
        $new_division_id = $request->input('new_division_id');
        $new_division_cat_id = $request->input('new_division_cat_id');

        $filenames = [];
        $employee_datachange = ModelsEmployeeDataChange::findOrFail($id);
        $employee_datachange->new_position_id = $new_position_id;
        $employee_datachange->new_department_id = $new_department_id;
        $employee_datachange->new_subunit_id = $new_subunit_id;
        $employee_datachange->new_location_id = $new_location_id;
        $employee_datachange->new_company_id = $new_company_id;
        $employee_datachange->new_division_id = $new_division_id;
        $employee_datachange->new_division_cat_id = $new_division_cat_id;
        $employee_datachange->requestor_remarks = ($request->input('requestor_remarks')) ? $request->input('requestor_remarks') : '';

        $new_structures = explode('|', $request->input('new_salary_structure'));
        $new_job_level = ($request->input('new_salary_structure')) ? trim($new_structures[0]) : '';
        $new_salary_structure = ($request->input('new_salary_structure')) ? trim($new_structures[1]) : '';
        $new_jobrate_name = ($request->input('new_salary_structure')) ? trim($new_structures[2]) : '';

        $employee_datachange->new_jobrate_name = $new_jobrate_name;
        $employee_datachange->new_salary_structure = $new_salary_structure;
        $employee_datachange->new_job_level = $new_job_level;
        $employee_datachange->new_additional_rate = (float) str_replace(',', '', $request->input('new_additional_rate'));
        $employee_datachange->new_allowance = (float) str_replace(',', '', $request->input('new_allowance'));
        $employee_datachange->new_job_rate = (float) str_replace(',', '', $request->input('new_job_rate'));
        $employee_datachange->new_salary = (float) str_replace(',', '', $request->input('new_salary'));

        $employee_datachange->change_reason = $request->input('change_reason');


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
                $employee_datachange->attachment = implode(',', $filenames);
            }
        }

        if ($employee_datachange->save()) {
            Helpers::LogActivity($employee_datachange->id, 'DATACHANGE', 'UPDATED DATACHANGE FORM DATA');

            $form_history = FormHistory::where('form_id', $employee_datachange->id)
                ->where('form_type', 'employee-datachange')
                ->first();

            // $form_history->form_id = $employee_datachange->id;
            // $form_history->form_type = $employee_datachange->form_type;
            $form_history->form_data = $employee_datachange->toJson();
            // $form_history->status = $employee_datachange->current_status;
            // $form_history->status_mark = $employee_datachange->current_status_mark;
            $form_history->reviewer_id = $employee_datachange->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            // $form_history->remarks = $employee_datachange->requestor_remarks;
            // $form_history->level = $employee_datachange->level;
            // $form_history->requestor_id = $employee_datachange->requestor_id;
            // $form_history->employee_id = $employee_datachange->employee_id;
            // $form_history->is_fulfilled = $employee_datachange->is_fulfilled;
            // $form_history->description = 'REQUESTING FOR DATACHANGE APPROVAL';
            if ($filenames) {
                $form_history->reviewer_attachment = implode(',', $filenames);
            }
            $form_history->save();

            return ['data updated'];
        }
    }

    public function destroy($id)
    {
        $employee_datachange = ModelsEmployeeDataChange::findOrFail($id);

        if ($employee_datachange->delete()) {
            Helpers::LogActivity($employee_datachange->id, 'DATA CHANGE', 'DELETED DATA CHANGE REQUEST');

            $form_history = DB::table('form_history')
                ->where('form_id', $id)
                ->where('form_type', $employee_datachange->form_type)
                ->delete();

            $transaction_statuses = DB::table('transaction_statuses')
                ->where('form_id', '=', $id)
                ->where('form_type', '=', $employee_datachange->form_type)
                ->delete();

            $transaction_approvers = DB::table('transaction_approvers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $employee_datachange->form_type)
                ->delete();

            $transaction_receivers = DB::table('transaction_receivers')
                ->where('transaction_id', '=', $id)
                ->where('form_type', '=', $employee_datachange->form_type)
                ->delete();
                
            return response()->json($form_history);
        }
    }

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
            'BACK OUT',
            'RETURNED TO AGENCY'
        ];

        $query = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
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
                'positions.subunit_id',
                'positions.position_name',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.order',
                'jobbands.subunit_bound',
                'jobbands.jobband_name',
                'employee_positions.position_id',
                'locations.location_name',
                'companies.company_name',
                'divisions.division_name',
                'division_categories.category_name'
            ])
            // ->where('jobbands.order', '>', $order_no)
            ->where('positions.department_id', '=', $department_id)
            // ->where(function ($query) {
            //     $query->where('employee_states.employee_state', '=', 'extended')
            //         ->orWhere('employee_states.employee_state', '=', 'under_evaluation')
            //         ->orWhere('employee_states.employee_state', '=', 'evaluated_regular')
            //         ->orWhere('employee_states.employee_state', '=', 'returned')
            //         ->orWhereNull('employee_states.employee_state');
            // })
            ->where(function ($q) use ($inactive) {
                $q->whereNotIn('employee_states.employee_state_label', $inactive)
                    ->orWhereNull('employee_states.employee_state');
            })
            ->whereNotIn('employees.id', DB::table('employee_datachanges')->where('employee_datachanges.current_status', '!=', 'approved')->pluck('employee_id'))
            ->where('employees.current_status', '!=', 'for-approval');

        // if ($order && $order->subunit_bound) {
        //     $subordinates = $query->where('positions.subunit_id', '=', $subunit_id)->get();
        // } else {
        //     $subordinates = $query->get();
        // }

        $subordinates = $query->get();

        return EmployeeDataChange::collection($subordinates);
    }

    public function cancelDataChange(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $form_type = 'employee-datachange';
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

        if ($form_type == 'employee-datachange') {
            $review = 'cancelled';
            $review_mark = 'CANCELLED';
            $remarks = '';

            $employee_datachange = ModelsEmployeeDataChange::findOrFail($form_id);

            $employee_datachange->level = 1;
            $employee_datachange->current_status = $review;
            $employee_datachange->current_status_mark = $review_mark;
            $employee_datachange->is_fulfilled = 'CANCELLED';

            if ($employee_datachange->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR DATACHANGE FORM - STATUS: '.$review_mark;
                Helpers::LogActivity($employee_datachange->id, 'FORM REQUEST - DATACHANGE FORM', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $employee_datachange->id;
                $form_history->code = $employee_datachange->code;
                $form_history->form_type = $employee_datachange->form_type;
                $form_history->form_data = $employee_datachange->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $employee_datachange->level;
                $form_history->requestor_id = $employee_datachange->requestor_id;
                $form_history->employee_id = $employee_datachange->employee_id;
                $form_history->is_fulfilled = $employee_datachange->is_fulfilled;
                $form_history->description = 'REVIEW FOR DATACHANGE REQUEST - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $employee_datachange->id;
                $transaction_statuses->code = $employee_datachange->code;
                $transaction_statuses->form_type = $employee_datachange->form_type;
                $transaction_statuses->form_data = $employee_datachange->toJson();
                $transaction_statuses->status = $review;
                $transaction_statuses->status_mark = $review_mark;
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $employee_datachange->level;
                $transaction_statuses->requestor_id = $employee_datachange->requestor_id;
                $transaction_statuses->employee_id = $employee_datachange->employee_id;
                $transaction_statuses->is_fulfilled = $employee_datachange->is_fulfilled;
                $transaction_statuses->description = 'REVIEW FOR DATACHANGE REQUEST - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($employee_datachange);
        }
    }

    public function resubmitDataChange(Request $request, $id) 
    {
        date_default_timezone_set('Asia/Manila');

        $employee_datachange = ModelsEmployeeDataChange::findOrFail($id);

        $employee = DB::table('employees')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select(['positions.subunit_id', 'positions.position_name'])
            ->where('employees.id', '=', $employee_datachange->employee_id)
            ->get()
            ->first();

        // check if requestor is an approver
        $isLoggedIn = Auth::check();
        $isApprover = false;

        if ($isLoggedIn) { 
            $current_user = Auth::user();

            $approver = DB::table('forms')
                ->select(['id'])
                ->where('form_type', '=', 'employee-datachange')
                ->where('employee_id', '=', $current_user->employee_id)
                ->where('subunit_id', '=', $employee->subunit_id)
                ->get();

            if ($approver->count()) {
                $isApprover = true;
            }
        }

        if ($employee_datachange->current_status == 'rejected' || $employee_datachange->current_status == 'cancelled') {
            $employee_datachange->level = ($isApprover) ? 2 : 1;
            $employee_datachange->current_status = 'for-approval';
            $employee_datachange->current_status_mark = 'FOR APPROVAL';
            $employee_datachange->is_fulfilled = 'PENDING';

            if ($employee_datachange->save()) {
                Helpers::LogActivity($employee_datachange->id, 'FORM REQUISITION - DATACHANGE FORM', 'RE-SUBMITTED DATACHANGE FORM REQUEST');

                $form_history = new FormHistory();
                $form_history->form_id = $employee_datachange->id;
                $form_history->code = $employee_datachange->code;
                $form_history->form_type = $employee_datachange->form_type;
                $form_history->form_data = $employee_datachange->toJson();
                $form_history->status = ($isApprover) ? 'approved' : $employee_datachange->current_status;
                $form_history->status_mark = ($isApprover) ? 'APPROVED' : $employee_datachange->current_status_mark;
                $form_history->reviewer_id = $employee_datachange->requestor_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'assess';
                $form_history->remarks = $employee_datachange->remarks;
                $form_history->level = ($isApprover) ? 2 : 1;
                $form_history->requestor_id = $employee_datachange->requestor_id;
                $form_history->employee_id = $employee_datachange->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'RESUBMISSION OF DATACHANGE REVISION';
                $form_history->save();

                $delete_transaction_statuses = DB::table('transaction_statuses')
                    ->where('form_id', '=', $employee_datachange->id)
                    ->where('form_type', '=', $employee_datachange->form_type)
                    ->delete();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $employee_datachange->id;
                $transaction_statuses->code = $employee_datachange->code;
                $transaction_statuses->form_type = $employee_datachange->form_type;
                $transaction_statuses->form_data = $employee_datachange->toJson();
                $transaction_statuses->status = ($isApprover) ? 'approved' : $employee_datachange->current_status;
                $transaction_statuses->status_mark = ($isApprover) ? 'APPROVED' : $employee_datachange->current_status_mark;
                $transaction_statuses->reviewer_id = $employee_datachange->requestor_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'assess';
                $transaction_statuses->remarks = $employee_datachange->remarks;
                $transaction_statuses->level = ($isApprover) ? 2 : 1;
                $transaction_statuses->requestor_id = $employee_datachange->requestor_id;
                $transaction_statuses->employee_id = $employee_datachange->id;
                $transaction_statuses->is_fulfilled = 'PENDING';
                $transaction_statuses->description = 'RESUBMISSION OF DATACHANGE REVISION';
                $transaction_statuses->save();
            }
        }
    }

    public function getPositions(Request $request) {
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

            if (($order && $order->subunit_bound) || ($request->change_reason == '201 DATA CHANGED')) {
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

        return response()->json($positions);
    }
}
