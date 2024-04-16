<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\EmployeeDataChangeDetail;
use App\Http\Resources\MonthlyEvaluation as ResourcesMonthlyEvaluation;
use App\Http\Resources\PrintEmployeeDataChangeDetail;
use App\Http\Resources\PrintManpowerApprovers;
use App\Http\Resources\PrintManpowerDefault;
use App\Http\Resources\PrintManpowerEmployeeMovement;
use App\Models\FormHistory;
use App\Models\ManpowerForms;
use App\Models\MonthlyEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrintController extends Controller
{
    public function printMonthly(Request $request, $id) {
        $employee_id = Auth::user()->employee_id;
        $form_id = $request->id;

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

        $monthly_evaluations = $query->where('monthly_evaluations.id', '=', $form_id)->get();
        
        return ResourcesMonthlyEvaluation::collection($monthly_evaluations);
    }

    public function printDataChange(Request $request) {
        $form_id = $request->id;

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
                'employee_datachanges.effectivity_date',
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

        $employee_datachanges = $query->where('employee_datachanges.id', '=', $form_id)->get();
        
        return PrintEmployeeDataChangeDetail::collection($employee_datachanges);
    }

    public function printManpower(Request $request) {
        $requisition_type = 'additional';

        $form_id = $request->id;

        $manpower_details = ManpowerForms::findOrFail($form_id);

        $requisition_type = $manpower_details->requisition_type;

        switch ($requisition_type) {
            case 'replacement_movement':
                
            $manpower = DB::table('manpower_forms')
                ->leftJoin('positions', 'manpower_forms.position_id', '=', 'positions.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as superior_employee_positions', 'superior.id', '=', 'superior_employee_positions.employee_id')
                ->leftJoin('positions as superior_positions', 'superior_employee_positions.position_id', '=', 'superior_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('datachange_forms', 'datachange_forms.manpower_id', '=', 'manpower_forms.id')
                ->leftJoin('da_forms', 'datachange_forms.id', '=', 'da_forms.datachange_id')

                ->leftJoin('employees as datachange_employees', 'datachange_forms.employee_id', '=', 'datachange_employees.id')
                ->leftJoin('transaction_datachanges as datachange_employee_positions', 'datachange_employees.id', '=', 'datachange_employee_positions.employee_id')
                ->leftJoin('positions as datachange_positions', 'datachange_employee_positions.position_id', '=', 'datachange_positions.id')

                ->leftJoin('departments as datachange_departments', 'datachange_positions.department_id', '=', 'datachange_departments.id')
                ->leftJoin('subunits as datachange_subunits', 'datachange_positions.subunit_id', '=', 'datachange_subunits.id')
                ->leftJoin('jobbands as datachange_jobbands', 'datachange_positions.jobband_id', '=', 'datachange_jobbands.id')

                ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
                ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')

                ->leftJoin('employees as employee_tobe_hired', 'manpower_forms.tobe_hired', '=', 'employee_tobe_hired.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = manpower_forms.tobe_hired)')); 
                })
                ->leftJoin('da_evaluations', 'da_forms.id', '=', 'da_evaluations.daform_id')
                ->select([
                    'manpower_forms.id',
                    'manpower_forms.code',
                    'manpower_forms.position_id',
                    'manpower_forms.salary_structure',
                    'manpower_forms.jobrate_name',
                    'manpower_forms.job_level',
                    'manpower_forms.expected_salary',
                    'manpower_forms.manpower_count',
                    'manpower_forms.employment_type',
                    'manpower_forms.employment_type_label',
                    'manpower_forms.requisition_type',
                    'manpower_forms.requisition_type_mark',
                    'manpower_forms.attachment',
                    'manpower_forms.justification',
                    'manpower_forms.replacement_for',
                    'manpower_forms.form_type as manpower_form_type',
                    'manpower_forms.level',
                    'manpower_forms.current_status',
                    'manpower_forms.current_status_mark',
                    'manpower_forms.requestor_id',
                    'manpower_forms.requestor_remarks',
                    'manpower_forms.is_fulfilled',
                    'manpower_forms.date_fulfilled',
                    'manpower_forms.tobe_hired',
                    'manpower_forms.created_at',
                    'positions.position_name',
                    'departments.department_name',

                    'superior.first_name as superior_first_name',
                    'superior.middle_name as superior_middle_name',
                    'superior.last_name as superior_last_name',
                    'superior.suffix as superior_suffix',

                    'superior_positions.position_name as superior_position_name',

                    'requestor.first_name as requestor_first_name',
                    'requestor.middle_name as requestor_middle_name',
                    'requestor.last_name as requestor_last_name',
                    'requestor.suffix as requestor_suffix',

                    'datachange_forms.employee_id',
                    'datachange_forms.change_position_id',
                    'datachange_forms.change_reason',
                    'datachange_forms.for_da',
                    'datachange_forms.manpower_id',
                    'datachange_forms.additional_rate',
                    'datachange_forms.jobrate_name',
                    'datachange_forms.salary_structure',
                    'datachange_forms.job_level',
                    'datachange_forms.allowance',
                    'datachange_forms.job_rate',
                    'datachange_forms.salary',

                    'da_forms.datachange_id',
                    'da_forms.measures as prev_measures',
                    'da_forms.inclusive_date_start',
                    'da_forms.inclusive_date_end',

                    'datachange_employees.prefix_id as datachange_employees_prefix_id',
                    'datachange_employees.id_number as datachange_employees_id_number',
                    'datachange_employees.first_name as datachange_employees_first_name',
                    'datachange_employees.middle_name as datachange_employees_middle_name',
                    'datachange_employees.last_name as datachange_employees_last_name',
                    'datachange_employees.suffix as datachange_employees_suffix',

                    'datachange_positions.position_name as datachange_positions_position_name',
                    'datachange_departments.department_name as datachange_positions_department_name',
                    'datachange_subunits.subunit_name as datachange_positions_subunit_name',
                    'datachange_jobbands.jobband_name as datachange_positions_jobband_name',

                    'change_position.position_name as change_position_name',
                    'change_department.department_name as change_department_name',
                    'change_subunit.subunit_name as change_subunit_name',
                    'change_jobband.jobband_name as change_jobband_name',
                    'datachange_employee_positions.additional_rate as prev_additional_rate',
                    'datachange_employee_positions.jobrate_name as prev_jobrate_name',
                    'datachange_employee_positions.salary_structure as prev_salary_structure',
                    'datachange_employee_positions.job_level as prev_job_level',
                    'datachange_employee_positions.allowance as prev_allowance',
                    'datachange_employee_positions.job_rate as prev_job_rate',
                    'datachange_employee_positions.salary as prev_salary',

                    'employee_tobe_hired.prefix_id as employee_tobe_hired_prefix_id',
                    'employee_tobe_hired.id_number as employee_tobe_hired_id_number',
                    'employee_tobe_hired.first_name as employee_tobe_hired_first_name',
                    'employee_tobe_hired.middle_name as employee_tobe_hired_middle_name',
                    'employee_tobe_hired.last_name as employee_tobe_hired_last_name',
                    'employee_tobe_hired.suffix as employee_tobe_hired_suffix',

                    'employee_statuses.hired_date as employee_hired_date',
                    'da_evaluations.measures as measures',
                    'da_evaluations.assessment_mark'
                ])
                ->where('manpower_forms.id', '=', $form_id)
                ->get();

                return PrintManpowerEmployeeMovement::collection($manpower);

                break;
            
            default:
                
            $manpower = DB::table('manpower_forms')
                ->leftJoin('positions', 'manpower_forms.position_id', '=', 'positions.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as superior_employee_positions', 'superior.id', '=', 'superior_employee_positions.employee_id')
                ->leftJoin('positions as superior_positions', 'superior_employee_positions.position_id', '=', 'superior_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('employees as employee_tobe_hired', 'manpower_forms.tobe_hired', '=', 'employee_tobe_hired.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = manpower_forms.tobe_hired)')); 
                })
                ->select([
                    'manpower_forms.id',
                    'manpower_forms.code',
                    'manpower_forms.position_id',
                    'manpower_forms.salary_structure',
                    'manpower_forms.jobrate_name',
                    'manpower_forms.job_level',
                    'manpower_forms.expected_salary',
                    'manpower_forms.manpower_count',
                    'manpower_forms.employment_type',
                    'manpower_forms.employment_type_label',
                    'manpower_forms.requisition_type',
                    'manpower_forms.requisition_type_mark',
                    'manpower_forms.attachment',
                    'manpower_forms.justification',
                    'manpower_forms.replacement_for',
                    'manpower_forms.form_type as manpower_form_type',
                    'manpower_forms.level',
                    'manpower_forms.current_status',
                    'manpower_forms.current_status_mark',
                    'manpower_forms.requestor_id',
                    'manpower_forms.requestor_remarks',
                    'manpower_forms.is_fulfilled',
                    'manpower_forms.date_fulfilled',
                    'manpower_forms.tobe_hired',
                    'manpower_forms.created_at',
                    'positions.position_name',
                    'departments.department_name',

                    'superior.first_name as superior_first_name',
                    'superior.middle_name as superior_middle_name',
                    'superior.last_name as superior_last_name',
                    'superior.suffix as superior_suffix',

                    'superior_positions.position_name as superior_position_name',

                    'requestor.first_name as requestor_first_name',
                    'requestor.middle_name as requestor_middle_name',
                    'requestor.last_name as requestor_last_name',
                    'requestor.suffix as requestor_suffix',

                    'employee_tobe_hired.prefix_id as employee_tobe_hired_prefix_id',
                    'employee_tobe_hired.id_number as employee_tobe_hired_id_number',
                    'employee_tobe_hired.first_name as employee_tobe_hired_first_name',
                    'employee_tobe_hired.middle_name as employee_tobe_hired_middle_name',
                    'employee_tobe_hired.last_name as employee_tobe_hired_last_name',
                    'employee_tobe_hired.suffix as employee_tobe_hired_suffix',

                    'employee_statuses.hired_date as employee_hired_date',
                ])
                ->where('manpower_forms.id', '=', $form_id)
                ->get();

                return PrintManpowerDefault::collection($manpower);

                break;
        }
    }

    public function getManpowerApprover(Request $request) {
        $form_id = $request->id;
        $form_type = 'manpower-form';

        $approver = DB::table('transaction_statuses')
            ->leftJoin('employees as approver', 'transaction_statuses.reviewer_id', '=', 'approver.id')
            ->leftJoin('employee_positions', 'transaction_statuses.reviewer_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'approver.id',
                'approver.first_name',
                'approver.middle_name',
                'approver.last_name',
                'approver.suffix',
                'transaction_statuses.created_at',
                'positions.position_name'
            ])
            ->where('transaction_statuses.form_id', '=', $form_id)
            ->where('transaction_statuses.form_type', '=', $form_type)
            ->where('transaction_statuses.status', '=', 'approved')
            ->orderBy('transaction_statuses.created_at', 'asc')
            ->groupBy('approver.id')
            ->get();

        return PrintManpowerApprovers::collection($approver);
    }

    public function getDataChangeApprover(Request $request) {
        $form_id = $request->id;
        $form_type = 'employee-datachange';

        $approver = DB::table('transaction_statuses')
            ->leftJoin('employees as approver', 'transaction_statuses.reviewer_id', '=', 'approver.id')
            ->leftJoin('employee_positions', 'transaction_statuses.reviewer_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'approver.id',
                'approver.first_name',
                'approver.middle_name',
                'approver.last_name',
                'approver.suffix',
                'transaction_statuses.created_at',
                'positions.position_name'
            ])
            ->where('transaction_statuses.form_id', '=', $form_id)
            ->where('transaction_statuses.form_type', '=', $form_type)
            ->where('transaction_statuses.status', '=', 'approved')
            ->orderBy('transaction_statuses.created_at', 'asc')
            ->groupBy('approver.id')
            ->get();

        return PrintManpowerApprovers::collection($approver);
    }

    public function getMonthlyApprover(Request $request) {
        $form_id = $request->id;
        $form_type = 'monthly-evaluation';

        $approver = DB::table('transaction_statuses')
            ->leftJoin('employees as approver', 'transaction_statuses.reviewer_id', '=', 'approver.id')
            ->leftJoin('employee_positions', 'transaction_statuses.reviewer_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->select([
                'approver.id',
                'approver.first_name',
                'approver.middle_name',
                'approver.last_name',
                'approver.suffix',
                'transaction_statuses.created_at',
                'positions.position_name'
            ])
            ->where('transaction_statuses.form_id', '=', $form_id)
            ->where('transaction_statuses.form_type', '=', $form_type)
            // ->where('transaction_statuses.status', '=', 'approved')
            ->orderBy('transaction_statuses.created_at', 'asc')
            ->groupBy('approver.id')
            ->get();

        return PrintManpowerApprovers::collection($approver);
    }

    public function getManpowerReceiver(Request $request) {
        $form_id = $request->id;
        $form_type = 'manpower-form';

        $receiver = DB::table('manpower_forms')
            ->leftJoin('employees', 'manpower_forms.requestor_id', '=', 'employees.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('transaction_receivers', function($join) { 
                $join->on('positions.subunit_id', '=', 'transaction_receivers.subunit_id'); 
                $join->on('manpower_forms.form_type', '=', 'transaction_receivers.form_type'); 
                $join->on('manpower_forms.id', '=', 'transaction_receivers.transaction_id');
            })
            ->leftJoin('transaction_statuses', function($join) { 
                $join->on('transaction_receivers.employee_id', '=', 'transaction_statuses.reviewer_id'); 
                $join->on('transaction_receivers.form_type', '=', 'transaction_statuses.form_type'); 
                $join->on('manpower_forms.id', '=', 'transaction_statuses.form_id');
            })
            ->leftJoin('employees as receiver', 'transaction_statuses.reviewer_id', '=', 'receiver.id')
            ->select([
                'transaction_statuses.form_id',
                'receiver.first_name as receiver_first_name',
                'receiver.last_name as receiver_last_name',
                'transaction_statuses.status_mark',
                'transaction_statuses.status',
                'transaction_statuses.reviewer_action',
                'transaction_statuses.created_at'
            ])
            ->where('manpower_forms.id', '=', $form_id)
            ->orderBy('transaction_statuses.created_at', 'DESC')
            ->get()
            ->map(function ($receiver) {
                $receiver->created_at = ($receiver->created_at) ? Carbon::createFromFormat('Y-m-d H:i:s', $receiver->created_at)->format('M d, Y') : '';
                return $receiver;
            })
            ->first();

        return response()->json($receiver);
    }

    public function getDataChangeReceiver(Request $request) {
        $form_id = $request->id;
        $form_type = 'employee-datachange';

        $receiver = DB::table('employee_datachanges')
            ->leftJoin('employees', 'employee_datachanges.requestor_id', '=', 'employees.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('transaction_receivers', function($join) { 
                $join->on('positions.subunit_id', '=', 'transaction_receivers.subunit_id'); 
                $join->on('employee_datachanges.form_type', '=', 'transaction_receivers.form_type'); 
                $join->on('employee_datachanges.id', '=', 'transaction_receivers.transaction_id');
            })
            ->leftJoin('transaction_statuses', function($join) { 
                $join->on('transaction_receivers.employee_id', '=', 'transaction_statuses.reviewer_id'); 
                $join->on('transaction_receivers.form_type', '=', 'transaction_statuses.form_type'); 
                $join->on('employee_datachanges.id', '=', 'transaction_statuses.form_id');
            })
            ->leftJoin('employees as receiver', 'transaction_statuses.reviewer_id', '=', 'receiver.id')
            ->select([
                'transaction_statuses.form_id',
                'receiver.first_name as receiver_first_name',
                'receiver.middle_name as receiver_middle_name',
                'receiver.last_name as receiver_last_name',
                'transaction_statuses.status_mark',
                'transaction_statuses.status',
                'transaction_statuses.reviewer_action',
                'transaction_statuses.created_at'
            ])
            ->where('employee_datachanges.id', '=', $form_id)
            ->orderBy('transaction_statuses.created_at', 'DESC')
            ->get()
            ->map(function ($receiver) {
                $receiver->created_at = ($receiver->created_at) ? Carbon::createFromFormat('Y-m-d H:i:s', $receiver->created_at)->format('M d, Y') : '';
                return $receiver;
            })
            ->first();

        return response()->json($receiver);
    }

    public function getMonthlyReceiver(Request $request) {
        $form_id = $request->id;
        $form_type = 'monthly-evaluation';

        $receiver = DB::table('monthly_evaluations')
            ->leftJoin('employees', 'monthly_evaluations.requestor_id', '=', 'employees.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('transaction_receivers', function($join) { 
                $join->on('positions.subunit_id', '=', 'transaction_receivers.subunit_id'); 
                $join->on('monthly_evaluations.form_type', '=', 'transaction_receivers.form_type'); 
                $join->on('monthly_evaluations.id', '=', 'transaction_receivers.transaction_id');
            })
            ->leftJoin('transaction_statuses', function($join) { 
                $join->on('transaction_receivers.employee_id', '=', 'transaction_statuses.reviewer_id'); 
                $join->on('transaction_receivers.form_type', '=', 'transaction_statuses.form_type'); 
                $join->on('monthly_evaluations.id', '=', 'transaction_statuses.form_id');
            })
            ->leftJoin('employees as receiver', 'transaction_statuses.reviewer_id', '=', 'receiver.id')
            ->select([
                'transaction_statuses.form_id',
                'receiver.first_name as receiver_first_name',
                'receiver.middle_name as receiver_middle_name',
                'receiver.last_name as receiver_last_name',
                'transaction_statuses.status_mark',
                'transaction_statuses.status',
                'transaction_statuses.reviewer_action',
                'transaction_statuses.created_at'
            ])
            ->where('monthly_evaluations.id', '=', $form_id)
            ->orderBy('transaction_statuses.created_at', 'DESC')
            ->get()
            ->map(function ($receiver) {
                $receiver->created_at = ($receiver->created_at) ? Carbon::createFromFormat('Y-m-d H:i:s', $receiver->created_at)->format('M d, Y') : '';
                return $receiver;
            })
            ->first();

        return response()->json($receiver);
    }
}



