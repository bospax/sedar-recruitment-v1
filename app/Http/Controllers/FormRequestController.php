<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Resources\AnnualEvaluationDetail as ResourcesAnnualEvaluationDetail;
use App\Http\Resources\AnnualEvaluationHistoryDetails;
use App\Http\Resources\ApproverHistoryDetails as ApproverHistoryDetailsResource;
use App\Http\Resources\DaEvaluationDetail as ResourcesDaEvaluationDetail;
use App\Http\Resources\DaEvaluationDetailRevised;
use App\Http\Resources\DaEvaluationDetailRevisedII;
use App\Http\Resources\DaEvaluationHistoryDetails as DaEvaluationHistoryDetailsResource;
use App\Http\Resources\DaEvaluationWithManpowerCode;
use App\Http\Resources\EmployeeDataChangeDetail;
use App\Http\Resources\EmployeeDataChangeRequestDetail;
use App\Http\Resources\FormHistory as FormHistoryResources;
use App\Http\Resources\ManpowerDetail as ManpowerDetailResource;
use App\Http\Resources\MeritIncreaseDetail as MeritIncreaseDetailResource;
use App\Http\Resources\MonthlyEvaluationDetail as ResourcesMonthlyEvaluationDetail;
use App\Http\Resources\MeritIncreaseHistoryDetails as MeritIncreaseHistoryDetailsResource;
use App\Http\Resources\MonthlyEvaluationHistoryDetails as MonthlyEvaluationHistoryDetailsResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProbiEvaluationDetail as ResourcesProbiEvaluationDetail;
use App\Http\Resources\ProbiEvaluationHistoryDetails as ProbiEvaluationHistoryDetailsResource;
use App\Models\AnnualEvaluation;
use App\Models\DaEvaluation;
use App\Models\DaForms;
use App\Models\DatachangeForms;
use App\Models\EmployeeDataChange;
use App\Models\EmployeeState;
use App\Models\EmployeeStatus;
use App\Models\FormHistory;
use App\Models\ManpowerForms;
use App\Models\MeritIncreaseForm;
use App\Models\MonthlyEvaluation;
use App\Models\ProbiEvaluation;
use App\Models\Role;
use App\Models\TransactionStatuses;
use App\Models\User;
use Carbon\Carbon;

class FormRequestController extends Controller
{
    public function getFormRequests()
    {
        $employee_id = Auth::user()->employee_id;

        $formtypes = DB::table('transaction_approvers')
            ->select([
                'transaction_approvers.form_type',
                'transaction_approvers.label'
            ])
            ->where('transaction_approvers.employee_id', '=', $employee_id)
            ->groupBy('transaction_approvers.form_type')
            ->get();

        $forms = [];

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;

            if ($form_type == 'probi-evaluation') {
                $getprobi = DB::table('forms')
                    ->leftJoin('probi_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
                        $join->on('forms.level', '=', 'probi_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type',
                        'forms.label',
                        'forms.action',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('probi_evaluations.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'probi-evaluation')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getprobi->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getprobi->count(),
                        'requests' => ResourcesProbiEvaluationDetail::collection($getprobi),
                    ];
                }
            }

            if ($form_type == 'annual-evaluation') {
                $getannual = DB::table('forms')
                    ->leftJoin('annual_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'annual_evaluations.form_type'); 
                        $join->on('forms.level', '=', 'annual_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type',
                        'forms.label',
                        'forms.action',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('annual_evaluations.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'annual-evaluation')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getannual->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getannual->count(),
                        'requests' => ResourcesAnnualEvaluationDetail::collection($getannual),
                    ];
                }
            }

            if ($form_type == 'da-evaluation') {
                $getda = DB::table('transaction_approvers')
                    ->leftJoin('da_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'da_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'da_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'da_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'da_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    
                    // ->leftJoin('employee_positions', 'da_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'requestor_employee_position.position_id', '=', 'positions.id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                   
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->leftJoin('da_forms', 'employees.id', '=', 'da_forms.employee_id')
                    ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')

                    ->leftJoin('transaction_datachanges', function($join) { 
                        $join->on('datachange_forms.form_type', '=', 'transaction_datachanges.form_type'); 
                        $join->on('datachange_forms.id', '=', 'transaction_datachanges.transaction_id'); 
                    })

                    ->leftJoin('positions as previous_positions', 'transaction_datachanges.position_id', '=', 'previous_positions.id')
                    ->leftJoin('departments', 'previous_positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'previous_positions.subunit_id', '=', 'subunits.id')

                    ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                    ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                    ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
                    ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')
                    ->select([
                        'transaction_approvers.form_type',
                        'transaction_approvers.label',
                        'transaction_approvers.action',
                        'da_evaluations.id',
                        'da_evaluations.code',
                        'da_evaluations.employee_id',
                        'da_evaluations.daform_id',
                        'da_evaluations.measures',
                        'da_evaluations.total_grade',
                        'da_evaluations.total_target',
                        'da_evaluations.attachment',
                        'da_evaluations.assessment',
                        'da_evaluations.assessment_mark',
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
                        'da_evaluations.effectivity_date',
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
                        'requestor_positions.position_name as requestor_position',
                        'previous_positions.position_name',
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
                    ])
                    ->where('da_evaluations.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'da-evaluation')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => DaEvaluationDetailRevised::collection($getda),
                    ];
                }
            }

            if ($form_type == 'employee-datachange') {
                $getdc = DB::table('transaction_approvers')
                    ->leftJoin('employee_datachanges', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'employee_datachanges.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'employee_datachanges.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'employee_datachanges.level'); 
                    })
                    ->leftJoin('employees', 'employee_datachanges.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'employee_datachanges.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'employee_datachanges.requestor_id', '=', 'requestor_employee_position.employee_id')

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
                    
                    // ->rightJoin('positions as right_join_position', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'right_join_position.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'right_join_position.subunit_id'); 
                    // })

                    ->leftJoin('positions as right_join_position', 'requestor_employee_position.position_id', '=', 'right_join_position.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->select([
                        'transaction_approvers.form_type',
                        'transaction_approvers.label',
                        'transaction_approvers.action',
                        
                        'employee_datachanges.id',
                        'employee_datachanges.code',
                        'employee_datachanges.employee_id',
                        'employee_datachanges.change_reason',
                        'employee_datachanges.attachment',
                        'employee_datachanges.level',
                        'employee_datachanges.current_status',
                        'employee_datachanges.current_status_mark',
                        'employee_datachanges.requestor_id',
                        'employee_datachanges.requestor_remarks',
                        'employee_datachanges.is_fulfilled',
                        'employee_datachanges.date_fulfilled',
                        'employee_datachanges.created_at',
                        'employee_datachanges.effectivity_date',

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
                        'right_join_position.id AS right_join_position_id',
                        'requestor_positions.position_name as requestor_position',

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
                    ])
                    ->where('employee_datachanges.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'employee-datachange')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getdc->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getdc->count(),
                        'requests' => EmployeeDataChangeRequestDetail::collection($getdc),
                    ];
                }
            }

            if ($form_type == 'monthly-evaluation') {
                $getmo = DB::table('transaction_approvers')
                    ->leftJoin('monthly_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'monthly_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'monthly_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'monthly_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'transaction_approvers.form_type',
                        'transaction_approvers.label',
                        'transaction_approvers.action',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('monthly_evaluations.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'monthly-evaluation')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmo->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmo->count(),
                        'requests' => ResourcesMonthlyEvaluationDetail::collection($getmo)
                    ];
                }
            }

