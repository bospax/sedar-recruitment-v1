<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\RegistrationDetails as RegistrationDetailsResources;
use App\Http\Resources\RegistrationDetailsWithManpower;
use App\Models\Address;
use App\Models\EmployeeAccount;
use App\Models\EmployeeAttainment;
use App\Models\EmployeeContact;
use App\Models\EmployeeFile;
use App\Models\EmployeePosition;
use App\Models\EmployeeState;
use App\Models\EmployeeStatus;
use App\Models\FormHistory;
use App\Models\Role;
use App\Models\TransactionApprovers;
use App\Models\TransactionReceivers;
use App\Models\TransactionStatuses;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegistrationController extends Controller
{
    public function fetchEmployees() {
        $isLoggedIn = Auth::check();
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $employee_id = '';
        $role_id = '';
        $permissions = [];

        if ($isLoggedIn) { 
            $current_user = Auth::user();
            $role_id = $current_user->role_id;
            $role = Role::findOrFail($role_id);
            $permissions = explode(',', $role->permissions);
            $employee_id = $current_user->employee_id;
        }
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'emp.first_name' : 'emp.created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';

                $query = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->select([
                        'emp.id',
                        'emp.code',
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
                    ->where('emp.current_status', '!=', 'approved')
                    ->where(function($query) use ($value){
                        $query->where('emp.last_name', 'LIKE', $value)
                            ->orWhere('emp.middle_name', 'LIKE', $value)
                            ->orWhere('emp.first_name', 'LIKE', $value)
                            ->orWhere('emp.code', 'LIKE', $value);
                    });
                
                // $employees = $query
                //     ->orderBy($field, $sort)
                //     ->paginate(100);

                // if (!in_array('register', $permissions)) {
                //     $employees = $query
                //         ->where('emp.requestor_id', '=', $employee_id)
                //         ->orderBy($field, $sort)
                //         ->paginate(100);
                // }

                $employees = $query
                    ->where('emp.requestor_id', '=', $employee_id)
                    ->orderBy($field, $sort)
                    ->paginate(100);

            } else {
                $query = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->select([
                        'emp.id',
                        'emp.code',
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
                    ->where('emp.current_status', '!=', 'approved');

                // $employees = $query
                //     ->orderBy($field, $sort)
                //     ->paginate(100);

                // if (!in_array('register', $permissions)) {
                //     $employees = $query
                //         ->where('emp.requestor_id', '=', $employee_id)
                //         ->orderBy($field, $sort)
                //         ->paginate(100);
                // }

                $employees = $query
                    ->where('emp.requestor_id', '=', $employee_id)
                    ->orderBy($field, $sort)
                    ->paginate(100);
            }
        }

        return RegistrationDetailsWithManpower::collection($employees);
    }

    public function validateGeneralInfoSection(Request $request) {
        $generate_idnumber = $request->input('generate_idnumber');
        $create_useraccount = $request->input('create_useraccount');

        $rules = [
            // 'manpower_id' => ['required'],
            'prefix_id' => ['required'],
            'first_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'middle_name' => ['nullable','regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'last_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'suffix' => ['nullable','regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'birthdate' => ['required'],
            'religion' => ['required'],
            'civil_status' => ['required'],
            'gender' => ['required']
        ];

        $special_rule = [
            'id_number' => ['required', 'numeric', 'unique:employees,id_number,NULL,id,prefix_id,'. $request->prefix_id]
        ];

        $account_rule = [
            'name' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u'],
            'username' => ['required', 'unique:users'],
            'password' => ['required', 'min:8']
        ];

        if ($generate_idnumber == 'false') {
            $rules = array_merge($rules, $special_rule);
        }

        if ($create_useraccount == 'true') {
            $rules = array_merge($rules, $account_rule);
        }
        
        $this->validate($request, $rules);

        return response()->json('ok');
    }

    public function validateFullName(Request $request) {
        $duplicate = false;

        $data = DB::table('employees')->select(['id'])
            ->where('first_name', 'LIKE', $request->first_name)
            ->where('last_name', 'LIKE', $request->last_name)
            ->get()
            ->count();

        if ($data) {
            $duplicate = true;
        }

        return response()->json(['duplicate' => $duplicate]);
    }

    public function validatePositionSection(Request $request) {
        $this->validate($request, [
            'position_id' => ['required'],
            // 'jobrate_id' => ['required'],
            'division_id' => ['required'],
            'division_cat_id' => ['required'],
            'company_id' => ['required'],
            'location_id' => ['required'],
            'salary_structure' => ['required'],
            'salary' => ['required'],
            'job_rate' => ['required'],
        ]);

        return response()->json('position ok');
    }

    public function validateEmploymentTypeSection(Request $request) {
        $employment_type = $request->input('employment_type');

        $this->validate($request, [
            'employment_type' => 'required',
        ]);
        
        if ($employment_type == 'regular') {
            $this->validate($request, [
                'regularization_date' => 'required'
            ]);
        } else {
            $this->validate($request, [
                'employment_date_start' => 'required',
                'employment_date_end' => 'required'
            ]);
        }

        return response()->json('employment type ok');
    }

    public function validateEmployeeStateSection(Request $request) {
        $employee_state = $request->input('employee_state');

        if (($employee_state == 'extended' || $employee_state == 'suspended' || $employee_state == 'maternity') && $employee_state != 'active') {
            $this->validate($request, [
                'employee_state' => 'required',
                'state_date_start' => 'required',
                'state_date_end' => 'required',
            ]);
        }

        if (!in_array($employee_state, ['extended', 'suspended', 'active', 'maternity'])) {
            $this->validate($request, [
                'employee_state' => 'required',
                'state_date' => 'required',
            ]);
        }

        $this->validate($request, [
            'employee_state' => 'required'
        ]);

        return response()->json('employee state ok');
    }

    public function validateEmployeeAddressSection(Request $request) {
        $this->validate($request, [
            'region' => ['required'],
            'province' => ['required'],
            'municipal' => ['required'],
            'barangay' => ['required'],
            // 'street' => ['required'],
            'zip_code' => ['required', 'numeric']
        ]);

        return response()->json('employee address ok');
    }

    public function validateAttainmentInfoSection(Request $request) {
        $academic_year_from = $request->input('academic_year_from');
        $academic_year_to = $request->input('academic_year_to');

        $this->validate($request, [
            // 'institution' => ['required'],
            'attainment' => ['required'],
            'course' => ['required'],
            'degree' => ['required'],
            'gpa' => ['nullable','regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/'],
        ]);

        if ($academic_year_from != '' && $academic_year_to == '') {
            throw ValidationException::withMessages([
                'academic_year_to' => ['The \'to\' date is required.']
            ]);
        }

        if ($academic_year_from == '' && $academic_year_to != '') {
            throw ValidationException::withMessages([
                'academic_year_from' => ['The \'from\' date is required.']
            ]);
        }

        return response()->json('employee attainment ok');
    }

    public function validateEmployeeAccountSection(Request $request) {
        $this->validate($request, [
            'sss_no' => ['regex:/^\d{2}-\d{7}-\d{1}$/', 'nullable'],
            'pagibig_no' => ['regex:/^\d{4}-\d{4}-\d{4}$/', 'nullable'],
            'philhealth_no' => ['regex:/^\d{2}-\d{9}-\d{1}$/', 'nullable'],
            'tin_no' => ['regex:/^\d{3}-\d{3}-\d{3}-\d{5}$/', 'nullable']
        ]);

        return response()->json('employee account ok');
    }

    public function confirmRegistration(Request $request) { 
        date_default_timezone_set('Asia/Manila');

        // if (empty($request->input('files'))) {
        //     throw ValidationException::withMessages([
        //         'file_type' => ['Files are required.']
        //     ]);
        // }

        // register general info
        $employee = new Employee();
        $employee->code = Helpers::generateCodeNewVersion('employees', 'ERF');
        $employee->prefix_id = $request->input('prefix_id');
        
        if ($request->input('generate_idnumber') == 'true') {
            $generated_number = DB::table("employees")
                ->select(DB::raw("max(substring_index(id_number, '-', -1) + 0) as `id_number`"))
                ->where('prefix_id', $request->input('prefix_id'))
                ->get()
                ->first()
                ->id_number;

            $generated_number = (int) $generated_number + 1;
            $employee->id_number = $generated_number;
        } else {
            $employee->id_number = $request->input('id_number');
        }

        $employee->first_name = $request->input('first_name');
        $employee->middle_name = ($request->input('middle_name')) ? $request->input('middle_name') : '';
        $employee->last_name = $request->input('last_name');
        $employee->suffix = ($request->input('suffix')) ? $request->input('suffix') : '';
        $employee->birthdate = strtoupper($request->input('birthdate'));
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

        $employee->manpower_id = (int) $request->input('manpower_id');
        $employee->manpower_details = $request->input('manpower_details');

        if ($request->hasFile('employee_image')) {
            $filenameWithExt = $request->file('employee_image')->getClientOriginalName();
            $fileNameToStore = time().'_'.$filenameWithExt;
            $path = $request->file('employee_image')->storeAs('public/employee_images', $fileNameToStore);
        } else {
            $fileNameToStore = 'noimage.png';
        }

        $employee->image = $fileNameToStore;

        // check if requestor is an approver
        $isLoggedIn = Auth::check();

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
                ->where('form_type', '=', 'employee-registration')
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
                ->where('form_type', '=', 'employee-registration')
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
                ->where('form_type', '=', 'employee-registration')
                ->where('subunit_id', '=', $data->subunit_id)
                ->get();

            if ($approver->count()) {
                $employee->level = 2;
                $employee->current_status = 'approved';
                $employee->current_status_mark = 'APPROVED';
            }
        }

        if ($employee->save()) {
            Helpers::LogActivity($employee->id, 'EMPLOYEE REGISTRATION', 'REGISTERED NEW EMPLOYEE DATA');

            $form_history = new FormHistory();
            $form_history->form_id = $employee->id;
            $form_history->code = $employee->code;
            $form_history->form_type = $employee->form_type;
            $form_history->form_data = $employee->toJson();
            $form_history->status = $employee->current_status;
            $form_history->status_mark = $employee->current_status_mark;
            $form_history->reviewer_id = $employee->requestor_id;
            $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
            $form_history->reviewer_action = 'assess';
            $form_history->remarks = $employee->remarks;
            $form_history->level = $employee->level;
            $form_history->requestor_id = $employee->requestor_id;
            $form_history->employee_id = $employee->id;
            $form_history->is_fulfilled = 'PENDING';
            $form_history->description = 'REQUESTING FOR REGISTRATION APPROVAL';
            $form_history->save();

            $transaction_statuses = new TransactionStatuses();
            $transaction_statuses->form_id = $employee->id;
            $transaction_statuses->code = $employee->code;
            $transaction_statuses->form_type = $employee->form_type;
            $transaction_statuses->form_data = $employee->toJson();
            $transaction_statuses->status = $employee->current_status;
            $transaction_statuses->status_mark = $employee->current_status_mark;
            $transaction_statuses->reviewer_id = $employee->requestor_id;
            $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
            $transaction_statuses->reviewer_action = 'assess';
            $transaction_statuses->remarks = $employee->remarks;
            $transaction_statuses->level = $employee->level;
            $transaction_statuses->requestor_id = $employee->requestor_id;
            $transaction_statuses->employee_id = $employee->id;
            $transaction_statuses->is_fulfilled = 'PENDING';
            $transaction_statuses->description = 'REQUESTING FOR REGISTRATION APPROVAL';
            $transaction_statuses->save();

            $data_approvers = [];
            $approvers = $approvers->toArray();

            foreach ($approvers as $form) {
                $data_approvers[] = [
                    'transaction_id' => $employee->id,
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
                    'transaction_id' => $employee->id,
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

            // user account & role 
            if ($request->input('create_useraccount') == 'true') {
                $roles = DB::table('roles')->select(['id'])->where('role_name', '=', 'USER')->get()->first();

                if ($roles) {
                    $user = new User();
                    $user->employee_id = $employee->id;
                    $user->name = $request->input('name');
                    $user->username = $request->input('username');
                    $user->role_id = $roles->id;
                    $user->status = 'inactive';
                    $user->status_description = 'DEACTIVATED';
                    $user->password = Hash::make($request->input('password'));
                    $user->save();
                } else {
                    $role = new Role();
                    $role->key = '';
                    $role->role_name = 'USER';
                    $role->permissions = 'edit,delete,dashboard,form_requests,approve,reject,evaluations,probational,forms,monthly,annual';
                    if ($role->save()) {
                        // user account 
                        $user = new User();
                        $user->employee_id = $employee->id;
                        $user->name = $request->input('name');
                        $user->username = $request->input('username');
                        $user->role_id = $role->id;
                        $user->status = 'inactive';
                        $user->status_description = 'DEACTIVATED';
                        $user->password = Hash::make($request->input('password'));
                        $user->save();
                    }
                }
            }

            // position info
            $employee_position = new EmployeePosition();
            $employee_position->employee_id = $employee->id;
            $employee_position->position_id = $request->input('position_id');
            $employee_position->jobrate_id = $request->input('jobrate_id');
            $employee_position->division_id = $request->input('division_id');
            $employee_position->division_cat_id = $request->input('division_cat_id');
            $employee_position->company_id = $request->input('company_id');
            $employee_position->location_id = $request->input('location_id');

            $structures = explode('|', $request->input('salary_structure'));
            $job_level = trim($structures[0]);
            $salary_structure = trim($structures[1]);
            $jobrate_name = trim($structures[2]);

            $employee_position->jobrate_name = $jobrate_name;
            $employee_position->salary_structure = $salary_structure;
            $employee_position->job_level = $job_level;
            $employee_position->additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
            $employee_position->allowance = (float) str_replace(',', '', $request->input('allowance'));
            $employee_position->job_rate = (float) str_replace(',', '', $request->input('job_rate'));
            $employee_position->salary = (float) str_replace(',', '', $request->input('salary'));

            $employee_position->additional_tool = $request->input('additional_tool');
            $employee_position->schedule = $request->input('schedule');
            $employee_position->emp_shift = $request->input('emp_shift');
            $employee_position->remarks = $request->input('position_remarks');
            $employee_position->save();

            // employment type
            $employee_status = new EmployeeStatus();
            $employee_status->employee_id = $employee->id;
            $employment_type = $request->input('employment_type');
            
            if ($employment_type == 'regular') {
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
                $employee_status->employment_type_label = $request->input('employment_type_label');
                $employee_status->employment_type = $request->input('employment_type');
                $employee_status->employment_date_start = $request->input('employment_date_start');
                $employee_status->employment_date_end = $request->input('employment_date_end');
                $employee_status->regularization_date = '--';
                $employee_status->hired_date = $request->input('employment_date_start');
                $employee_status->hired_date_fix = Carbon::parse($request->input('employment_date_start'));
                $employee_status->reminder = Carbon::parse($request->input('employment_date_start'));
            }
            $employee_status->save();

            // log in job history
            $reference_id = $employee->code;
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

            // status info
            // $employee_state = new EmployeeState();
            // $employee_state->employee_id = $employee->id;
            // $employee_state->employee_state_label = $request->input('employee_state_label');
            // $employee_state->employee_state = $request->input('employee_state');
            // $employee_state->state_date_start = $request->input('state_date_start');
            // $employee_state->state_date_end = $request->input('state_date_end');
            // $employee_state->state_date = $request->input('state_date');
            // $employee_state->save();

            // address info 
            $address = new Address();
            $address->employee_id = $employee->id;
            $address->region = $request->input('region');
            $address->province = $request->input('province');
            $address->municipal = $request->input('municipal');
            $address->barangay = $request->input('barangay');
            $address->street = $request->input('street');
            $address->zip_code = $request->input('zip_code');
            $address->detailed_address = $request->input('detailed_address');
            $address->foreign_address = $request->input('foreign_address');
            $address->address_remarks = $request->input('address_remarks');
            $address->save();

            // attainment info 
            $employee_attainment = new EmployeeAttainment();
            $employee_attainment->employee_id = $employee->id;
            $employee_attainment->attainment = $request->input('attainment');
            $employee_attainment->course = $request->input('course');
            $employee_attainment->degree = $request->input('degree');
            $employee_attainment->honorary = $request->input('honorary');
            $employee_attainment->institution = $request->input('institution');
            $employee_attainment->years = $request->input('years');
            $employee_attainment->academic_year_from = $request->input('academic_year_from');
            $employee_attainment->academic_year_to = $request->input('academic_year_to');
            $employee_attainment->gpa = ($request->input('gpa')) ? $request->input('gpa') : '';
            $employee_attainment->attainment_remarks = ($request->input('attainment_remarks')) ? $request->input('attainment_remarks') : '';

            if ($request->hasFile('attachments')) {
                $files = $request->attachments;
                $filenames = [];
    
                foreach ($files as $file) {
                    $filenameWithExt = $file->getClientOriginalName();
                    $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                    $path = $file->storeAs('public/attainment_attachments', $fileNameToStore);
                    $filenames[] = str_replace(',', '', $fileNameToStore);
                }
    
                if ($filenames) {
                    $employee_attainment->attachment = implode(',', $filenames);
                }
            }

            $employee_attainment->save();

            // account 
            $employee_account = new EmployeeAccount();
            $employee_account->employee_id = $employee->id;
            $employee_account->sss_no = $request->input('sss_no');
            $employee_account->pagibig_no = $request->input('pagibig_no');
            $employee_account->philhealth_no = $request->input('philhealth_no');
            $employee_account->tin_no = $request->input('tin_no');
            $employee_account->bank_name = $request->input('bank_name');
            $employee_account->bank_account_no = $request->input('bank_account_no');
            $employee_account->save();

            // files 
            if (!empty($request->input('files'))) {
                $employee_id = $employee->id;
                $data = [];
                foreach ($request->input('files') as $key => $file) {
                    $file = json_decode($file);
                    $uploaded = $request->uploaded[$key];

                    if ($uploaded) {
                        $filenameWithExt = $uploaded->getClientOriginalName();
                        $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                        $path = $uploaded->storeAs('public/201_files', $fileNameToStore);
                        $filename = str_replace(',', '', $fileNameToStore);

                        $data[] = [
                            'employee_id' => $employee_id,
                            'file_type' => $file->file_type,
                            'cabinet_number' => $file->cabinet_number,
                            'description' => $file->description,
                            'file' => $filename,
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString()
                        ];
                    } else {
                        $data[] = [
                            'employee_id' => $employee_id,
                            'file_type' => $file->file_type,
                            'cabinet_number' => $file->cabinet_number,
                            'description' => $file->description,
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString()
                        ];
                    }
                }
                EmployeeFile::insert($data);
            }

            // contacts
            if (!empty($request->input('contacts'))) {
                $employee_id = $employee->id;
                $data = [];
                
                foreach ($request->input('contacts') as $key => $contact) {
                    $contact = json_decode($contact);

                    $data[] = [
                        'employee_id' => $employee_id,
                        'contact_type' => $contact->contact_type,
                        'contact_details' => $contact->contact_details,
                        'description' => $contact->description,
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString()
                    ];
                }
                EmployeeContact::insert($data);
            }

            return new RegistrationDetailsResources($employee);
        }
    }

    public function resubmitRegistration(Request $request, $id) {
        $employee = Employee::findOrFail($id);

        if ($employee->current_status == 'rejected') {
            $employee->current_status = 'for-approval';
            $employee->current_status_mark = 'FOR APPROVAL';

            if ($employee->save()) {
                $form_history = new FormHistory();
                $form_history->form_id = $employee->id;
                $form_history->code = $employee->code;
                $form_history->form_type = $employee->form_type;
                $form_history->form_data = $employee->toJson();
                $form_history->status = $employee->current_status;
                $form_history->status_mark = $employee->current_status_mark;
                $form_history->reviewer_id = $employee->requestor_id;
                $form_history->review_date = Carbon::now();
                $form_history->reviewer_action = 'assess';
                $form_history->remarks = $employee->remarks;
                $form_history->level = $employee->level;
                $form_history->requestor_id = $employee->requestor_id;
                $form_history->employee_id = $employee->id;
                $form_history->is_fulfilled = 'PENDING';
                $form_history->description = 'REQUESTING FOR REGISTRATION APPROVAL';
                $form_history->save();

                $delete_transaction_statuses = DB::table('transaction_statuses')
                    ->where('form_id', '=', $employee->id)
                    ->where('form_type', '=', $employee->form_type)
                    ->delete();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $employee->id;
                $transaction_statuses->code = $employee->code;
                $transaction_statuses->form_type = $employee->form_type;
                $transaction_statuses->form_data = $employee->toJson();
                $transaction_statuses->status = $employee->current_status;
                $transaction_statuses->status_mark = $employee->current_status_mark;
                $transaction_statuses->reviewer_id = $employee->requestor_id;
                $transaction_statuses->review_date = Carbon::now();
                $transaction_statuses->reviewer_action = 'assess';
                $transaction_statuses->remarks = $employee->remarks;
                $transaction_statuses->level = $employee->level;
                $transaction_statuses->requestor_id = $employee->requestor_id;
                $transaction_statuses->employee_id = $employee->id;
                $transaction_statuses->is_fulfilled = 'PENDING';
                $transaction_statuses->description = 'REQUESTING FOR REGISTRATION APPROVAL';
                $transaction_statuses->save();
            }
        }
    }

    public function registrationDetails(Request $request) {
        $id = $request->id;
        $section = $request->section;

        switch ($section) {
            case 'general':
                $employee = DB::table('employees')
                    ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
                    ->select([
                        'employees.id',
                        'employees.code',
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
                    ->where('employees.id', '=', $id)
                    ->get();
                
                return RegistrationDetailsWithManpower::collection($employee);
                break;

            case 'position':
                $employee_position = DB::table('employee_positions')
                    ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
                    ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
                    ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
                    ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
                    ->select([
                        'employee_positions.id',
                        'employee_positions.employee_id',
                        'employee_positions.position_id',
                        'employee_positions.jobrate_id',
                        'employee_positions.division_id',
                        'employee_positions.division_cat_id',
                        'employee_positions.company_id',
                        'employee_positions.location_id',
                        'employee_positions.additional_rate',
                        'employee_positions.jobrate_name',
                        'employee_positions.salary_structure',
                        'employee_positions.job_level',
                        'employee_positions.allowance',
                        'employee_positions.job_rate',
                        'employee_positions.salary',
                        'employee_positions.additional_tool',
                        'employee_positions.remarks',
                        'employee_positions.schedule as emp_schedule',
                        'employee_positions.emp_shift',
                        'employees.prefix_id',
                        'employees.id_number',
                        'employees.first_name',
                        'employees.middle_name',
                        'employees.last_name',
                        'employees.suffix',
                        'employees.gender',
                        'employees.image',
                        'positions.code',
                        'positions.position_name',
                        'positions.payrate',
                        'positions.employment',
                        'positions.no_of_months',
                        'positions.schedule',
                        'positions.shift',
                        'positions.team',
                        'positions.attachments',
                        'positions.tools',
                        // 'jobrates.salary_structure',
                        // 'jobrates.job_level',
                        // 'jobrates.jobrate_name',
                        'departments.department_name',
                        'subunits.subunit_name',
                        'divisions.division_name',
                        'division_categories.category_name',
                        'companies.company_name',
                        'locations.location_name'
                    ])
                    ->where('employee_positions.employee_id', '=', $id)
                    ->get()
                    ->map(function ($employee_position) {
                        $employee_position->full_salary_structure = $employee_position->job_level.' | '.$employee_position->salary_structure.' | '.$employee_position->jobrate_name;
                        return $employee_position;
                    })
                    ->first();

                return response()->json($employee_position);
                break;

            case 'employment':
                $employment = DB::table('employee_statuses')
                    ->select([
                        'employee_statuses.id',
                        'employee_statuses.employee_id',
                        'employee_statuses.employment_type_label',
                        'employee_statuses.employment_type',
                        'employee_statuses.employment_date_start',
                        'employee_statuses.employment_date_end',
                        'employee_statuses.regularization_date',
                        'employee_statuses.hired_date',
                    ])
                    ->where('employee_id', '=', $id)
                    ->get();

                return response()->json($employment);
                break;

            case 'status':
                $states = DB::table('employee_states')
                    ->leftJoin('employees', 'employee_states.employee_id', '=', 'employees.id')
                    ->select([
                        'employee_states.id',
                        'employee_states.employee_id',
                        'employee_states.employee_state_label',
                        'employee_states.employee_state',
                        'employee_states.state_date_start',
                        'employee_states.state_date_end',
                        'employee_states.state_date'
                    ])
                    ->where('employee_id', '=', $id)
                    ->get();

                return response()->json($states);
                break;

            case 'address':
                $addresses = DB::table('addresses')
                    ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
                    ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
                    ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
                    ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
                    ->select([
                        'addresses.id',
                        'addresses.employee_id',
                        'addresses.region',
                        'addresses.province',
                        'addresses.municipal',
                        'addresses.barangay',
                        'addresses.street',
                        'addresses.zip_code',
                        'addresses.detailed_address',
                        'addresses.foreign_address',
                        'addresses.address_remarks',
                        'regions.reg_desc',
                        'regions.reg_code',
                        'provinces.prov_desc',
                        'provinces.prov_code',
                        'municipals.citymun_desc',
                        'municipals.citymun_code',
                        'barangays.brgy_desc',
                        'barangays.brgy_code'
                    ])
                    ->where('addresses.employee_id', '=', $id)
                    ->get();

                return response()->json($addresses);
                break;

            case 'attainment':
                $employee_attainments = DB::table('employee_attainments')
                    ->select([
                        'employee_attainments.id',
                        'employee_attainments.employee_id',
                        'employee_attainments.attainment',
                        'employee_attainments.course',
                        'employee_attainments.degree',
                        'employee_attainments.honorary',
                        'employee_attainments.institution',
                        'employee_attainments.attachment',
                        'employee_attainments.years',
                        'employee_attainments.gpa',
                        'employee_attainments.academic_year_from',
                        'employee_attainments.academic_year_to',
                        'employee_attainments.attainment_remarks'
                    ])
                    ->where('employee_attainments.employee_id', '=', $id)
                    ->get();

                return response()->json($employee_attainments);
                break;

            case 'contact':
                $contacts = DB::table('employee_contacts')
                    ->select([
                        'employee_contacts.id',
                        'employee_contacts.employee_id',
                        'employee_contacts.contact_type',
                        'employee_contacts.contact_details',
                        'employee_contacts.description'
                    ])
                    ->where('employee_contacts.employee_id', '=', $id)
                    ->get();

                return response()->json($contacts);
                break;

            case 'account':
                $employee_accounts = DB::table('employee_accounts')
                    ->select([
                        'employee_accounts.id',
                        'employee_accounts.employee_id',
                        'employee_accounts.sss_no',
                        'employee_accounts.pagibig_no',
                        'employee_accounts.philhealth_no',
                        'employee_accounts.tin_no',
                        'employee_accounts.bank_name',
                        'employee_accounts.bank_account_no'
                    ])
                    ->where('employee_accounts.employee_id', '=', $id)
                    ->get();

                return response()->json($employee_accounts);
                break;

            case 'files':
                $files = DB::table('employee_files')
                    ->select([
                        'employee_files.id',
                        'employee_files.employee_id',
                        'employee_files.file_type',
                        'employee_files.cabinet_number',
                        'employee_files.description',
                        'employee_files.file'
                    ])
                    ->where('employee_id', '=', $id)
                    ->get();

                return response()->json($files);
                break;

            default:
              // code
        }
    }

    public function updateGeneralInfo(Request $request, $id) {
        $generate_idnumber = $request->input('generate_idnumber');

        $rules = [
            'prefix_id' => ['required'],
            'first_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'middle_name' => ['nullable', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'last_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'suffix' => ['nullable','regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
            'birthdate' => ['required'],
            'religion' => ['required'],
            'civil_status' => ['required'],
            'gender' => ['required'],
            // 'manpower_id' => ['required']
        ];

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

        $special_rule = [
            // 'id_number' => ['required', 'numeric', 'unique:employees,id_number,'.$id]
        ];

        if ($generate_idnumber == 'false') {
            $rules = array_merge($rules, $special_rule);
        }
        
        $this->validate($request, $rules);

        $employee = Employee::findOrFail($id);
        $employee->prefix_id = $request->input('prefix_id');
        
        if ($request->input('generate_idnumber') != 'true') {
            $employee->id_number = $request->input('id_number');
        }

        $employee->first_name = $request->input('first_name');
        $employee->middle_name = ($request->input('middle_name')) ? $request->input('middle_name') : '';
        $employee->last_name = $request->input('last_name');
        $employee->suffix = ($request->input('suffix')) ? $request->input('suffix') : '';
        $employee->birthdate = strtoupper($request->input('birthdate'));
        $employee->religion = $request->input('religion');
        $employee->civil_status = $request->input('civil_status');
        $employee->gender = $request->input('gender');
        $employee->remarks = ($request->input('remarks')) ? $request->input('remarks') : '';
        $employee->referrer_id = ($request->input('referrer_id')) ? $request->input('referrer_id') : null;
        $employee->form_type = 'employee-registration';
        // $employee->level = 1;
        // $employee->current_status = 'for-approval';
        // $employee->current_status_mark = 'FOR APPROVAL';
        $employee->requestor_id = auth()->user()->employee_id;

        $employee->manpower_id = (int) $request->input('manpower_id');
        $employee->manpower_details = $request->input('manpower_details');

        if ($request->hasFile('employee_image')) {
            $filenameWithExt = $request->file('employee_image')->getClientOriginalName();
            $fileNameToStore = time().'_'.$filenameWithExt;
            $path = $request->file('employee_image')->storeAs('public/employee_images', $fileNameToStore);
            $employee->image = $fileNameToStore;
        }

        if ($employee->save()) {
            Helpers::LogActivity($employee->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - GENERAL SECTION');

            $form_history = FormHistory::where('form_id', $employee->id)
            ->where('form_type', 'employee-registration')
            ->first();

            $form_history->form_id = $employee->id;
            $form_history->code = $employee->code;
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

            $employee = DB::table('employees AS emp')
                ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                ->select([
                    'emp.id',
                    'emp.code',
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

            return RegistrationDetailsWithManpower::collection($employee);
        }
    }

    public function updatePositionSection(Request $request, $id) {
        $this->validate($request, [
            'position_id' => ['required'],
            // 'jobrate_id' => ['required'],
            'division_id' => ['required'],
            'division_cat_id' => ['required'],
            'company_id' => ['required'],
            'location_id' => ['required'],
            'salary_structure' => ['required'],
            'salary' => ['required'],
            'job_rate' => ['required'],
        ]);

        $employee_position = EmployeePosition::findOrFail($id);
        $employee_position->employee_id = $request->input('employee_id');
        $employee_position->position_id = $request->input('position_id');
        $employee_position->jobrate_id = $request->input('jobrate_id');
        $employee_position->division_id = $request->input('division_id');
        $employee_position->division_cat_id = $request->input('division_cat_id');
        $employee_position->company_id = $request->input('company_id');
        $employee_position->location_id = $request->input('location_id');

        $structures = explode('|', $request->input('salary_structure'));
        $job_level = trim($structures[0]);
        $salary_structure = trim($structures[1]);
        $jobrate_name = trim($structures[2]);

        $employee_position->jobrate_name = $jobrate_name;
        $employee_position->salary_structure = $salary_structure;
        $employee_position->job_level = $job_level;
        $employee_position->additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
        $employee_position->allowance = (float) str_replace(',', '', $request->input('allowance'));
        $employee_position->job_rate = (float) str_replace(',', '', $request->input('job_rate'));
        $employee_position->salary = (float) str_replace(',', '', $request->input('salary'));

        $employee_position->additional_tool = implode(',', $request->input('additional_tool'));
        $employee_position->schedule = $request->input('schedule');
        $employee_position->emp_shift = $request->input('emp_shift');
        $employee_position->remarks = $request->input('remarks');

        if ($employee_position->save()) {
            Helpers::LogActivity($employee_position->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - POSITION SECTION');
            return response()->json($employee_position);
        }
    }

    public function updateEmploymentTypeSection(Request $request, $id) {
        $employment_type = $request->input('employment_type');

        $this->validate($request, [
            'employment_type' => 'required',
        ]);
        
        if ($employment_type == 'regular') {
            $this->validate($request, [
                'regularization_date' => 'required'
            ]);
        } else {
            $this->validate($request, [
                'employment_date_start' => 'required',
                'employment_date_end' => 'required'
            ]);
        }

        $employee_status = EmployeeStatus::findOrFail($id);
        $employee_status->employee_id = $request->input('employee_id');
        $employment_type = $request->input('employment_type');

        if ($employment_type == 'regular') {
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('regularization_date');
            $employee_status->employment_date_end = '--';
            $employee_status->regularization_date = $request->input('regularization_date');
            $employee_status->hired_date = $request->input('hired_date');
            $employee_status->hired_date_fix = Carbon::parse($request->input('hired_date'));
            

            if (empty($request->input('hired_date'))) {
                $employee_status->hired_date = $request->input('regularization_date');
                $employee_status->hired_date_fix = Carbon::parse($request->input('regularization_date'));
            }
        } else {
            $employee_status->employment_type_label = $request->input('employment_type_label');
            $employee_status->employment_type = $request->input('employment_type');
            $employee_status->employment_date_start = $request->input('employment_date_start');
            $employee_status->employment_date_end = $request->input('employment_date_end');
            $employee_status->regularization_date = '--';
            $employee_status->hired_date = $request->input('employment_date_start');
            $employee_status->hired_date_fix = Carbon::parse($request->input('employment_date_start'));
        }
        $employee_status->save();

        Helpers::LogActivity($employee_status->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - EMPLOYMENT TYPE SECTION');
    }

    public function updateEmployeeStateSection(Request $request, $id) {
        $employee_state = $request->input('employee_state');

        if (($employee_state == 'extended' || $employee_state == 'suspended' || $employee_state == 'maternity') && $employee_state != 'active') {
            $this->validate($request, [
                'employee_state' => 'required',
                'state_date_start' => 'required',
                'state_date_end' => 'required',
            ]);
        }

        if (!in_array($employee_state, ['extended', 'suspended', 'active', 'maternity'])) {
            $this->validate($request, [
                'employee_state' => 'required',
                'state_date' => 'required',
            ]);
        }

        $this->validate($request, [
            'employee_state' => 'required'
        ]);

        $employee_state = EmployeeState::findOrFail($id);
        $employee_state->employee_id = $request->input('employee_id');
        $employee_state->employee_state_label = $request->input('employee_state_label');
        $employee_state->employee_state = $request->input('employee_state');
        $employee_state->state_date_start = $request->input('state_date_start');
        $employee_state->state_date_end = $request->input('state_date_end');
        $employee_state->state_date = $request->input('state_date');
        $employee_state->save();

        Helpers::LogActivity($employee_state->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - EMPLOYEE STATUS SECTION');
    }

    public function updateEmployeeAddressSection(Request $request, $id) {
        $this->validate($request, [
            'region' => ['required'],
            'province' => ['required'],
            'municipal' => ['required'],
            'barangay' => ['required'],
            // 'street' => ['required'],
            'zip_code' => ['required', 'numeric']
        ]);

        $address = Address::findOrFail($id);

        $address->employee_id = $request->input('employee_id');
        $address->region = $request->input('region');
        $address->province = $request->input('province');
        $address->municipal = $request->input('municipal');
        $address->barangay = $request->input('barangay');
        $address->street = $request->input('street');
        $address->zip_code = $request->input('zip_code');
        $address->detailed_address = $request->input('detailed_address');
        $address->foreign_address = $request->input('foreign_address');
        $address->address_remarks = $request->input('address_remarks');
        $address->save();

        Helpers::LogActivity($address->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - ADDRESS SECTION');
    }

    public function updateAttainmentInfoSection(Request $request, $id) {
        $academic_year_from = $request->input('academic_year_from');
        $academic_year_to = $request->input('academic_year_to');

        $this->validate($request, [
            // 'institution' => ['required'],
            'attainment' => ['required'],
            'course' => ['required'],
            'degree' => ['required'],
            'gpa' => ['nullable','regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/'],
        ]);

        if ($academic_year_from != '' && $academic_year_to == '') {
            throw ValidationException::withMessages([
                'academic_year_to' => ['The \'to\' date is required.']
            ]);
        }

        if ($academic_year_from == '' && $academic_year_to != '') {
            throw ValidationException::withMessages([
                'academic_year_from' => ['The \'from\' date is required.']
            ]);
        }

        $employee_attainment = EmployeeAttainment::findOrFail($id);

        $academic_year_from = $request->input('academic_year_from');
        $academic_year_to = $request->input('academic_year_to');
        $employee_attainment->employee_id = $request->input('employee_id');
        $employee_attainment->attainment = $request->input('attainment');
        $employee_attainment->course = $request->input('course');
        $employee_attainment->degree = $request->input('degree');
        $employee_attainment->honorary = $request->input('honorary');
        $employee_attainment->institution = $request->input('institution');
        $employee_attainment->years = $request->input('years');
        $employee_attainment->academic_year_from = $request->input('academic_year_from');
        $employee_attainment->academic_year_to = $request->input('academic_year_to');
        $employee_attainment->gpa = ($request->input('gpa')) ? $request->input('gpa') : '';
        $employee_attainment->attainment_remarks = ($request->input('attainment_remarks')) ? $request->input('attainment_remarks') : '';

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;
            $filenames = [];

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/attainment_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $employee_attainment->attachment = implode(',', $filenames);
            }
        }

        $employee_attainment->save();

        Helpers::LogActivity($employee_attainment->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - ATTAINMENT SECTION');
    }

    public function updateEmployeeAccountSection(Request $request, $id) {
        $this->validate($request, [
            'sss_no' => ['regex:/^\d{2}-\d{7}-\d{1}$/', 'nullable'],
            'pagibig_no' => ['regex:/^\d{4}-\d{4}-\d{4}$/', 'nullable'],
            'philhealth_no' => ['regex:/^\d{2}-\d{9}-\d{1}$/', 'nullable'],
            'tin_no' => ['regex:/^\d{3}-\d{3}-\d{3}-\d{5}$/', 'nullable']
        ]);

        $employee_account = EmployeeAccount::findOrFail($id);
        $employee_account->employee_id = $request->input('employee_id');
        $employee_account->sss_no = $request->input('sss_no');
        $employee_account->pagibig_no = $request->input('pagibig_no');
        $employee_account->philhealth_no = $request->input('philhealth_no');
        $employee_account->tin_no = $request->input('tin_no');
        $employee_account->bank_name = $request->input('bank_name');
        $employee_account->bank_account_no = $request->input('bank_account_no');
        $employee_account->save();

        Helpers::LogActivity($employee_account->id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - EMPLOYEE ACCOUNT SECTION');
    }

    public function updateFileSection(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        if (empty($request->input('files'))) {
            throw ValidationException::withMessages([
                'file_type' => ['Files are required.']
            ]);
        }

        $data = [];
        $filename = '';

        foreach ($request->input('files') as $key => $file) {
            $file = json_decode($file);
            $uploaded = $request->uploaded[$key];

            if ($uploaded) {
                $filenameWithExt = $uploaded->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $uploaded->storeAs('public/201_files', $fileNameToStore);
                $filename = str_replace(',', '', $fileNameToStore);

                $data = [
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'file' => $filename,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            } else {
                $data = [
                    'file_type' => $file->file_type,
                    'cabinet_number' => $file->cabinet_number,
                    'description' => $file->description,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $update = DB::table('employee_files')
            ->where('id', $id)
            ->update($data);

        Helpers::LogActivity($id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - EMPLOYEE FILES SECTION');
    }

    public function updateContactSection(Request $request, $id) {
        date_default_timezone_set('Asia/Manila');

        $data = [];

        foreach ($request->input('contacts') as $key => $contact) {
            $contact = json_decode($contact);

            $data = [
                'contact_type' => $contact->contact_type,
                'contact_details' => $contact->contact_details,
                'description' => $contact->description,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString()
            ];
        }

        $update = DB::table('employee_contacts')
            ->where('id', $id)
            ->update($data);

        Helpers::LogActivity($id, 'EMPLOYEE REGISTRATION', 'UPDATED EMPLOYEE DATA - EMPLOYEE CONTACT SECTION');
    }

    public function deleteRecord($id) {
        $employee = Employee::findOrFail($id);

        if ($employee->delete()) {
            // position 
            DB::table('employee_positions')
                ->where('employee_id', '=', $id)
                ->delete();

            // employment type 
            DB::table('employee_statuses')
                ->where('employee_id', '=', $id)
                ->delete();

            // address 
            DB::table('addresses')
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

            // receivers
            DB::table('receivers')
                ->where('employee_id', '=', $id)
                ->delete();

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

            Helpers::LogActivity($employee->id, 'EMPLOYEE REGISTRATION', 'DELETED EMPLOYEE DATA');

            return new RegistrationDetailsResources($employee);
        }
    }
}
