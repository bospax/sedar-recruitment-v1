<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Employee as EmployeeResources;
use App\Http\Resources\EmployeeData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EmployeeProfileController extends Controller
{
    public function fetchProfiles(Request $request) {
        $employees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $department_id = request('department_id');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'emp.last_name' : 'emp.created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $query = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->leftJoin('employee_positions', 'emp.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->select([
                        'emp.id',
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
                    ])
                    ->where('emp.current_status', '=', 'approved')
                    ->where(function($query) use ($value){
                        $query->where('emp.last_name', 'LIKE', $value)
                            ->orWhere('emp.middle_name', 'LIKE', $value)
                            ->orWhere('emp.first_name', 'LIKE', $value);
                    });
                    
                if ($department_id) {
                    $employees = $query
                        ->where('positions.department_id', '=', $department_id)
                        ->orderBy($field, $sort)
                        ->paginate(5);
                } else {
                    $employees = $query
                        ->orderBy($field, $sort)
                        ->paginate(5);
                }
            } else {
                $query = DB::table('employees AS emp')
                    ->leftJoin('employees AS ref', 'emp.referrer_id', '=', 'ref.id')
                    ->leftJoin('employee_positions', 'emp.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->select([
                        'emp.id',
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
                    ])
                    ->where('emp.current_status', '=', 'approved');
                    
                if ($department_id) {
                    $employees = $query
                        ->where('positions.department_id', '=', $department_id)
                        ->orderBy($field, $sort)
                        ->paginate(5);
                } else {
                    $employees = $query
                        ->orderBy($field, $sort)
                        ->paginate(5);
                }
            }
        }

        return EmployeeResources::collection($employees);
    }

    public function getPosition($employee_id) {
        $employee_position = DB::table('employee_positions')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->select([
                'employee_positions.id',
                'employee_positions.employee_id',
                'employee_positions.position_id',
                'employee_positions.jobrate_id',
                'employee_positions.additional_rate',
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
                'positions.tools',
                'jobrates.job_level',
                'jobrates.job_rate',
                'jobrates.salary_structure',
                'jobrates.jobrate_name',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name'
            ])
            ->where('employee_positions.employee_id', '=', $employee_id)
            ->get();

        return response()->json($employee_position);
    }

    public static function getAddresses($employee_id) {
        $employee_address = DB::table('addresses')
            ->leftJoin('regions', 'addresses.region', '=', 'regions.reg_code')
            ->leftJoin('provinces', 'addresses.province', '=', 'provinces.prov_code')
            ->leftJoin('municipals', 'addresses.municipal', '=', 'municipals.citymun_code')
            ->leftJoin('barangays', 'addresses.barangay', '=', 'barangays.brgy_code')
            ->select([
                'addresses.id',
                'addresses.street',
                'addresses.zip_code',
                'addresses.detailed_address',
                'addresses.foreign_address',
                'addresses.address_remarks',
                'regions.reg_desc',
                'provinces.prov_desc',
                'municipals.citymun_desc',
                'barangays.brgy_code'
            ])
            ->where('addresses.employee_id', '=', $employee_id)
            ->get();

        return response()->json($employee_address);
    }

    public static function getAttainments($employee_id) {
        $employee_attainments = DB::table('employee_attainments')
            ->select([
                'id',
                'attainment',
                'course',
                'degree',
                'honorary',
                'academic_year_from',
                'academic_year_to',
                'years',
                'gpa',
                'attainment_remarks'
            ])
            ->where('employee_id', '=', $employee_id)
            ->get();

        return response()->json($employee_attainments);
    }

    public function getAccounts($employee_id) {
        $employee_accounts = DB::table('employee_accounts')
            ->select([
                'id',
                'sss_no',
                'pagibig_no',
                'philhealth_no',
                'tin_no',
                'bank_name',
                'bank_account_no'
            ])
            ->where('employee_id', '=', $employee_id)
            ->get();

        return response()->json($employee_accounts);
    }

    public function fetchPrintProfileData(Request $request, $id) {
        $data = [];

        $general = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
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
            ])
            ->where('employees.id', '=', $id)
            ->get()
            ->map(function ($general) {
                $general->full_name = $general->last_name.', '.$general->first_name.' '.$general->suffix.' '.$general->middle_name;
                $general->full_id_number = $general->prefix_id.'-'.$general->id_number;
                $general->r_full_id_number_full_name = $general->r_prefix_id.'-'.$general->r_id_number.' '.$general->r_last_name.', '.$general->r_first_name.' '.$general->r_suffix.' '.$general->r_middle_name;
                return $general;
            })
            ->first();

        $employee_position = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->select([
                'employee_positions.id',
                'employee_positions.employee_id',
                'employee_positions.position_id',
                'employee_positions.jobrate_id',
                'employee_positions.additional_rate',
                'employee_positions.additional_tool',
                'employee_positions.remarks',
                'employee_positions.schedule as emp_schedule',
                'employee_positions.emp_shift',
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
                'jobrates.salary_structure',
                'jobrates.job_level',
                'jobrates.jobrate_name',
                'departments.department_name',
                'subunits.subunit_name'
            ])
            ->where('employee_positions.employee_id', '=', $id)
            ->get()
            ->first();

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
            ->get()
            ->map(function ($employee_attainments) {
                $employee_attainments->attachment = explode(',', $employee_attainments->attachment);
                return $employee_attainments;
            });

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

        $data['general_info'] = $general;
        $data['position'] = ($employee_position) ? $employee_position : '';
        $data['employment'] = $employment;
        $data['status'] = $states;
        $data['addresses'] = $addresses;
        $data['employee_attainments'] = $employee_attainments;
        $data['contacts'] = $contacts;
        $data['employee_accounts'] = $employee_accounts;
        $data['files'] = $files;
        
        return response()->json($data);
    }
}