            if ($form_type == 'employee-registration') {
                $getregister = DB::table('transaction_approvers')
                    ->leftJoin('employees', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'employees.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'employees.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'employees.level'); 
                    })
                    ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees AS requestor', 'employees.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'employees.requestor_id', '=', 'requestor_employee_position.employee_id')
                    
                    // ->rightJoin('positions as request', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'request.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'request.subunit_id'); 
                    // })

                    ->leftJoin('positions as request', 'requestor_employee_position.position_id', '=', 'request.id')

                    ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->select([
                        'transaction_approvers.form_type',
                        'transaction_approvers.label',
                        'transaction_approvers.action',
                        'employees.id',
                        'employees.prefix_id',
                        'employees.code',
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
                        'employees.referrer_id',
                        'employees.manpower_id',
                        'employees.manpower_details',
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
                        'positions.position_name',
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name',
                        'jobbands.jobband_name',
                        'jobrates.job_level',
                        'jobrates.salary_structure',
                        'jobrates.jobrate_name'
                    ])
                    ->where('employees.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'employee-registration')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getregister->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getregister->count(),
                        'requests' => $getregister
                    ];
                }
            }

            if ($form_type == 'manpower-form') {
                $getmanpower = DB::table('transaction_approvers')
                    ->leftJoin('manpower_forms', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'manpower_forms.id');
                        $join->on('transaction_approvers.form_type', '=', 'manpower_forms.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'manpower_forms.level'); 
                    })
                    ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'manpower_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id') // new join for positions
                    ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->select([
                        'transaction_approvers.form_type',
                        'transaction_approvers.label',
                        'transaction_approvers.action',
                        'manpower_forms.id',
                        'manpower_forms.code',
                        'manpower_forms.position_id',
                        'manpower_forms.jobrate_id',
                        'manpower_forms.manpower_count',
                        'manpower_forms.employment_type',
                        'manpower_forms.employment_type_label',
                        'manpower_forms.requisition_type',
                        'manpower_forms.requisition_type_mark',
                        'manpower_forms.attachment',
                        'manpower_forms.justification',
                        'manpower_forms.replacement_for',
                        'manpower_forms.form_type',
                        'manpower_forms.level',
                        'manpower_forms.current_status',
                        'manpower_forms.current_status_mark',
                        'manpower_forms.requestor_id',
                        'manpower_forms.requestor_remarks',
                        'manpower_forms.is_fulfilled',
                        'manpower_forms.date_fulfilled',
                        'manpower_forms.created_at',
                        'requestor.prefix_id AS req_prefix_id',
                        'requestor.id_number AS req_id_number',
                        'requestor.first_name AS req_first_name',
                        'requestor.middle_name AS req_middle_name',
                        'requestor.last_name AS req_last_name',
                        'requestor.suffix AS req_suffix',
                        // 'positions.id AS position_id',
                        // 'positions.position_name',
                        'requested_position.position_name as requested_position_name',
                        'requestor_positions.position_name',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('manpower_forms.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'manpower-form')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmanpower->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmanpower->count(),
                        'requests' => ManpowerDetailResource::collection($getmanpower),
                    ];
                }
            }

            if ($form_type == 'merit-increase-form') {
                $getmerit = DB::table('forms')
                    ->leftJoin('merit_increase_forms', function($join) { 
                        $join->on('forms.form_type', '=', 'merit_increase_forms.form_type'); 
                        $join->on('forms.level', '=', 'merit_increase_forms.level'); 
                    })
                    ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'merit_increase_forms.employee_id', '=', 'employee_positions.employee_id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type',
                        'forms.label',
                        'forms.action',
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
                        'requestor_positions.position_name as requestor_position',
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
                    ])
                    ->where('merit_increase_forms.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'merit-increase-form')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getmerit->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmerit->count(),
                        'requests' => MeritIncreaseDetailResource::collection($getmerit),
                    ];
                }
            }
        }

        return response()->json($forms);
    }

    public function getFormConfirmation() {
        $employee_id = Auth::user()->employee_id;

        // $formtypes = DB::table('forms')
        //     ->select([
        //         'forms.form_type',
        //         'forms.label'
        //     ])
        //     ->where('forms.employee_id', '=', $employee_id)
        //     ->groupBy('forms.form_type')
        //     ->get();

        $formtypes = collect([
            (object) [
                'form_type' => 'probi-evaluation',
                'label' => 'PROBI EVALUATION'
            ],
            (object) [
                'form_type' => 'monthly-evaluation',
                'label' => 'MONTHLY EVALUATION'
            ],
            (object) [
                'form_type' => 'da-evaluation',
                'label' => 'DA EVALUATION'
            ],
        ]);

        $forms = [];

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;
            $label = $value->label;

            if ($form_type == 'monthly-evaluation') {
                $getmo = DB::table('monthly_evaluations')
                    // ->leftJoin('monthly_evaluations', function($join) { 
                    //     $join->on('forms.form_type', '=', 'monthly_evaluations.form_type'); 
                    //     $join->on('forms.level', '=', 'monthly_evaluations.level'); 
                    // })
                    ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        // 'forms.form_type',
                        // 'forms.label',
                        // 'forms.action',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('monthly_evaluations.is_confirmed', '!=', 'CONFIRMED')
                    // ->where('forms.form_type', '=', 'monthly-evaluation')
                    // ->where('forms.employee_id', '=', $employee_id)
                    ->where('monthly_evaluations.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getmo->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmo->count(),
                        'requests' => ResourcesMonthlyEvaluationDetail::collection($getmo)
                    ];
                }
            }

            if ($form_type == 'probi-evaluation') {
                $getprobi = DB::table('probi_evaluations')
                    // ->leftJoin('probi_evaluations', function($join) { 
                    //     $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
                    //     $join->on('forms.level', '=', 'probi_evaluations.level'); 
                    // })
                    ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        // 'forms.form_type',
                        // 'forms.label',
                        // 'forms.action',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name'
                    ])
                    ->where('probi_evaluations.is_confirmed', '!=', 'CONFIRMED')
                    ->where('probi_evaluations.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getprobi->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getprobi->count(),
                        'requests' => ResourcesProbiEvaluationDetail::collection($getprobi),
                    ];
                }
            }

            if ($form_type == 'da-evaluation') {
                $getda = DB::table('da_forms')
                    ->leftJoin('employees', 'da_forms.employee_id', '=', 'employees.id')
                    ->leftJoin('da_evaluations', 'employees.id', '=', 'da_evaluations.employee_id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'da_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'da_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'da_forms.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                    ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                    ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                    ->select([
                        // 'forms.form_type',
                        // 'forms.label',
                        // 'forms.action',
                        'da_forms.id',
                        'da_forms.code',
                        'da_forms.employee_id',
                        'da_evaluations.daform_id',
                        'da_evaluations.measures',
                        'da_evaluations.total_grade',
                        'da_evaluations.attachment',
                        'da_evaluations.assessment',
                        'da_evaluations.assessment_mark',
                        'da_evaluations.form_type',
                        'da_evaluations.level',
                        'da_evaluations.current_status',
                        'da_evaluations.current_status_mark',
                        'da_evaluations.requestor_id',
                        'da_forms.requestor_remarks',
                        'da_evaluations.date_evaluated',
                        'da_forms.is_fulfilled',
                        'da_forms.date_fulfilled',
                        'da_forms.created_at',
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
                        'requestor_positions.position_name as requestor_position',
                        'departments.department_name',
                        'subunits.subunit_name',
                        'da_forms.id as daform_id',
                        'da_forms.inclusive_date_start',
                        'da_forms.inclusive_date_end',
                        'datachange_forms.change_position_id',
                        'datachange_forms.change_reason',
                        'change_position.position_name as change_position_name',
                        'change_department.department_name as change_department_name',
                    ])
                    ->where('da_forms.is_confirmed', '!=', 'CONFIRMED')
                    ->where('da_forms.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => ResourcesDaEvaluationDetail::collection($getda),
                    ];
                }
            }
        }

        return response()->json($forms);
    }

    public function getFormRequestCount() {
        $employee_id = Auth::user()->employee_id;

        $formtypes = DB::table('transaction_approvers')
            ->select([
                'transaction_approvers.form_type',
                'transaction_approvers.label'
            ])
            ->where('transaction_approvers.employee_id', '=', $employee_id)
            ->groupBy('transaction_approvers.form_type')
            ->get();

        $forms = [];

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;

            if ($form_type == 'probi-evaluation') {
                $getprobi = DB::table('forms')
                    ->leftJoin('probi_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
                        $join->on('forms.level', '=', 'probi_evaluations.level'); 
                    }) 
                    ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->where('probi_evaluations.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'probi-evaluation')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getprobi->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getprobi->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'annual-evaluation') {
                $getannual = DB::table('forms')
                    ->leftJoin('annual_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'annual_evaluations.form_type'); 
                        $join->on('forms.level', '=', 'annual_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->where('annual_evaluations.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'annual-evaluation')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getannual->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getannual->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'da-evaluation') {
                $getda = DB::table('transaction_approvers')
                    ->leftJoin('da_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'da_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'da_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'da_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'da_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    
                    // ->leftJoin('employee_positions', 'da_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'requestor_employee_position.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                   
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->leftJoin('da_forms', 'employees.id', '=', 'da_forms.employee_id')
                    ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')

                    ->leftJoin('transaction_datachanges', function($join) { 
                        $join->on('datachange_forms.form_type', '=', 'transaction_datachanges.form_type'); 
                        $join->on('datachange_forms.id', '=', 'transaction_datachanges.transaction_id'); 
                    })

                    ->leftJoin('positions as previous_positions', 'transaction_datachanges.position_id', '=', 'previous_positions.id')
                    ->leftJoin('departments', 'previous_positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'previous_positions.subunit_id', '=', 'subunits.id')

                    ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                    ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                    ->leftJoin('subunits as change_subunit', 'change_position.subunit_id', '=', 'change_subunit.id')
                    ->leftJoin('jobbands as change_jobband', 'change_position.jobband_id', '=', 'change_jobband.id')
                    ->where('da_evaluations.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'da-evaluation')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'monthly-evaluation') {
                $getmo = DB::table('transaction_approvers')
                    ->leftJoin('monthly_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'monthly_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'monthly_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'monthly_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->where('monthly_evaluations.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'monthly-evaluation')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmo->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmo->count(),
                        'requests' => ''
                    ];
                }
            }

            if ($form_type == 'employee-registration') {
                $getregister = DB::table('transaction_approvers')
                    ->leftJoin('employees', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'employees.id');
                        $join->on('transaction_approvers.form_type', '=', 'employees.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'employees.level'); 
                    })
                    ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees AS requestor', 'employees.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'employees.requestor_id', '=', 'requestor_employee_position.employee_id')
                    
                    // ->rightJoin('positions as request', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'request.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'request.subunit_id'); 
                    // })
                    ->leftJoin('positions as request', 'requestor_employee_position.position_id', '=', 'request.id')

                    ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->where('employees.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'employee-registration')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getregister->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getregister->count(),
                        'requests' => ''
                    ];
                }
            }

            if ($form_type == 'manpower-form') {
                $getmanpower = DB::table('transaction_approvers')
                    ->leftJoin('manpower_forms', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'manpower_forms.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'manpower_forms.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'manpower_forms.level'); 
                    })
                    ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'manpower_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->where('manpower_forms.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'manpower-form')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmanpower->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmanpower->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'merit-increase-form') {
                $getmerit = DB::table('forms')
                    ->leftJoin('merit_increase_forms', function($join) { 
                        $join->on('forms.form_type', '=', 'merit_increase_forms.form_type'); 
                        $join->on('forms.level', '=', 'merit_increase_forms.level'); 
                    })
                    ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'merit_increase_forms.employee_id', '=', 'employee_positions.employee_id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->where('merit_increase_forms.current_status', '=', 'for-approval')
                    ->where('forms.form_type', '=', 'merit-increase-form')
                    ->where('forms.employee_id', '=', $employee_id)
                    ->get();

                if ($getmerit->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmerit->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'employee-datachange') {
                $getdc = DB::table('transaction_approvers')
                    ->leftJoin('employee_datachanges', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'employee_datachanges.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'employee_datachanges.form_type'); 
                        $join->on('transaction_approvers.level', '=', 'employee_datachanges.level'); 
                    })
                    ->leftJoin('employees', 'employee_datachanges.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'employee_datachanges.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'employee_datachanges.requestor_id', '=', 'requestor_employee_position.employee_id')

                    ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
                    ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
                    ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
                    ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')

                    ->leftJoin('positions as new_positions', 'employee_datachanges.new_position_id', '=', 'new_positions.id')
                    ->leftJoin('departments as new_departments', 'employee_datachanges.new_department_id', '=', 'new_departments.id')
                    ->leftJoin('subunits as new_subunits', 'employee_datachanges.new_subunit_id', '=', 'new_subunits.id')
                    ->leftJoin('locations as new_locations', 'employee_datachanges.new_location_id', '=', 'new_locations.id')
                    ->leftJoin('companies as new_companies', 'employee_datachanges.new_company_id', '=', 'new_companies.id')
                    ->leftJoin('divisions as new_divisions', 'employee_datachanges.new_division_id', '=', 'new_divisions.id')
                    ->leftJoin('division_categories as new_division_categories', 'employee_datachanges.new_division_cat_id', '=', 'new_division_categories.id')
                    
                    // ->rightJoin('positions as right_join_position', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'right_join_position.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'right_join_position.subunit_id'); 
                    // })

                    ->leftJoin('positions as right_join_position', 'requestor_employee_position.position_id', '=', 'right_join_position.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->where('employee_datachanges.current_status', '=', 'for-approval')
                    ->where('transaction_approvers.form_type', '=', 'employee-datachange')
                    ->where('transaction_approvers.employee_id', '=', $employee_id)
                    ->get();

                    if ($getdc->count()) {
                        $forms[] = [
                            'form_type' => $form_type,
                            'count' => $getdc->count(),
                            'requests' => '',
                        ];
                    }
            }
        }

        return response()->json($forms);
    }

    public function getFormFilingCount() { 
        $employee_id = Auth::user()->employee_id;

        $formtypes = DB::table('transaction_receivers')
            ->select([
                'transaction_receivers.form_type',
                'transaction_receivers.label'
            ])
            ->where('transaction_receivers.employee_id', '=', $employee_id)
            ->groupBy('transaction_receivers.form_type')
            ->get();

        $forms = [];

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;

            if ($form_type == 'probi-evaluation') { 
                $getprobi = DB::table('receivers')
                    ->leftJoin('forms', function($join) { 
                        $join->on('receivers.form_type', '=', 'forms.form_type'); 
                        $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
                        $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
                    })
                    ->leftJoin('probi_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
                        $join->on('forms.level', '<', 'probi_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type'
                    ])
                    ->where('probi_evaluations.is_fulfilled', '!=', 'FILED')
                    ->where('receivers.form_type', '=', 'probi-evaluation')
                    ->where('receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getprobi->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getprobi->count(),
                        'requests' => '',
                    ];
                } 
            }

            if ($form_type == 'annual-evaluation') { 
                $getannual = DB::table('receivers')
                    ->leftJoin('forms', function($join) { 
                        $join->on('receivers.form_type', '=', 'forms.form_type'); 
                        $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
                        $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
                    })
                    ->leftJoin('annual_evaluations', function($join) { 
                        $join->on('forms.form_type', '=', 'annual_evaluations.form_type'); 
                        $join->on('forms.level', '<', 'annual_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type'
                    ])
                    ->where('annual_evaluations.is_fulfilled', '!=', 'FILED')
                    ->where('receivers.form_type', '=', 'annual-evaluation')
                    ->where('receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getannual->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getannual->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'monthly-evaluation') { 
                $getmo = DB::table('transaction_receivers')
                    ->leftJoin('transaction_approvers', function($join) { 
                        $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
                        $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
                        $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                        $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
                    })
                    ->leftJoin('monthly_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'monthly_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'monthly_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '<', 'monthly_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->where('monthly_evaluations.is_fulfilled', '!=', 'FILED')
                    ->where('transaction_receivers.form_type', '=', 'monthly-evaluation')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmo->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmo->count(),
                        'requests' => '',
                    ];
                } 
            }

            if ($form_type == 'manpower-form') { 
                $getmanpower = DB::table('transaction_receivers')
                    ->leftJoin('transaction_approvers', function($join) { 
                        $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
                        $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
                        $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                        $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
                    })
                    ->leftJoin('manpower_forms', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'manpower_forms.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'manpower_forms.form_type'); 
                        $join->on('transaction_approvers.level', '<', 'manpower_forms.level'); 
                    })
                    ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'manpower_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->select([
                        'transaction_approvers.form_type'
                    ])
                    ->where('manpower_forms.is_fulfilled', '!=', 'FILED')
                    ->where('transaction_receivers.form_type', '=', 'manpower-form')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmanpower->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmanpower->count(),
                        'requests' => ''
                    ];
                }
            }

            if ($form_type == 'da-evaluation') { 
                $getda = DB::table('transaction_receivers')
                    ->leftJoin('transaction_approvers', function($join) { 
                        $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
                        $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
                        $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                        $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
                    })
                    ->leftJoin('da_evaluations', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'da_evaluations.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'da_evaluations.form_type'); 
                        $join->on('transaction_approvers.level', '<', 'da_evaluations.level'); 
                    })
                    ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'da_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'da_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'requestor_employee_position.position_id', '=', 'positions.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->leftJoin('da_forms', 'employees.id', '=', 'da_forms.employee_id')
                    ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                    ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                    ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                    ->select([
                        'transaction_approvers.form_type'
                    ])
                    ->where('da_evaluations.is_fulfilled', '!=', 'FILED')
                    ->where('transaction_receivers.form_type', '=', 'da-evaluation')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'merit-increase-form') { 
                $getmerit = DB::table('receivers')
                    ->leftJoin('forms', function($join) { 
                        $join->on('receivers.form_type', '=', 'forms.form_type'); 
                        $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
                        $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
                    })
                    ->leftJoin('merit_increase_forms', function($join) { 
                        $join->on('forms.form_type', '=', 'merit_increase_forms.form_type'); 
                        $join->on('forms.level', '<', 'merit_increase_forms.level'); 
                    })
                    ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'merit_increase_forms.employee_id', '=', 'employee_positions.employee_id')
                    ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                    ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
                    ->rightJoin('positions', function($join) { 
                        $join->on('employee_positions.position_id', '=', 'positions.id'); 
                        $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    })
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'forms.form_type'
                    ])
                    ->where('merit_increase_forms.is_fulfilled', '!=', 'FILED')
                    ->where('receivers.form_type', '=', 'merit-increase-form')
                    ->where('receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getmerit->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmerit->count(),
                        'requests' => '',
                    ];
                }
            }

            if ($form_type == 'employee-datachange') {
                $getdc = DB::table('transaction_receivers')
                    ->leftJoin('transaction_approvers', function($join) { 
                        $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
                        $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
                        $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                        $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
                    })
                    ->leftJoin('employee_datachanges', function($join) { 
                        $join->on('transaction_approvers.transaction_id', '=', 'employee_datachanges.id'); 
                        $join->on('transaction_approvers.form_type', '=', 'employee_datachanges.form_type'); 
                        $join->on('transaction_approvers.level', '<', 'employee_datachanges.level'); 
                    })
                    ->leftJoin('employees', 'employee_datachanges.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'employee_datachanges.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'employee_datachanges.requestor_id', '=', 'requestor_employee_position.employee_id')

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
                    
                    // ->rightJoin('positions as right_join_position', function($join) { 
                    //     $join->on('requestor_employee_position.position_id', '=', 'right_join_position.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'right_join_position.subunit_id'); 
                    // })

                    ->leftJoin('positions as right_join_position', 'requestor_employee_position.position_id', '=', 'right_join_position.id')

                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->where('employee_datachanges.is_fulfilled', '!=', 'FILED')
                    ->where('transaction_receivers.form_type', '=', 'employee-datachange')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->get();

                if ($getdc->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getdc->count(),
                        'requests' => '',
                    ];
                }
            }
        }

        return response()->json($forms);
    }

    public function getFormConfirmationCount() {
        $employee_id = Auth::user()->employee_id;

        // $formtypes = DB::table('forms')
        //     ->select([
        //         'forms.form_type',
        //         'forms.label'
        //     ])
        //     ->where('forms.employee_id', '=', $employee_id)
        //     ->groupBy('forms.form_type')
        //     ->get();

        $formtypes = collect([
            (object) [
                'form_type' => 'probi-evaluation',
                'label' => 'PROBI EVALUATION'
            ],
            (object) [
                'form_type' => 'monthly-evaluation',
                'label' => 'MONTHLY EVALUATION'
            ],
            (object) [
                'form_type' => 'da-evaluation',
                'label' => 'DA EVALUATION'
            ],
        ]);

        $forms = [];

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;
            $label = $value->label;

            if ($form_type == 'monthly-evaluation') {
                $getmo = DB::table('monthly_evaluations')
                    // ->leftJoin('monthly_evaluations', function($join) { 
                    //     $join->on('forms.form_type', '=', 'monthly_evaluations.form_type'); 
                    //     $join->on('forms.level', '=', 'monthly_evaluations.level'); 
                    // })
                    ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'monthly_evaluations.id',
                    ])
                    ->where('monthly_evaluations.is_confirmed', '!=', 'CONFIRMED')
                    // ->where('forms.form_type', '=', 'monthly-evaluation')
                    // ->where('forms.employee_id', '=', $employee_id)
                    ->where('monthly_evaluations.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getmo->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmo->count(),
                        'requests' => ResourcesMonthlyEvaluationDetail::collection($getmo)
                    ];
                }
            }

            if ($form_type == 'probi-evaluation') {
                $getprobi = DB::table('probi_evaluations')
                    // ->leftJoin('probi_evaluations', function($join) { 
                    //     $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
                    //     $join->on('forms.level', '=', 'probi_evaluations.level'); 
                    // })
                    ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->select([
                        'probi_evaluations.id',
                    ])
                    ->where('probi_evaluations.is_confirmed', '!=', 'CONFIRMED')
                    ->where('probi_evaluations.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getprobi->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getprobi->count(),
                        'requests' => ResourcesProbiEvaluationDetail::collection($getprobi),
                    ];
                }
            }

            if ($form_type == 'da-evaluation') {
                $getda = DB::table('da_forms')
                    ->leftJoin('employees', 'da_forms.employee_id', '=', 'employees.id')
                    ->leftJoin('da_evaluations', 'employees.id', '=', 'da_evaluations.employee_id')
                    ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                    ->leftJoin('employees as requestor', 'da_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'da_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'da_forms.employee_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
                    // })
                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('employee_statuses', function($join) { 
                        $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                    })
                    ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                    ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                    ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                    ->select([
                        'da_forms.id',
                    ])
                    ->where('da_forms.is_confirmed', '!=', 'CONFIRMED')
                    ->where('da_forms.employee_id', '=', $employee_id)
                    ->get()
                    ->map(function ($getmo) use ($form_type, $label) {
                        $getmo->form_type = $form_type;
                        $getmo->label = $label;
                        $getmo->action = 'confirm';

                        return $getmo;
                    });

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => ResourcesDaEvaluationDetail::collection($getda),
                    ];
                }
            }
        }

        return response()->json($forms);
    }

    public function getFormRejected(Request $request) {
        $employee_id = Auth::user()->employee_id;
        $rejected = [];
        $types = [
            'monthly-evaluation' => 'monthly_evaluations',
            'probi-evaluation' => 'probi_evaluations',
            'annual-evaluation' => 'annual_evaluations',
            'employee-registration' => 'employees',
            'manpower-form' => 'manpower_forms',
            'da-evaluation' => 'da_evaluations',
            'merit-increase-form' => 'merit_increase_forms',
            'employee-datachange' => 'employee_datachanges'
        ];

        foreach ($types as $key => $type) {
            $fetch = DB::table($type)
                ->where('requestor_id', '=', $employee_id)
                ->where('current_status', '=', 'rejected')
                ->get();
                
            if ($fetch->count()) {
                $rejected[] = [
                    'form_type' => $key,
                    'count' => $fetch->count(),
                    'requests' => '',
                ];
            }
        }

        return response()->json($rejected);
    }

    public function getHistory(Request $request) {
        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');

        $history = DB::table('form_history')
            ->leftJoin('employees as reviewer', 'form_history.reviewer_id', '=', 'reviewer.id')
            ->leftJoin('employee_positions', 'form_history.reviewer_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->select([
                'form_history.id',
                'form_history.form_id',
                'form_history.form_type',
                'form_history.form_data',
                'form_history.status',
                'form_history.status_mark',
                'form_history.reviewer_id',
                'form_history.review_date',
                'form_history.reviewer_action',
                'form_history.reviewer_attachment',
                'form_history.remarks',
                'form_history.level',
                'form_history.requestor_id',
                'form_history.employee_id',
                'form_history.is_fulfilled',
                'form_history.date_fulfilled',
                'form_history.description',
                'form_history.created_at',
                'reviewer.prefix_id',
                'reviewer.id_number',
                'reviewer.first_name',
                'reviewer.middle_name',
                'reviewer.last_name',
                'reviewer.suffix',
                'positions.position_name as reviewer_position',
                'departments.department_name as reviewer_department'
            ])
            ->where('form_history.form_id', '=', $form_id)
            ->where('form_history.form_type', '=', $form_type)
            ->orderBy('form_history.id', 'desc')
            ->get();

        return FormHistoryResources::collection($history);
    }

    public function getApproverHistory(Request $request) {
        $employee_id = Auth::user()->employee_id;
        $keyword = request('keyword');

        $query = DB::table('form_history')
            ->leftJoin('employees as reviewer', 'form_history.reviewer_id', '=', 'reviewer.id')
            ->leftJoin('employee_positions', 'form_history.reviewer_id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->select([
                'form_history.id',
                'form_history.code',
                'form_history.form_id',
                'form_history.form_type',
                'form_history.form_data',
                'form_history.status',
                'form_history.status_mark',
                'form_history.reviewer_id',
                'form_history.review_date',
                'form_history.reviewer_action',
                'form_history.reviewer_attachment',
                'form_history.remarks',
                'form_history.level',
                'form_history.requestor_id',
                'form_history.employee_id',
                'form_history.is_fulfilled',
                'form_history.date_fulfilled',
                'form_history.description',
                'form_history.created_at',
                'reviewer.prefix_id',
                'reviewer.id_number',
                'reviewer.first_name',
                'reviewer.middle_name',
                'reviewer.last_name',
                'reviewer.suffix',
                'positions.position_name as reviewer_position',
                'departments.department_name as reviewer_department'
            ])
            ->where('form_history.reviewer_id', '=', $employee_id);

        $history = $query->paginate(20);

        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';
            $history = $query->where('form_history.code', 'LIKE', $value)->paginate(20);
        }

        return ApproverHistoryDetailsResource::collection($history);
    }

    public function reviewForm(Request $request) 
    {
        date_default_timezone_set('Asia/Manila');

        $filenames = [];
        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');
        $remarks = $request->input('remarks');
        $review = $request->input('review');
        $review_mark = $request->input('review_mark');
        $reviewer_action = $request->input('reviewer_action');

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }
        }

        if ($form_type == 'annual-evaluation') {
            $annual_evaluation = AnnualEvaluation::findOrFail($form_id);
            $annual_evaluation->level = ($review == 'approved') ? $annual_evaluation->level + 1 : $annual_evaluation->level;
            $annual_evaluation->current_status = ($review != 'approved') ? $review : $annual_evaluation->current_status;
            $annual_evaluation->current_status_mark = ($review != 'approved') ? $review_mark : $annual_evaluation->current_status_mark;
            
            if ($annual_evaluation->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR ANNUAL EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($annual_evaluation->id, 'FORM REQUEST - ANNUAL EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->code = $annual_evaluation->code;
                $form_history->form_id = $annual_evaluation->id;
                $form_history->form_type = $annual_evaluation->form_type;
                $form_history->form_data = $annual_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $annual_evaluation->level;
                $form_history->requestor_id = $annual_evaluation->requestor_id;
                $form_history->employee_id = $annual_evaluation->employee_id;
                $form_history->is_fulfilled = $annual_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }
            
            return response()->json($annual_evaluation);
        }

        if ($form_type == 'probi-evaluation') {
            $probi_evaluation = ProbiEvaluation::findOrFail($form_id);
            $probi_evaluation->level = ($review == 'approved') ? $probi_evaluation->level + 1 : $probi_evaluation->level;
            $probi_evaluation->current_status = ($review != 'approved') ? $review : $probi_evaluation->current_status;
            $probi_evaluation->current_status_mark = ($review != 'approved') ? $review_mark : $probi_evaluation->current_status_mark;
            
            if ($probi_evaluation->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR PROBATIONARY EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($probi_evaluation->id, 'FORM REQUEST - PROBATIONARY EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->code = $probi_evaluation->code;
                $form_history->form_id = $probi_evaluation->id;
                $form_history->form_type = $probi_evaluation->form_type;
                $form_history->form_data = $probi_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = $reviewer_action;
                $form_history->remarks = $remarks;
                $form_history->level = $probi_evaluation->level;
                $form_history->requestor_id = $probi_evaluation->requestor_id;
                $form_history->employee_id = $probi_evaluation->employee_id;
                $form_history->is_fulfilled = $probi_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }

            return response()->json($probi_evaluation);
        }

        if ($form_type == 'employee-registration') {
            $employee = Employee::findOrFail($form_id);
            $employee->level = ($review == 'approved') ? $employee->level + 1 : $employee->level;
            $employee->current_status = $review;
            $employee->current_status_mark = $review_mark;

            if ($employee->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR EMPLOYEE REGISTRATION - STATUS: '.$review_mark;
                Helpers::LogActivity($employee->id, 'FORM REQUEST - EMPLOYEE REGISTRATION', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $employee->id;
                $form_history->code = $employee->code;
                $form_history->form_type = $employee->form_type;
                $form_history->form_data = $employee->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = $reviewer_action;
                $form_history->remarks = $remarks;
                $form_history->level = $employee->level;
                $form_history->requestor_id = $employee->requestor_id;
                $form_history->employee_id = $employee->id;
                $form_history->is_fulfilled = ($review == 'approved') ? 'FILED' : 'PENDING';
                $form_history->date_fulfilled = ($review == 'approved') ? Carbon::now() : '';
                $form_history->description = 'REVIEW FOR REGISTRATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                // activate account 
                $user = User::where('employee_id', $employee->id)->first();
                if ($user) {
                    $user->status = ($review == 'approved') ? 'active' : 'inactive';
                    $user->status_description = ($review == 'approved') ? 'ACTIVE' : 'DEACTIVATED';
                    $user->save();
                }

                if ($review == 'approved') {
                    $reference_id = $employee->code;
                    $record_id = $employee->id;
                    $record_type = $employee->form_type;
                    $employee_id = $employee->id;

                    $data = DB::table('employees')
                        ->leftJoin('employee_statuses', function($join) { 
                            $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                        })
                        // ->where('employee_statuses.employment_type', '=', 'probationary')
                        ->where('employees.id', $employee->id)
                        ->first();

                    $details = 'REGISTRATION APPROVED | EMPLOYEMENT TYPE: '.$data->employment_type_label.' | DATE HIRED: '.$data->hired_date;

                    Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);

                    if ($data->employment_type == 'probationary') {
                        $record_id = $data->id;
                        $record_type = 'employment-type';
                        $details = 'UNDER PROBATIONARY EVALUATION | DATE START: '.$data->employment_date_start.' | DATE END: '.$data->employment_date_end;
                        Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);

                        // status info
                        $employee_state = new EmployeeState();
                        $employee_state->employee_id = $employee->id;
                        $employee_state->employee_state_label = 'UNDER EVALUATION (PROBATIONARY)';
                        $employee_state->employee_state = 'under_evaluation';
                        $employee_state->state_date_start = $data->employment_date_start;
                        $employee_state->state_date_end = $data->employment_date_end;
                        $employee_state->state_date = $data->employment_date_start;
                        $employee_state->save();
                    }
                }
            }

            return response()->json($employee);
        }

        if ($form_type == 'monthly-evaluation') {
            $monthly_evaluation = MonthlyEvaluation::findOrFail($form_id);
            $monthly_evaluation->level = ($review == 'approved') ? $monthly_evaluation->level + 1 : 1;
            $monthly_evaluation->current_status = ($review != 'approved') ? $review : $monthly_evaluation->current_status;
            $monthly_evaluation->current_status_mark = ($review != 'approved') ? $review_mark : $monthly_evaluation->current_status_mark;
            
            if ($monthly_evaluation->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR MONTHLY EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($monthly_evaluation->id, 'FORM REQUEST - MONTHLY EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $monthly_evaluation->id;
                $form_history->code = $monthly_evaluation->code;
                $form_history->form_type = $monthly_evaluation->form_type;
                $form_history->form_data = $monthly_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $monthly_evaluation->level;
                $form_history->requestor_id = $monthly_evaluation->requestor_id;
                $form_history->employee_id = $monthly_evaluation->employee_id;
                $form_history->is_fulfilled = $monthly_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $monthly_evaluation->id;
                $transaction_statuses->code = $monthly_evaluation->code;
                $transaction_statuses->form_type = $monthly_evaluation->form_type;
                $transaction_statuses->form_data = $monthly_evaluation->toJson();
                $transaction_statuses->status = $review;
                $transaction_statuses->status_mark = $review_mark;
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $monthly_evaluation->level;
                $transaction_statuses->requestor_id = $monthly_evaluation->requestor_id;
                $transaction_statuses->employee_id = $monthly_evaluation->employee_id;
                $transaction_statuses->is_fulfilled = $monthly_evaluation->is_fulfilled;
                $transaction_statuses->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($monthly_evaluation);
        }

        if ($form_type == 'manpower-form') {
            $manpower = ManpowerForms::findOrFail($form_id);
            $manpower->level = ($review == 'approved') ? $manpower->level + 1 : 1;
            $manpower->current_status = ($review != 'approved') ? $review : $manpower->current_status;
            $manpower->current_status_mark = ($review != 'approved') ? $review_mark : $manpower->current_status_mark;

            if ($manpower->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR MANPOWER FORM - STATUS: '.$review_mark;
                Helpers::LogActivity($manpower->id, 'FORM REQUEST - MANPOWER FORM', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $manpower->id;
                $form_history->code = $manpower->code;
                $form_history->form_type = $manpower->form_type;
                $form_history->form_data = $manpower->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $manpower->level;
                $form_history->requestor_id = $manpower->requestor_id;
                $form_history->employee_id = $manpower->employee_id;
                $form_history->is_fulfilled = $manpower->is_fulfilled;
                $form_history->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $manpower->id;
                $transaction_statuses->code = $manpower->code;
                $transaction_statuses->form_type = $manpower->form_type;
                $transaction_statuses->form_data = $manpower->toJson();
                $transaction_statuses->status = $review;
                $transaction_statuses->status_mark = $review_mark;
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $manpower->level;
                $transaction_statuses->requestor_id = $manpower->requestor_id;
                $transaction_statuses->employee_id = $manpower->employee_id;
                $transaction_statuses->is_fulfilled = $manpower->is_fulfilled;
                $transaction_statuses->description = 'REVIEW FOR MANPOWER REQUEST - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();

                $datachange = DatachangeForms::where('manpower_id', '=', $manpower->id)->first();
                
                if ($datachange) {
                    $datachange->level = ($review == 'approved') ? $datachange->level + 1 : 1;
                    $datachange->current_status = $review;
                    $datachange->current_status_mark = $review_mark;
                    $datachange->save();
                    
                    $da = DaForms::where('datachange_id', '=', $datachange->id)->first();
                    
                    if ($da) {
                        $da->level = ($review == 'approved') ? $da->level + 1 : $da->level;
                        $da->current_status = $review;
                        $da->current_status_mark = $review_mark;
                        $da->save();
                    }
                }
            }

            return response()->json($manpower);
        }

        if ($form_type == 'da-evaluation') {
            $da_evaluation = DaEvaluation::findOrFail($form_id);
            $da_evaluation->level = ($review == 'approved') ? $da_evaluation->level + 1 : 1;
            $da_evaluation->current_status = ($review != 'approved') ? $review : $da_evaluation->current_status;
            $da_evaluation->current_status_mark = ($review != 'approved') ? $review_mark : $da_evaluation->current_status_mark;
            
            if ($da_evaluation->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR DA EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($da_evaluation->id, 'FORM REQUEST - DA EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $da_evaluation->id;
                $form_history->code = $da_evaluation->code;
                $form_history->form_type = $da_evaluation->form_type;
                $form_history->form_data = $da_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $da_evaluation->level;
                $form_history->requestor_id = $da_evaluation->requestor_id;
                $form_history->employee_id = $da_evaluation->employee_id;
                $form_history->is_fulfilled = $da_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $da_evaluation->id;
                $transaction_statuses->code = $da_evaluation->code;
                $transaction_statuses->form_type = $da_evaluation->form_type;
                $transaction_statuses->form_data = $da_evaluation->toJson();
                $transaction_statuses->status = $review;
                $transaction_statuses->status_mark = $review_mark;
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $da_evaluation->level;
                $transaction_statuses->requestor_id = $da_evaluation->requestor_id;
                $transaction_statuses->employee_id = $da_evaluation->employee_id;
                $transaction_statuses->is_fulfilled = $da_evaluation->is_fulfilled;
                $transaction_statuses->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($da_evaluation);
        }

        if ($form_type == 'employee-datachange') {
            $employee_datachange = EmployeeDataChange::findOrFail($form_id);
            $employee_datachange->level = ($review == 'approved') ? $employee_datachange->level + 1 : 1;
            $employee_datachange->current_status = ($review != 'approved') ? $review : $employee_datachange->current_status;
            $employee_datachange->current_status_mark = ($review != 'approved') ? $review_mark : $employee_datachange->current_status_mark;
            
            if ($employee_datachange->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR DATACHANGE - STATUS: '.$review_mark;
                Helpers::LogActivity($employee_datachange->id, 'FORM REQUEST - DATACHANGE', $activity);

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
                $form_history->description = 'REVIEW FOR DATACHANGE - '.$review_mark;
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
                $transaction_statuses->description = 'REVIEW FOR DATACHANGE - '.$review_mark;
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($employee_datachange);
        }

        if ($form_type == 'merit-increase-form') {
            $merit_increase = MeritIncreaseForm::findOrFail($form_id);
            $merit_increase->level = ($review == 'approved') ? $merit_increase->level + 1 : $merit_increase->level;
            $merit_increase->current_status = ($review != 'approved') ? $review : $merit_increase->current_status;
            $merit_increase->current_status_mark = ($review != 'approved') ? $review_mark : $merit_increase->current_status_mark;
            
            if ($merit_increase->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR DATA CHANGE FORM (MERIT INCREASE) - STATUS: '.$review_mark;
                Helpers::LogActivity($merit_increase->id, 'FORM REQUEST - DATA CHANGE FORM (MERIT INCREASE)', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $merit_increase->id;
                $form_history->code = $merit_increase->code;
                $form_history->form_type = $merit_increase->form_type;
                $form_history->form_data = $merit_increase->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $merit_increase->level;
                $form_history->requestor_id = $merit_increase->requestor_id;
                $form_history->employee_id = $merit_increase->employee_id;
                $form_history->is_fulfilled = $merit_increase->is_fulfilled;
                $form_history->description = 'REVIEW FOR DATA CHANGE REQUEST - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }

            return response()->json($merit_increase);
        }
    }

    public function confirmForm(Request $request) 
    {
        date_default_timezone_set('Asia/Manila');

        $filenames = [];
        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');
        $remarks = $request->input('remarks');
        $review = $request->input('review');
        $review_mark = $request->input('review_mark');
        $reviewer_action = $request->input('reviewer_action');

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/reviewer_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }
        }

        if ($form_type == 'annual-evaluation') {
            $annual_evaluation = AnnualEvaluation::findOrFail($form_id);
            $annual_evaluation->level = ($review == 'approved') ? $annual_evaluation->level + 1 : $annual_evaluation->level;
            $annual_evaluation->current_status = ($review != 'approved') ? $review : $annual_evaluation->current_status;
            $annual_evaluation->current_status_mark = ($review != 'approved') ? $review_mark : $annual_evaluation->current_status_mark;
            
            if ($annual_evaluation->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR ANNUAL EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($annual_evaluation->id, 'FORM REQUEST - ANNUAL EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->code = $annual_evaluation->code;
                $form_history->form_id = $annual_evaluation->id;
                $form_history->form_type = $annual_evaluation->form_type;
                $form_history->form_data = $annual_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $annual_evaluation->level;
                $form_history->requestor_id = $annual_evaluation->requestor_id;
                $form_history->employee_id = $annual_evaluation->employee_id;
                $form_history->is_fulfilled = $annual_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }
            
            return response()->json($annual_evaluation);
        }

        if ($form_type == 'probi-evaluation') {
            $probi_evaluation = ProbiEvaluation::findOrFail($form_id);
            $probi_evaluation->is_confirmed = ($review == 'confirmed') ? $review_mark : ''; 
            
            if ($probi_evaluation->save()) {
                $activity = 'CONFIRMED FORM REQUEST FOR PROBATIONARY EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($probi_evaluation->id, 'FORM REQUEST - PROBATIONARY EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->code = $probi_evaluation->code;
                $form_history->form_id = $probi_evaluation->id;
                $form_history->form_type = $probi_evaluation->form_type;
                $form_history->form_data = $probi_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = $reviewer_action;
                $form_history->remarks = $remarks;
                $form_history->level = $probi_evaluation->level;
                $form_history->requestor_id = $probi_evaluation->requestor_id;
                $form_history->employee_id = $probi_evaluation->employee_id;
                $form_history->is_fulfilled = $probi_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }

            return response()->json($probi_evaluation);
        }

        if ($form_type == 'monthly-evaluation') {
            $monthly_evaluation = MonthlyEvaluation::findOrFail($form_id);
            $monthly_evaluation->is_confirmed = ($review == 'confirmed') ? $review_mark : ''; 
            
            if ($monthly_evaluation->save()) {
                $activity = 'CONFIRMED FORM REQUEST FOR MONTHLY EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($monthly_evaluation->id, 'FORM REQUEST - MONTHLY EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->code = $monthly_evaluation->code;
                $form_history->form_id = $monthly_evaluation->id;
                $form_history->form_type = $monthly_evaluation->form_type;
                $form_history->form_data = $monthly_evaluation->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $monthly_evaluation->level;
                $form_history->requestor_id = $monthly_evaluation->requestor_id;
                $form_history->employee_id = $monthly_evaluation->employee_id;
                $form_history->is_fulfilled = $monthly_evaluation->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }

            return response()->json($monthly_evaluation);
        }

        if ($form_type == 'da-evaluation') {
            $da_form = DaForms::findOrFail($form_id);
            $da_form->is_confirmed = ($review == 'confirmed') ? $review_mark : ''; 
            
            if ($da_form->save()) {
                $activity = 'REVIEWED FORM REQUEST FOR DA EVALUATION - STATUS: '.$review_mark;
                Helpers::LogActivity($da_form->id, 'FORM REQUEST - DA EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $da_form->id;
                $form_history->code = $da_form->code;
                $form_history->form_type = $da_form->form_type;
                $form_history->form_data = $da_form->toJson();
                $form_history->status = $review;
                $form_history->status_mark = $review_mark;
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->remarks = $remarks;
                $form_history->level = $da_form->level;
                $form_history->requestor_id = $da_form->requestor_id;
                $form_history->employee_id = $da_form->employee_id;
                $form_history->is_fulfilled = $da_form->is_fulfilled;
                $form_history->description = 'REVIEW FOR EVALUATION - '.$review_mark;
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();
            }

            return response()->json($da_form);
        }
    }

    public function getRegistrationRequests($level)
    {
        $requests = DB::table('employees')
            ->leftJoin('employees AS req', 'employees.requestor_id', '=', 'req.id')
            ->select([
                'employees.id',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.birthdate',
                'employees.age',
                'employees.religion',
                'employees.civil_status',
                'employees.gender',
                'employees.referrer_id',
                'employees.image',
                'employees.form_type',
                'employees.level',
                'employees.current_status',
                'employees.current_status_mark',
                'employees.requestor_id',
                'employees.remarks',
                'req.prefix_id AS r_prefix_id',
                'req.id_number AS r_id_number',
                'req.first_name AS r_first_name',
                'req.middle_name AS r_middle_name',
                'req.last_name AS r_last_name',
                'req.suffix AS r_suffix',
            ])
            ->where('employees.form_type', '=', 'employee-registration')
            ->where('employees.level', '=', $level)
            ->where('employees.current_status', '=', 'for-approval')
            ->get();

        return response()->json([
            'form_type' => 'employee-registration', 
            'requests' => $requests
        ]);
    }

    public function getManpowerRequests($level)
    {
        return response()->json([
            'form_type' => 'manpower-request', 
            'requests' => 'manpower request - '.$level
        ]);
    }

    public function approveRegistration(Request $request)
    {
        $employee = Employee::findOrFail($request->input('employee_id'));
        $employee->level = $employee->level + 1;
        $employee->current_status = 'approved';
        $employee->current_status_mark = $request->input('approved_mark');

        if ($employee->save()) {
            Helpers::LogActivity($employee->id, 'FORM REQUEST - EMPLOYEE REGISTRATION', 'APPROVED EMPLOYEE REGISTRATION');
            return response()->json($employee);
        }
    }

    public function rejectRegistration(Request $request) {
        $employee = Employee::findOrFail($request->input('employee_id'));
        $employee->current_status = 'rejected';
        $employee->current_status_mark = $request->input('rejected_mark');

        if ($employee->save()) {
            Helpers::LogActivity($employee->id, 'FORM REQUEST - EMPLOYEE REGISTRATION', 'REJECTED EMPLOYEE REGISTRATION');
            return response()->json($employee);
        }
    }

    public function getFormApprovers(Request $request) {
        $form_id = $request->form_id;
        $types = [
            'monthly-evaluation' => 'monthly_evaluations',
            'probi-evaluation' => 'probi_evaluations',
            'annual-evaluation' => 'annual_evaluations',
            'employee-registration' => 'employees',
            'manpower-form' => 'manpower_forms',
            'da-evaluation' => 'da_evaluations',
            'merit-increase-form' => 'merit_increase_forms',
            'employee-datachange' => 'employee_datachanges'
        ];
        $table = $types[$request->form_type];

        $approver = DB::table($table)
            ->leftJoin('employees', $table.'.requestor_id', '=', 'employees.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('transaction_approvers', function($join) use ($table) { 
                $join->on('positions.subunit_id', '=', 'transaction_approvers.subunit_id'); 
                $join->on($table.'.form_type', '=', 'transaction_approvers.form_type'); 
                $join->on($table.'.id', '=', 'transaction_approvers.transaction_id'); 
            })
            ->leftJoin('employees as approvers', 'transaction_approvers.employee_id', '=', 'approvers.id')
            ->leftJoin('transaction_statuses', function($join) use ($table) { 
                $join->on('transaction_approvers.form_type', '=', 'transaction_statuses.form_type'); 
                $join->on($table.'.id', '=', 'transaction_statuses.form_id'); 
                $join->on('approvers.id', '=', 'transaction_statuses.reviewer_id')->groupBy('transaction_statuses.reviewer_id'); 
            })
            ->leftJoin('employees as receivers', 'transaction_approvers.receiver_id', '=', 'receivers.id')
            ->select([
                'transaction_statuses.form_id',
                'approvers.first_name as approver_first_name',
                'approvers.last_name as approver_last_name',
                'transaction_approvers.level',
                'transaction_approvers.employee_id',
                'receivers.first_name as receiver_first_name',
                'receivers.last_name as receiver_last_name',
                'transaction_statuses.status_mark',
                'transaction_statuses.status',
                $table.'.is_fulfilled'
            ])
            ->where($table.'.id', '=', $form_id)
            ->get()
            ->map(function ($approver) {
                $approver->full_name = $approver->approver_first_name.' '.$approver->approver_last_name;
                $approver->receiver_full_name = $approver->receiver_first_name.' '.$approver->receiver_last_name;
                $approver->status_mark = ($approver->status_mark) ? $approver->status_mark : 'PENDING';
                $approver->is_fulfilled = ($approver->is_fulfilled) ? $approver->is_fulfilled : 'PENDING';
                return $approver;
            });

            $approvers = [];

            foreach ($approver as $value) {
                $approvers[$value->employee_id] = $value;
            }

        return response()->json($approvers);
    }

    public function getApproverForms(Request $request) {
        $approver_id = Auth::user()->employee_id;
        // $approver_id = $request->employee_id;

        $forms = DB::table('forms')
            ->leftJoin('subunits', 'forms.subunit_id', '=', 'subunits.id')
            ->leftJoin('employees', 'forms.receiver_id', '=', 'employees.id')
            ->select([
                'forms.form_type',
                'forms.label',
                'forms.level',
                'forms.subunit_id',
                'forms.receiver_id',
                'subunits.subunit_name',
                'employees.first_name',
                'employees.last_name',
            ])
            ->where('forms.employee_id', '=', $approver_id)
            ->get()
            ->map(function ($forms) {
                $forms->receiver_name = $forms->first_name.' '.$forms->last_name;
                return $forms;
            })
            ->mapToGroups(function ($forms, $key) {
                return [$forms->form_type => $forms];
            });

        foreach ($forms as $key => $value) {
            if ($key == 'monthly-evaluation') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('monthly_evaluations')
                        ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['monthly_evaluations.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('monthly_evaluations.level', '=', $v->level)
                        ->where('monthly_evaluations.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'probi-evaluation') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('probi_evaluations')
                        ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['probi_evaluations.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('probi_evaluations.level', '=', $v->level)
                        ->where('probi_evaluations.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'annual-evaluation') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('annual_evaluations')
                        ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['annual_evaluations.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('annual_evaluations.level', '=', $v->level)
                        ->where('annual_evaluations.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'manpower-form') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('manpower_forms')
                        ->leftJoin('employees', 'manpower_forms.requestor_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['manpower_forms.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('manpower_forms.level', '=', $v->level)
                        ->where('manpower_forms.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'da-evaluation') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('da_evaluations')
                        ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['da_evaluations.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('da_evaluations.level', '=', $v->level)
                        ->where('da_evaluations.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'merit-increase-form') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('merit_increase_forms')
                        ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['merit_increase_forms.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('merit_increase_forms.level', '=', $v->level)
                        ->where('merit_increase_forms.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }

            if ($key == 'employee-registration') {
                foreach ($value as $i => $v) {
                    $fetch = DB::table('employees')
                        ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                        ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                        ->select(['employees.id'])
                        ->where('positions.subunit_id', '=', $v->subunit_id)
                        ->where('employees.level', '=', $v->level)
                        ->where('employees.current_status', '=', 'for-approval')
                        ->get()
                        ->count();

                    $v->for_approval = $fetch;
                }
            }
        }

        return response()->json($forms);
    }

    public function getApproverFormHistory(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');

        if ($form_type == 'merit-increase-form') {
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
                ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
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
                    'requestor_positions.position_name as requestor_position',
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

            $merit_increase = $query->where('merit_increase_forms.id', '=', $form_id)->get();
            
            return MeritIncreaseHistoryDetailsResource::collection($merit_increase);
        }

        if ($form_type == 'monthly-evaluation') {
            $getmo = DB::table('monthly_evaluations')
                ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
                ->rightJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
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
                    'requestor_positions.position_name as requestor_position',
                    'departments.department_name',
                    'subunits.subunit_name'
                ])
                ->where('monthly_evaluations.id', '=', $form_id)
                ->get();

                return MonthlyEvaluationHistoryDetailsResource::collection($getmo);
        }

        if ($form_type == 'probi-evaluation') {
            $getprobi = DB::table('probi_evaluations')
                ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
                ->rightJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
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
                    'requestor_positions.position_name as requestor_position',
                    'departments.department_name',
                    'subunits.subunit_name'
                ])
                ->where('probi_evaluations.id', '=', $form_id)
                ->get();

            return ProbiEvaluationHistoryDetailsResource::collection($getprobi);
        }

        if ($form_type == 'annual-evaluation') {
            $getannual = DB::table('annual_evaluations')
                ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
                ->rightJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
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
                    'requestor_positions.position_name as requestor_position',
                    'departments.department_name',
                    'subunits.subunit_name'
                ])
                ->where('annual_evaluations.id', '=', $form_id)
                ->get();

            return AnnualEvaluationHistoryDetails::collection($getannual);
        }

        if ($form_type == 'da-evaluation') {
            $getda = DB::table('da_evaluations')
                ->leftJoin('employees', 'da_evaluations.employee_id', '=', 'employees.id')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'da_evaluations.requestor_id', '=', 'requestor.id')

                // ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')

                ->leftJoin('da_forms', 'da_evaluations.daform_id', '=', 'da_forms.id')
                ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                ->leftJoin('manpower_forms', 'datachange_forms.manpower_id', '=', 'manpower_forms.id')

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
                    'manpower_forms.code as manpower_code',
                    'manpower_forms.id as manpower_id',
                ])
                ->where('da_evaluations.id', '=', $form_id)
                ->get();

            return DaEvaluationWithManpowerCode::collection($getda);
        }

        if ($form_type == 'employee-datachange') {
            $getdc = DB::table('employee_datachanges')
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
                ])
                ->where('employee_datachanges.id', '=', $form_id)
                ->get();

            return EmployeeDataChangeDetail::collection($getdc);
        }

        if ($form_type == 'da-form') {
            $getda = DB::table('da_forms')
                ->leftJoin('employees', 'da_forms.employee_id', '=', 'employees.id')
                ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees as requestor', 'da_forms.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as requestor_employee_position', 'da_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('employee_positions', 'da_forms.employee_id', '=', 'employee_positions.employee_id')
                ->rightJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employee_statuses', function($join) { 
                    $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
                })
                ->leftJoin('da_evaluations', 'employees.id', '=', 'da_evaluations.employee_id')
                ->leftJoin('datachange_forms', 'da_forms.datachange_id', '=', 'datachange_forms.id')
                ->leftJoin('positions as change_position', 'datachange_forms.change_position_id', '=', 'change_position.id')
                ->leftJoin('departments as change_department', 'change_position.department_id', '=', 'change_department.id')
                ->select([
                    'da_forms.id',
                    'da_forms.code',
                    'da_forms.employee_id',
                    'da_evaluations.daform_id',
                    'da_evaluations.measures',
                    'da_evaluations.total_grade',
                    'da_evaluations.attachment',
                    'da_evaluations.assessment',
                    'da_evaluations.assessment_mark',
                    'da_forms.form_type',
                    'da_evaluations.level',
                    'da_evaluations.current_status',
                    'da_evaluations.current_status_mark',
                    'da_forms.requestor_id',
                    'da_forms.requestor_remarks',
                    'da_evaluations.date_evaluated',
                    'da_forms.is_fulfilled',
                    'da_evaluations.date_fulfilled',
                    'da_forms.created_at',
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
                    'requestor_positions.position_name as requestor_position',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'da_forms.id as daform_id',
                    'da_forms.inclusive_date_start',
                    'da_forms.inclusive_date_end',
                    'datachange_forms.change_position_id',
                    'datachange_forms.change_reason',
                    'change_position.position_name as change_position_name',
                    'change_department.department_name as change_department_name',
                ])
                ->where('da_forms.id', '=', $form_id)
                ->get();

            return DaEvaluationHistoryDetailsResource::collection($getda);
        }

        if ($form_type == 'employee-registration') {
            $getregister = DB::table('employees')
                ->leftJoin('employees AS referrer', 'employees.referrer_id', '=', 'referrer.id')
                ->leftJoin('employees AS requestor', 'employees.requestor_id', '=', 'requestor.id')
                ->leftJoin('employee_positions as requestor_employee_position', 'employees.requestor_id', '=', 'requestor_employee_position.employee_id')
                ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
                ->rightJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
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
                    'positions.position_name',
                    'requestor_positions.position_name as requestor_position',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'jobbands.jobband_name',
                    'jobrates.job_level',
                    'jobrates.salary_structure',
                    'jobrates.jobrate_name'
                ])
                ->where('employees.id', '=', $form_id)
                ->get();

            return response()->json($getregister);
        }
    }

    public function getListOfApprovers(Request $request) {
        $isLoggedIn = Auth::check();
        $isApprover = false;
        $approvers = [];
        $result = [];
        $form_type = $request->input('form_type');

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

            $approvers = DB::table('forms')
                ->leftJoin('employees', 'forms.employee_id', '=', 'employees.id')
                ->select([
                    'forms.form_type',
                    'forms.batch',
                    'forms.label',
                    'forms.subunit_id',
                    'forms.employee_id',
                    'forms.action',
                    'forms.level',
                    'forms.receiver_id',
                    'employees.first_name as approver_first_name',
                    'employees.middle_name as approver_middle_name',
                    'employees.last_name as approver_last_name',
                ])
                ->where('forms.form_type', '=', $form_type)
                ->where('forms.subunit_id', '=', $data->subunit_id)
                ->orderBy('forms.level', 'asc')
                ->get()
                ->map(function ($approvers) {
                    $approvers->full_name = $approvers->approver_first_name.' '.$approvers->approver_middle_name.' '.$approvers->approver_last_name;
                    return $approvers;
                });

            $receivers = DB::table('receivers')
                ->leftJoin('employees', 'receivers.employee_id', '=', 'employees.id')
                ->select([
                    'receivers.form_type',
                    'receivers.batch',
                    'receivers.label',
                    'receivers.subunit_id',
                    'receivers.employee_id',
                    'employees.first_name as receiver_first_name',
                    'employees.middle_name as receiver_middle_name',
                    'employees.last_name as receiver_last_name',
                ])
                ->where('receivers.form_type', '=', $form_type)
                ->where('receivers.subunit_id', '=', $data->subunit_id)
                ->get()
                ->map(function ($receivers) {
                    $receivers->full_name = $receivers->receiver_first_name.' '.$receivers->receiver_middle_name.' '.$receivers->receiver_last_name;
                    return $receivers;
                });


            $result = [
                'approvers' => $approvers,
                'receivers' => $receivers
            ];
        }

        return response()->json($result);
    }
}
