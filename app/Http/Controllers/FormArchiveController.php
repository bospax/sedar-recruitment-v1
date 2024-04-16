<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\ManpowerExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Employee as EmployeeResources;
use App\Http\Resources\EmployeeDataChangeArchive;
use App\Http\Resources\EmployeeDataChangeDetail;
use App\Http\Resources\FormArchive as FormArchiveResource;
use App\Http\Resources\FormArchiveDevAssign;
use App\Http\Resources\FormArchiveManpower;
use App\Http\Resources\FormArchiveManpowerComplete;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FormArchiveController extends Controller
{
    public function fetchEmployees(Request $request) {
        $employees = [];
        $department_id = request('department_id');

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
                'emp.manpower_id',
                'emp.manpower_details'
            ])
            ->where('emp.current_status', '=', 'approved');
            
        if ($department_id) {
            $employees = $query
                ->where('positions.department_id', '=', $department_id)
                ->get();
        } else {
            $employees = $query
                ->get();
        }

        return EmployeeResources::collection($employees);
    }

    public function generateArchive(Request $request) {
        $loggin_id = Auth::user()->employee_id;

        $fetch = [];
        $form_type = $request->form_type;
        $is_filtered = $request->is_filtered;
        $daterange = $request->daterange;
        $department_id = $request->department_id;
        $position_id = $request->position_id;
        $requestor_id = $request->requestor_id;
        $keyword = $request->keyword;
        $status = $request->status;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $this->validate($request, [
            'form_type' => ['required']
        ]);

        $types = [
            'monthly-evaluation' => 'monthly_evaluations',
            'probi-evaluation' => 'probi_evaluations',
            'annual-evaluation' => 'annual_evaluations',
            'manpower-form' => 'manpower_forms',
            'da-evaluation' => 'da_evaluations',
            'merit-increase-form' => 'merit_increase_forms'
        ];

        if (isset($types[$form_type])) {
            $table = $types[$form_type];

            switch ($table) {
                case 'manpower_forms':
                    $query = DB::table($table)
                        ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                        ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
                        ->leftJoin('departments', 'requestor_position.department_id', '=', 'departments.id')
                        ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                        ->select([
                            'manpower_forms.id',
                            'manpower_forms.code',
                            'manpower_forms.form_type',
                            'manpower_forms.requestor_id',
                            'manpower_forms.requestor_remarks',
                            'manpower_forms.requisition_type_mark',
                            'manpower_forms.is_fulfilled',
                            'manpower_forms.date_fulfilled',
                            'manpower_forms.created_at',
                            'manpower_forms.updated_at',
                            'requested_position.id as position_id',
                            'requested_position.position_name as requested_position_name',
                            'requestor.prefix_id',
                            'requestor.id_number',
                            'requestor.first_name',
                            'requestor.middle_name',
                            'requestor.last_name',
                            'requestor.suffix',
                            'requestor_position.position_name as requestor_position',
                            'departments.department_name as requestor_department'
                        ]);
                    break;

                case 'da_evaluations':
                    $query = DB::table($table)
                        ->leftJoin('employees as requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                        ->leftJoin('employee_positions', 'da_evaluations.requestor_id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
                        ->leftJoin('departments', 'requestor_position.department_id', '=', 'departments.id')
                        ->leftJoin('employees as incumbent', 'da_evaluations.employee_id', '=', 'incumbent.id')
                        ->leftJoin('da_forms', 'da_evaluations.daform_id', '=', 'da_forms.id')
                        ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                        ->leftJoin('positions as designation', 'datachange_forms.change_position_id', '=', 'designation.id')
                        ->select([
                            'da_evaluations.id',
                            'da_evaluations.code',
                            'da_evaluations.form_type',
                            'da_evaluations.requestor_id',
                            'da_evaluations.requestor_remarks',
                            'da_evaluations.is_fulfilled',
                            'da_evaluations.date_fulfilled',
                            'da_evaluations.created_at',
                            'da_evaluations.updated_at',
                            'requestor.prefix_id',
                            'requestor.id_number',
                            'requestor.first_name',
                            'requestor.middle_name',
                            'requestor.last_name',
                            'requestor.suffix',
                            'incumbent.first_name as incumbent_first_name',
                            'incumbent.middle_name as incumbent_middle_name',
                            'incumbent.last_name as incumbent_last_name',
                            'incumbent.suffix as incumbent_suffix',
                            'designation.position_name as designation_position_name',
                            'requestor_position.position_name as requestor_position',
                            'departments.department_name as requestor_department'
                        ]);
                    break;
                
                default:
                    $query = DB::table($table)
                        ->leftJoin('employees as requestor', $table.'.requestor_id', '=', 'requestor.id')
                        ->leftJoin('employee_positions', $table.'.requestor_id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                        ->select([
                            $table.'.id',
                            $table.'.code',
                            $table.'.form_type',
                            $table.'.requestor_id',
                            $table.'.requestor_remarks',
                            $table.'.is_fulfilled',
                            $table.'.date_fulfilled',
                            $table.'.created_at',
                            $table.'.updated_at',
                            'requestor.prefix_id',
                            'requestor.id_number',
                            'requestor.first_name',
                            'requestor.middle_name',
                            'requestor.last_name',
                            'requestor.suffix',
                            'positions.position_name as requestor_position',
                            'departments.department_name as requestor_department'
                        ]);
                    break;
            }

            $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) use ($table) {
                $query->leftJoin('form_history as permission', $table.'.id', '=', 'permission.form_id');
            });
    
            if (!Helpers::checkPermission('show_alluser_archive')) {
                $query->where('permission.reviewer_id', '=', $loggin_id);
            }

            if (!empty($daterange)) {
                $daterange = explode('-', $daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $query->whereBetween($table.'.created_at', [$dateFrom, $dateTo]);
            }

            if ($department_id) {
                $query->where('departments.id', '=', $department_id);
            }

            if ($position_id && $table == 'manpower_forms') {
                $query->where('requested_position.id', '=', $position_id);
            }

            if ($requestor_id) {
                $query->where($table.'.requestor_id', '=', $requestor_id);
            }

            if ($status && $status != 'UPDATED') {
                $query->where($table.'.is_fulfilled', '=', $status);
            }

            if ($status == 'UPDATED') {
                $query->where($table.'.employee_data_status', '=', $status);
            }

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $query->where($table.'.code', 'LIKE', $value);
            }

            $fetch = $query
                // ->where($table.'.current_status', '=', 'approved')
                // ->where($table.'.is_fulfilled', '=', 'FILED')
                ->paginate(20);
        }

        return FormArchiveResource::collection($fetch);
    }

    public function generateDAArchive(Request $request) {
        $loggin_id = Auth::user()->employee_id;

        $fetch = [];
        $form_type = $request->form_type;
        $is_filtered = $request->is_filtered;
        $daterange = $request->daterange;
        $department_id = $request->department_id;
        $position_id = $request->position_id;
        $requestor_id = $request->requestor_id;
        $keyword = $request->keyword;
        $status = $request->status;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $this->validate($request, [
            'form_type' => ['required']
        ]);

        $types = [
            'monthly-evaluation' => 'monthly_evaluations',
            'probi-evaluation' => 'probi_evaluations',
            'annual-evaluation' => 'annual_evaluations',
            'manpower-form' => 'manpower_forms',
            'da-evaluation' => 'da_evaluations',
            'merit-increase-form' => 'merit_increase_forms'
        ];

        if (isset($types[$form_type])) {
            $table = $types[$form_type];

            $query = DB::table('da_evaluations')
                ->leftJoin('employees as requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions', 'da_evaluations.requestor_id', '=', 'employee_positions.employee_id')
                ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
                ->leftJoin('departments', 'requestor_position.department_id', '=', 'departments.id')
                ->leftJoin('employees as incumbent', 'da_evaluations.employee_id', '=', 'incumbent.id')
                ->leftJoin('da_forms', 'da_evaluations.daform_id', '=', 'da_forms.id')
                ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                ->leftJoin('positions as designation', 'datachange_forms.change_position_id', '=', 'designation.id')
                ->select([
                    'da_evaluations.id',
                    'da_evaluations.code',
                    'da_evaluations.form_type',
                    'da_evaluations.requestor_id',
                    'da_evaluations.requestor_remarks',
                    'da_evaluations.is_fulfilled',
                    'da_evaluations.date_fulfilled',
                    'da_evaluations.created_at',
                    'da_evaluations.updated_at',
                    'da_evaluations.employee_data_status',
                    'da_evaluations.effectivity_date',
                    'requestor.prefix_id',
                    'requestor.id_number',
                    'requestor.first_name',
                    'requestor.middle_name',
                    'requestor.last_name',
                    'requestor.suffix',
                    'incumbent.first_name as incumbent_first_name',
                    'incumbent.middle_name as incumbent_middle_name',
                    'incumbent.last_name as incumbent_last_name',
                    'incumbent.suffix as incumbent_suffix',
                    'designation.position_name as designation_position_name',
                    'requestor_position.position_name as requestor_position',
                    'departments.department_name as requestor_department'
                ]);

            $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) use ($table) {
                $query->leftJoin('form_history as permission', $table.'.id', '=', 'permission.form_id');
            });
    
            if (!Helpers::checkPermission('show_alluser_archive')) {
                $query->where('permission.reviewer_id', '=', $loggin_id);
            }

            if (!empty($daterange)) {
                $daterange = explode('-', $daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $query->whereBetween($table.'.created_at', [$dateFrom, $dateTo]);
            }

            if ($department_id) {
                $query->where('departments.id', '=', $department_id);
            }

            if ($position_id && $table == 'manpower_forms') {
                $query->where('requested_position.id', '=', $position_id);
            }

            if ($requestor_id) {
                $query->where($table.'.requestor_id', '=', $requestor_id);
            }

            if ($status && $status != 'UPDATED') {
                $query->where($table.'.is_fulfilled', '=', $status);
            }

            if ($status == 'UPDATED') {
                $query->where($table.'.employee_data_status', '=', $status);
            }

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $query->where($table.'.code', 'LIKE', $value);
            }

            $fetch = $query
                // ->where($table.'.current_status', '=', 'approved')
                // ->where($table.'.is_fulfilled', '=', 'FILED')
                ->paginate(20);
        }

        return FormArchiveDevAssign::collection($fetch);
    }

    public function generateArchiveDataChange(Request $request) {
        $loggin_id = Auth::user()->employee_id;

        $fetch = [];
        $form_type = $request->form_type;
        $is_filtered = $request->is_filtered;
        $daterange = $request->daterange;
        $department_id = $request->department_id;
        $requestor_id = $request->requestor_id;
        $keyword = $request->keyword;
        $status = $request->status;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $this->validate($request, [
            'form_type' => ['required']
        ]);

        $types = [
            'employee-datachange' => 'employee_datachanges'
        ];

        if (isset($types[$form_type])) {
            $table = $types[$form_type];

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
                    'employee_datachanges.change_reason',
                    'employee_datachanges.attachment',
                    'employee_datachanges.form_type',
                    'employee_datachanges.level',
                    'employee_datachanges.current_status',
                    'employee_datachanges.current_status_mark',
                    'employee_datachanges.requestor_id',
                    'employee_datachanges.requestor_remarks',
                    'employee_datachanges.is_fulfilled',
                    'employee_datachanges.date_fulfilled',
                    'employee_datachanges.created_at',
                    'employee_datachanges.effectivity_date',
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

                    'employee_datachanges.employee_data_status'
                ]);

            $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) {
                $query->leftJoin('form_history as permission', 'employee_datachanges.id', '=', 'permission.form_id');
            });
    
            if (!Helpers::checkPermission('show_alluser_archive')) {
                $query->where('permission.reviewer_id', '=', $loggin_id);
            }

            if (!empty($daterange)) {
                $daterange = explode('-', $daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

                $query->whereBetween($table.'.created_at', [$dateFrom, $dateTo]);
            }

            if ($department_id) {
                $query->where('departments.id', '=', $department_id);
            }

            if ($requestor_id) {
                $query->where($table.'.requestor_id', '=', $requestor_id);
            }

            if ($status && $status != 'UPDATED') {
                $query->where($table.'.is_fulfilled', '=', $status);
            }

            if ($status == 'UPDATED') {
                $query->where($table.'.employee_data_status', '=', $status);
            }

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $query->where($table.'.code', 'LIKE', $value);
            }

            $fetch = $query
                // ->where($table.'.current_status', '=', 'approved')
                // ->where($table.'.is_fulfilled', '=', 'FILED')
                ->paginate(20);
        }

        return EmployeeDataChangeArchive::collection($fetch);
    }

    public function generateArchiveManpower(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $loggin_id = Auth::user()->employee_id;
        $fetch = [];
        $filters = (isset($request->filters)) ? $request->filters : [];
        $daterange = $request->daterange;
        $position = $request->position;
        $department = $request->department;
        $code = $request->code;
        $status = $request->status;
        $requesttype = $request->requesttype;
        $requestor = $request->requestor;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $query = DB::table('manpower_forms')
            ->leftJoin('employees as tobe_hired', 'manpower_forms.tobe_hired', '=', 'tobe_hired.id')
            ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
            ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
            ->leftJoin('departments', 'requested_position.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'requested_position.subunit_id', '=', 'subunits.id')
            ->leftJoin('employee_statuses', 'tobe_hired.id', '=', 'employee_statuses.employee_id')
            ->leftJoin('form_history', function($join) {
                $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type AND form_history.is_fulfilled != "FILED")')); 
            })
            ->select([
                'manpower_forms.id',
                'manpower_forms.code',
                'manpower_forms.form_type',
                'manpower_forms.job_level',
                'manpower_forms.expected_salary',
                'manpower_forms.employment_type',
                'manpower_forms.hiring_type',
                'manpower_forms.effectivity_date',
                'manpower_forms.requestor_id',
                'manpower_forms.requestor_remarks',
                'manpower_forms.requisition_type_mark',
                'manpower_forms.is_fulfilled',
                'manpower_forms.date_fulfilled',
                'manpower_forms.created_at',
                'manpower_forms.updated_at',
                'manpower_forms.employee_data_status',
                'employee_statuses.hired_date',
                'employee_statuses.hired_date_fix',
                'requested_position.id as position_id',
                'requested_position.position_name as requested_position_name',
                'requestor.prefix_id',
                'requestor.id_number',
                'requestor.first_name',
                'requestor.middle_name',
                'requestor.last_name',
                'requestor.suffix',
                'requestor_position.position_name as requestor_position',
                'departments.department_name as requestor_department',
                'subunits.subunit_name',
                'tobe_hired.first_name as tobe_hired_first_name',
                'tobe_hired.middle_name as tobe_hired_middle_name',
                'tobe_hired.last_name as tobe_hired_last_name',
                'tobe_hired.suffix as tobe_hired_suffix',
                'form_history.review_date',
            ]);

        $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) {
            $query->leftJoin('form_history as permission', 'manpower_forms.id', '=', 'permission.form_id');
        });

        if (!Helpers::checkPermission('show_alluser_archive')) {
            $query->where('permission.reviewer_id', '=', $loggin_id);
        }

        if (!empty($daterange) && in_array('DATERANGE', $filters)) {
            $daterange = explode('-', $daterange);
            $from = $daterange[0];
            $to = $daterange[1];
            $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
            $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

            $query->whereBetween('manpower_forms.created_at', [$dateFrom, $dateTo]);
        }

        if ($position) {
            $query->where('requested_position.position_name', '=', $position);
        }

        if ($department) {
            $query->where('departments.department_name', '=', $department);
        }

        if ($code) {
            $query->where('manpower_forms.code', '=', $code);
        }

        if ($status && $status != 'UPDATED') {
            $query->where('manpower_forms.is_fulfilled', '=', $status);
        }

        if ($status == 'UPDATED') {
            $query->where('manpower_forms.employee_data_status', '=', $status);
        }

        if ($requesttype) {
            $query->where('manpower_forms.requisition_type_mark', '=', $requesttype);
        }

        if ($requestor) {
            $requestor = str_replace(' ', '', $requestor);
            $query->whereRaw("CONCAT(REPLACE(requestor.last_name, ' ', ''), ',', REPLACE(requestor.first_name, ' ', ''), REPLACE(requestor.middle_name, ' ', '')) LIKE ?", ["%$requestor%"]);
        }

        $fetch = $query
            // ->where($table.'.current_status', '=', 'approved')
            // ->where($table.'.is_fulfilled', '=', 'FILED')
            ->distinct('manpower_forms.id')
            ->paginate(20);

        return FormArchiveManpowerComplete::collection($fetch);
    }

    public function generateArchiveManpowerUnpaginated(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $loggin_id = Auth::user()->employee_id;
        $fetch = [];
        $filters = (isset($request->filters)) ? $request->filters : [];
        $daterange = $request->daterange;
        $position = $request->position;
        $department = $request->department;
        $code = $request->code;
        $status = $request->status;
        $requesttype = $request->requesttype;
        $requestor = $request->requestor;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $query = DB::table('manpower_forms')
            ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
            ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
            ->leftJoin('departments', 'requestor_position.department_id', '=', 'departments.id')
            ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
            ->select([
                'manpower_forms.id',
                'manpower_forms.code',
                'manpower_forms.form_type',
                'manpower_forms.requestor_id',
                'manpower_forms.requestor_remarks',
                'manpower_forms.requisition_type_mark',
                'manpower_forms.is_fulfilled',
                'manpower_forms.date_fulfilled',
                'manpower_forms.created_at',
                'manpower_forms.updated_at',
                'manpower_forms.employee_data_status',
                'requested_position.id as position_id',
                'requested_position.position_name as requested_position_name',
                'requestor.prefix_id',
                'requestor.id_number',
                'requestor.first_name',
                'requestor.middle_name',
                'requestor.last_name',
                'requestor.suffix',
                'requestor_position.position_name as requestor_position',
                'departments.department_name as requestor_department'
            ]);

        $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) {
            $query->leftJoin('form_history as permission', 'manpower_forms.id', '=', 'permission.form_id');
        });

        if (!Helpers::checkPermission('show_alluser_archive')) {
            $query->where('permission.reviewer_id', '=', $loggin_id);
        }

        if (!empty($daterange) && in_array('DATERANGE', $filters)) {
            $daterange = explode('-', $daterange);
            $from = $daterange[0];
            $to = $daterange[1];
            $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
            $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";

            $query->whereBetween('manpower_forms.created_at', [$dateFrom, $dateTo]);
        }

        if ($position) {
            $query->where('requested_position.position_name', '=', $position);
        }

        if ($department) {
            $query->where('departments.department_name', '=', $department);
        }

        if ($code) {
            $query->where('manpower_forms.code', '=', $code);
        }

        if ($status && $status != 'UPDATED') {
            $query->where('manpower_forms.is_fulfilled', '=', $status);
        }

        if ($status == 'UPDATED') {
            $query->where('manpower_forms.employee_data_status', '=', $status);
        }

        if ($requesttype) {
            $query->where('manpower_forms.requisition_type_mark', '=', $requesttype);
        }

        if ($requestor) {
            $requestor = str_replace(' ', '', $requestor);
            $query->whereRaw("CONCAT(REPLACE(requestor.last_name, ' ', ''), ',', REPLACE(requestor.first_name, ' ', ''), REPLACE(requestor.middle_name, ' ', '')) LIKE ?", ["%$requestor%"]);
        }

        $fetch = $query
            // ->where($table.'.current_status', '=', 'approved')
            // ->where($table.'.is_fulfilled', '=', 'FILED')
            ->distinct('manpower_forms.id')
            ->get();

        return FormArchiveManpower::collection($fetch);
    }

    public function generateManpowerReport(Request $request) {
        date_default_timezone_set('Asia/Manila');
        
        $loggin_id = Auth::user()->employee_id;

        $form_type = $request->form_type;
        $fetch = [];
        $filters = (isset($request->filters)) ? $request->filters : [];
        $daterange = $request->daterange;
        $position = $request->position;
        $department = $request->department;
        $code = $request->code;
        $status = $request->status;
        $requesttype = $request->requesttype;
        $requestor = $request->requestor;

        $status = ($status == 'APPROVED') ? 'FILED' : $status;

        $this->validate($request, [
            'form_type' => ['required']
        ]);

        $types = [
            'monthly-evaluation' => 'monthly_evaluations',
            'probi-evaluation' => 'probi_evaluations',
            'annual-evaluation' => 'annual_evaluations',
            'manpower-form' => 'manpower_forms',
            'da-evaluation' => 'da_evaluations',
            'merit-increase-form' => 'merit_increase_forms'
        ];

        if (isset($types[$form_type])) {
            $table = 'manpower_forms';

            switch ($table) {
                case 'manpower_forms':
                    $query = DB::table($table)
                        ->leftJoin('employees as tobe_hired', 'manpower_forms.tobe_hired', '=', 'tobe_hired.id')
                        ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                        ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions as requestor_position', 'employee_positions.position_id', '=', 'requestor_position.id')
                        ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                        ->leftJoin('departments', 'requested_position.department_id', '=', 'departments.id')
                        ->leftJoin('subunits', 'requested_position.subunit_id', '=', 'subunits.id')
                        ->leftJoin('employee_statuses', 'tobe_hired.id', '=', 'employee_statuses.employee_id')
                        ->leftJoin('form_history', function($join) {
                            $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type AND form_history.is_fulfilled != "FILED")')); 
                        })
                        ->select([
                            'manpower_forms.id',
                            'manpower_forms.code',
                            'manpower_forms.form_type',
                            'manpower_forms.job_level',
                            'manpower_forms.expected_salary',
                            'manpower_forms.employment_type',
                            'manpower_forms.hiring_type',
                            'manpower_forms.effectivity_date',
                            'manpower_forms.requestor_id',
                            'manpower_forms.requestor_remarks',
                            'manpower_forms.requisition_type_mark',
                            'manpower_forms.is_fulfilled',
                            'manpower_forms.date_fulfilled',
                            'manpower_forms.created_at',
                            'manpower_forms.updated_at',
                            'employee_statuses.hired_date',
                            'employee_statuses.hired_date_fix',
                            'requested_position.id as position_id',
                            'requested_position.position_name as requested_position_name',
                            'requestor.prefix_id',
                            'requestor.id_number',
                            'requestor.first_name',
                            'requestor.middle_name',
                            'requestor.last_name',
                            'requestor.suffix',
                            'requestor_position.position_name as requestor_position',
                            'departments.department_name',
                            'subunits.subunit_name',
                            'tobe_hired.first_name as tobe_hired_first_name',
                            'tobe_hired.middle_name as tobe_hired_middle_name',
                            'tobe_hired.last_name as tobe_hired_last_name',
                            'tobe_hired.suffix as tobe_hired_suffix',
                            'form_history.review_date',
                        ])
                        ->distinct('manpower_forms.id');
                    break;
                
                default:
                    break;
            }

            $query->when(!Helpers::checkPermission('show_alluser_archive'), function ($query) {
                $query->leftJoin('form_history as permission', 'manpower_forms.id', '=', 'permission.form_id');
            });
    
            if (!Helpers::checkPermission('show_alluser_archive')) {
                $query->where('permission.reviewer_id', '=', $loggin_id);
            }

            if (!empty($daterange) && in_array('DATERANGE', $filters)) {
                $daterange = explode('-', $daterange);
                $from = $daterange[0];
                $to = $daterange[1];
                $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
                $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";
    
                $query->whereBetween('manpower_forms.created_at', [$dateFrom, $dateTo]);
            }
    
            if ($position) {
                $query->where('requested_position.position_name', '=', $position);
            }
    
            if ($department) {
                $query->where('departments.department_name', '=', $department);
            }
    
            if ($code) {
                $query->where('manpower_forms.code', '=', $code);
            }
    
            if ($status) {
                $query->where('manpower_forms.is_fulfilled', '=', $status);
            }
    
            if ($requesttype) {
                $query->where('manpower_forms.requisition_type_mark', '=', $requesttype);
            }
    
            if ($requestor) {
                $requestor = str_replace(' ', '', $requestor);
                $query->whereRaw("CONCAT(REPLACE(requestor.last_name, ' ', ''), ',', REPLACE(requestor.first_name, ' ', ''), REPLACE(requestor.middle_name, ' ', '')) LIKE ?", ["%$requestor%"]);
            }

            $query->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'manpower-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $subunit_export = new ManpowerExport($query);
                $subunit_export->store('public/files/'.$filename);
            }

            return response()->json([
                'link' => $link,
                'count' => $count
            ]);
        }
    }
}
