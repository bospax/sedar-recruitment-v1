<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\ProbiEvaluationDetail as ResourcesProbiEvaluationDetail;
use App\Http\Resources\MonthlyEvaluationDetail as ResourcesMonthlyEvaluationDetail;
use App\Http\Resources\AnnualEvaluationDetail as ResourcesAnnualEvaluationDetail;
use App\Http\Resources\DaEvaluationDetailRevised;
use App\Http\Resources\EmployeeDataChangeRequestDetail;
use App\Http\Resources\ManpowerDetailWithApprover;
use App\Http\Resources\MeritIncreaseDetail as MeritIncreaseDetailResource;
use App\Models\DaEvaluation;
use App\Models\DaForms;
use App\Models\DatachangeForms;
use App\Models\EmployeeDataChange;
use App\Models\FormHistory;
use App\Models\ManpowerForms;
use App\Models\Position;
use App\Models\TransactionStatuses;
use Carbon\Carbon;

class FormUpdateController extends Controller
{
    public function getFormUpdates(Request $request) 
    {
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

        $target_date = Carbon::now();

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;

            // if ($form_type == 'probi-evaluation') { 
            //     $getprobi = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('probi_evaluations', function($join) { 
            //             $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
            //             $join->on('forms.level', '<', 'probi_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type',
            //             'forms.label',
            //             'forms.action',
            //             'probi_evaluations.id',
            //             'probi_evaluations.code',
            //             'probi_evaluations.employee_id',
            //             'probi_evaluations.measures',
            //             'probi_evaluations.total_grade',
            //             'probi_evaluations.attachment',
            //             'probi_evaluations.assessment',
            //             'probi_evaluations.assessment_mark',
            //             'probi_evaluations.form_type',
            //             'probi_evaluations.level',
            //             'probi_evaluations.current_status',
            //             'probi_evaluations.current_status_mark',
            //             'probi_evaluations.requestor_id',
            //             'probi_evaluations.requestor_remarks',
            //             'probi_evaluations.date_evaluated',
            //             'probi_evaluations.is_fulfilled',
            //             'probi_evaluations.date_fulfilled',
            //             'probi_evaluations.created_at',
            //             'employees.prefix_id',
            //             'employees.id_number',
            //             'employees.first_name',
            //             'employees.middle_name',
            //             'employees.last_name',
            //             'employees.suffix',
            //             'employees.gender',
            //             'employees.image',
            //             'employees.referrer_id',
            //             'referrer.prefix_id AS r_prefix_id',
            //             'referrer.id_number AS r_id_number',
            //             'referrer.first_name AS r_first_name',
            //             'referrer.middle_name AS r_middle_name',
            //             'referrer.last_name AS r_last_name',
            //             'referrer.suffix AS r_suffix',
            //             'requestor.prefix_id AS req_prefix_id',
            //             'requestor.id_number AS req_id_number',
            //             'requestor.first_name AS req_first_name',
            //             'requestor.middle_name AS req_middle_name',
            //             'requestor.last_name AS req_last_name',
            //             'requestor.suffix AS req_suffix',
            //             'employee_statuses.employment_type',
            //             'employee_statuses.employment_type_label',
            //             'employee_statuses.employment_date_start',
            //             'employee_statuses.employment_date_end',
            //             'employee_statuses.regularization_date',
            //             'employee_statuses.hired_date',
            //             'positions.id AS position_id',
            //             'positions.position_name',
            //             'requestor_positions.position_name as requestor_position',
            //             'departments.department_name',
            //             'subunits.subunit_name'
            //         ])
            //         ->where('probi_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'probi-evaluation')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getprobi->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getprobi->count(),
            //             'requests' => ResourcesProbiEvaluationDetail::collection($getprobi),
            //         ];
            //     } 
            // }

            // if ($form_type == 'annual-evaluation') { 
            //     $getannual = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('annual_evaluations', function($join) { 
            //             $join->on('forms.form_type', '=', 'annual_evaluations.form_type'); 
            //             $join->on('forms.level', '<', 'annual_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type',
            //             'forms.label',
            //             'forms.action',
            //             'annual_evaluations.id',
            //             'annual_evaluations.code',
            //             'annual_evaluations.employee_id',
            //             'annual_evaluations.measures',
            //             'annual_evaluations.total_grade',
            //             'annual_evaluations.performance_discussion',
            //             'annual_evaluations.attachment',
            //             'annual_evaluations.assessment',
            //             'annual_evaluations.assessment_mark',
            //             'annual_evaluations.form_type',
            //             'annual_evaluations.level',
            //             'annual_evaluations.current_status',
            //             'annual_evaluations.current_status_mark',
            //             'annual_evaluations.requestor_id',
            //             'annual_evaluations.requestor_remarks',
            //             'annual_evaluations.date_evaluated',
            //             'annual_evaluations.is_fulfilled',
            //             'annual_evaluations.date_fulfilled',
            //             'annual_evaluations.created_at',
            //             'employees.prefix_id',
            //             'employees.id_number',
            //             'employees.first_name',
            //             'employees.middle_name',
            //             'employees.last_name',
            //             'employees.suffix',
            //             'employees.gender',
            //             'employees.image',
            //             'employees.referrer_id',
            //             'referrer.prefix_id AS r_prefix_id',
            //             'referrer.id_number AS r_id_number',
            //             'referrer.first_name AS r_first_name',
            //             'referrer.middle_name AS r_middle_name',
            //             'referrer.last_name AS r_last_name',
            //             'referrer.suffix AS r_suffix',
            //             'requestor.prefix_id AS req_prefix_id',
            //             'requestor.id_number AS req_id_number',
            //             'requestor.first_name AS req_first_name',
            //             'requestor.middle_name AS req_middle_name',
            //             'requestor.last_name AS req_last_name',
            //             'requestor.suffix AS req_suffix',
            //             'employee_statuses.employment_type',
            //             'employee_statuses.employment_type_label',
            //             'employee_statuses.employment_date_start',
            //             'employee_statuses.employment_date_end',
            //             'employee_statuses.regularization_date',
            //             'employee_statuses.hired_date',
            //             'positions.id AS position_id',
            //             'positions.position_name',
            //             'requestor_positions.position_name as requestor_position',
            //             'departments.department_name',
            //             'subunits.subunit_name'
            //         ])
            //         ->where('annual_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'annual-evaluation')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getannual->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getannual->count(),
            //             'requests' => ResourcesAnnualEvaluationDetail::collection($getannual),
            //         ];
            //     } 
            // }

            // if ($form_type == 'monthly-evaluation') { 
            //     $getmo = DB::table('transaction_receivers')
            //         ->leftJoin('transaction_approvers', function($join) { 
            //             $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
            //             $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
            //             $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
            //             $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
            //         })
            //         ->leftJoin('monthly_evaluations', function($join) { 
            //             $join->on('transaction_approvers.transaction_id', '=', 'monthly_evaluations.id'); 
            //             $join->on('transaction_approvers.form_type', '=', 'monthly_evaluations.form_type'); 
            //             $join->on('transaction_approvers.level', '<', 'monthly_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'transaction_approvers.form_type',
            //             'transaction_approvers.label',
            //             'transaction_approvers.action',
            //             'monthly_evaluations.id',
            //             'monthly_evaluations.code',
            //             'monthly_evaluations.employee_id',
            //             'monthly_evaluations.month',
            //             'monthly_evaluations.measures',
            //             'monthly_evaluations.development_plan',
            //             'monthly_evaluations.development_area',
            //             'monthly_evaluations.developmental_plan',
            //             'monthly_evaluations.total_grade',
            //             'monthly_evaluations.total_target',
            //             'monthly_evaluations.attachment',
            //             'monthly_evaluations.assessment',
            //             'monthly_evaluations.form_type',
            //             'monthly_evaluations.level',
            //             'monthly_evaluations.current_status',
            //             'monthly_evaluations.current_status_mark',
            //             'monthly_evaluations.requestor_id',
            //             'monthly_evaluations.requestor_remarks',
            //             'monthly_evaluations.date_evaluated',
            //             'monthly_evaluations.is_fulfilled',
            //             'monthly_evaluations.date_fulfilled',
            //             'monthly_evaluations.created_at',
            //             'employees.prefix_id',
            //             'employees.id_number',
            //             'employees.first_name',
            //             'employees.middle_name',
            //             'employees.last_name',
            //             'employees.suffix',
            //             'employees.gender',
            //             'employees.image',
            //             'employees.referrer_id',
            //             'referrer.prefix_id AS r_prefix_id',
            //             'referrer.id_number AS r_id_number',
            //             'referrer.first_name AS r_first_name',
            //             'referrer.middle_name AS r_middle_name',
            //             'referrer.last_name AS r_last_name',
            //             'referrer.suffix AS r_suffix',
            //             'requestor.prefix_id AS req_prefix_id',
            //             'requestor.id_number AS req_id_number',
            //             'requestor.first_name AS req_first_name',
            //             'requestor.middle_name AS req_middle_name',
            //             'requestor.last_name AS req_last_name',
            //             'requestor.suffix AS req_suffix',
            //             'employee_statuses.employment_type',
            //             'employee_statuses.employment_type_label',
            //             'employee_statuses.employment_date_start',
            //             'employee_statuses.employment_date_end',
            //             'employee_statuses.regularization_date',
            //             'employee_statuses.hired_date',
            //             'positions.id AS position_id',
            //             'positions.position_name',
            //             'requestor_positions.position_name as requestor_position',
            //             'departments.department_name',
            //             'subunits.subunit_name'
            //         ])
            //         ->where('monthly_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('transaction_receivers.form_type', '=', 'monthly-evaluation')
            //         ->where('transaction_receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getmo->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getmo->count(),
            //             'requests' => ResourcesMonthlyEvaluationDetail::collection($getmo),
            //         ];
            //     } 
            // }

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
                    ->leftJoin('form_history', function($join) { 
                        $join->on('form_history.created_at', DB::raw('(SELECT MAX(form_history.created_at) FROM form_history WHERE form_history.form_id = manpower_forms.id AND form_history.form_type = manpower_forms.form_type)')); 
                    })
                    ->leftJoin('employees as approver', 'form_history.reviewer_id', '=', 'approver.id')
                    ->leftJoin('employees as requestor', 'manpower_forms.requestor_id', '=', 'requestor.id')
                    ->leftJoin('employee_positions as requestor_employee_position', 'manpower_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
                    ->leftJoin('employee_positions', 'manpower_forms.requestor_id', '=', 'employee_positions.employee_id')
                    // ->rightJoin('positions', function($join) { 
                    //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
                    //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
                    // })

                    ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    ->leftJoin('datachange_forms', 'manpower_forms.id', '=', 'datachange_forms.manpower_id')

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
                        'subunits.subunit_name',
                        'form_history.status_mark',
                        'form_history.review_date',
                        'approver.first_name as approver_first_name',
                        'approver.last_name as approver_last_name',
                        'manpower_forms.effectivity_date'
                    ])
                    ->where('manpower_forms.is_fulfilled', '=', 'FILED')
                    ->where('datachange_forms.for_da', '=', 'false')
                    ->where(function ($query) {
                        $query->where('manpower_forms.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('manpower_forms.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'manpower-form')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('manpower_forms.effectivity_date', '<=', $target_date)
                    ->get()
                    ->map(function ($approver) {
                        $approver->approver_full_name = $approver->approver_first_name.' '.$approver->approver_last_name;
                        return $approver;
                    });

                if ($getmanpower->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getmanpower->count(),
                        'requests' => ManpowerDetailWithApprover::collection($getmanpower)
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
                        'datachange_forms.change_reason',
                        'datachange_forms.change_position_id',
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
                    ->where('da_evaluations.is_fulfilled', '=', 'FILED')
                    ->where(function ($query) {
                        $query->where('da_evaluations.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('da_evaluations.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'da-evaluation')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('da_evaluations.effectivity_date', '<=', $target_date)
                    ->get();

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => DaEvaluationDetailRevised::collection($getda),
                    ];
                }
            }

            // if ($form_type == 'merit-increase-form') { 
            //     $getmerit = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('merit_increase_forms', function($join) { 
            //             $join->on('forms.form_type', '=', 'merit_increase_forms.form_type'); 
            //             $join->on('forms.level', '<', 'merit_increase_forms.level'); 
            //         })
            //         ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'merit_increase_forms.employee_id', '=', 'employee_positions.employee_id')
            //         ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            //         ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type',
            //             'forms.label',
            //             'forms.action',
            //             'merit_increase_forms.id',
            //             'merit_increase_forms.code',
            //             'merit_increase_forms.employee_id',
            //             'merit_increase_forms.attachment',
            //             'merit_increase_forms.form_type',
            //             'merit_increase_forms.level',
            //             'merit_increase_forms.current_status',
            //             'merit_increase_forms.current_status_mark',
            //             'merit_increase_forms.requestor_id',
            //             'merit_increase_forms.requestor_remarks',
            //             'merit_increase_forms.is_fulfilled',
            //             'merit_increase_forms.date_fulfilled',
            //             'merit_increase_forms.created_at',
            //             'employees.prefix_id',
            //             'employees.id_number',
            //             'employees.first_name',
            //             'employees.middle_name',
            //             'employees.last_name',
            //             'employees.suffix',
            //             'employees.gender',
            //             'employees.image',
            //             'employees.referrer_id',
            //             'referrer.prefix_id AS r_prefix_id',
            //             'referrer.id_number AS r_id_number',
            //             'referrer.first_name AS r_first_name',
            //             'referrer.middle_name AS r_middle_name',
            //             'referrer.last_name AS r_last_name',
            //             'referrer.suffix AS r_suffix',
            //             'requestor.prefix_id AS req_prefix_id',
            //             'requestor.id_number AS req_id_number',
            //             'requestor.first_name AS req_first_name',
            //             'requestor.middle_name AS req_middle_name',
            //             'requestor.last_name AS req_last_name',
            //             'requestor.suffix AS req_suffix',
            //             'employee_statuses.employment_type',
            //             'employee_statuses.employment_type_label',
            //             'employee_statuses.employment_date_start',
            //             'employee_statuses.employment_date_end',
            //             'employee_statuses.regularization_date',
            //             'employee_statuses.hired_date',
            //             'positions.id AS position_id',
            //             'positions.position_name',
            //             'requestor_positions.position_name as requestor_position',
            //             'departments.department_name',
            //             'subunits.subunit_name',
            //             'jobbands.jobband_name',
            //             'jobrates.id as jobrate_id',
            //             'jobrates.job_level', 
            //             'jobrates.job_rate', 
            //             'jobrates.salary_structure', 
            //             'jobrates.jobrate_name',
            //             'proposed_jobrate.id as proposed_jobrate_id',
            //             'proposed_jobrate.job_level as proposed_job_level',
            //             'proposed_jobrate.job_rate as proposed_job_rate',
            //             'proposed_jobrate.salary_structure as proposed_salary_structure',
            //             'proposed_jobrate.jobrate_name as proposed_jobrate_name',
            //         ])
            //         ->where('merit_increase_forms.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'merit-increase-form')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getmerit->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getmerit->count(),
            //             'requests' => MeritIncreaseDetailResource::collection($getmerit),
            //         ];
            //     }
            // }

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
                    ->where('employee_datachanges.is_fulfilled', '=', 'FILED')
                    ->where(function ($query) {
                        $query->where('employee_datachanges.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('employee_datachanges.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'employee-datachange')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('employee_datachanges.effectivity_date', '<=', $target_date)
                    ->get();

                if ($getdc->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getdc->count(),
                        'requests' => EmployeeDataChangeRequestDetail::collection($getdc),
                    ];
                }
            }
        }

        return response()->json($forms);
    }

    public function updateForm(Request $request)
    {
        date_default_timezone_set('Asia/Manila');

        $filenames = [];
        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');
        $employee_id = $request->input('employee_id');
        $state = $request->input('state');
        $state_mark = $request->input('state_mark');
        $state_date_start = $request->input('state_date_start');
        $state_date_end = $request->input('state_date_end');
        $state_date = $request->input('state_date');
        $effectivity_date = $request->input('effectivity_date');
        $remarks = $request->input('remarks');
        
        $range = ($state_date) ? $state_date : $state_date_start.' - '.$state_date_end;

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
            $da_evaluation = DaEvaluation::findOrFail($form_id);

            // $daform_id = $da_evaluation->daform_id;
            // $daform = DaForms::findOrFail($daform_id);

            $da_evaluation->jobrate_id = $request->jobrate_id;
            // $da_evaluation->prev_measures = $daform->measures;
            $da_evaluation->current_status = 'approved';
            $da_evaluation->current_status_mark = 'APPROVED';
            $da_evaluation->is_fulfilled = 'FILED';
            $da_evaluation->employee_data_status = 'UPDATED';
            $da_evaluation->date_fulfilled = Carbon::now()->format('M d, Y h:i a');
            // $da_evaluation->effectivity_date = Carbon::parse($effectivity_date);

            $structures = explode('|', $request->input('datachange_salary_structure'));
            $job_level = trim($structures[0]);
            $salary_structure = trim($structures[1]);
            $jobrate_name = trim($structures[2]);

            $da_evaluation->jobrate_name = $jobrate_name;
            $da_evaluation->salary_structure = $salary_structure;
            $da_evaluation->job_level = $job_level;
            $da_evaluation->additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
            $da_evaluation->allowance = (float) str_replace(',', '', $request->input('allowance'));
            $da_evaluation->job_rate = (float) str_replace(',', '', $request->input('job_rate'));
            $da_evaluation->salary = (float) str_replace(',', '', $request->input('salary'));

            if ($da_evaluation->save()) {
                $daform_id = $da_evaluation->daform_id;
                $daform = DaForms::findOrFail($daform_id);

                if ($daform) {
                    $datachange = DatachangeForms::findOrFail($daform->datachange_id);
                    $employee_id = $datachange->employee_id;
                    $change_position_id = $datachange->change_position_id;

                    $datachange->jobrate_name = $jobrate_name;
                    $datachange->salary_structure = $salary_structure;
                    $datachange->job_level = $job_level;
                    $datachange->additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
                    $datachange->allowance = (float) str_replace(',', '', $request->input('allowance'));
                    $datachange->job_rate = (float) str_replace(',', '', $request->input('job_rate'));
                    $datachange->salary = (float) str_replace(',', '', $request->input('salary'));
                    $datachange->save();

                    $update = DB::table('employee_positions')
                        ->where('employee_id', $employee_id)
                        ->update([
                            'position_id' => $change_position_id,
                            'jobrate_id' => $request->jobrate_id,
                            'jobrate_name' => $jobrate_name,
                            'salary_structure' => $salary_structure,
                            'job_level' => $job_level,
                            'additional_rate' => (float) str_replace(',', '', $request->input('additional_rate')),
                            'allowance' => (float) str_replace(',', '', $request->input('allowance')),
                            'job_rate' => (float) str_replace(',', '', $request->input('job_rate')),
                            'salary' => (float) str_replace(',', '', $request->input('salary')),
                        ]);

                    $update_manpower = DB::table('manpower_forms')
                        ->where('manpower_forms.id', $datachange->manpower_id)
                        ->update(['employee_data_status' => 'UPDATED']);

                    $reference_id = $da_evaluation->code;
                    $record_id = $da_evaluation->id;
                    $record_type = $da_evaluation->form_type;
                    $employee_id = $da_evaluation->employee_id;
                    $details = 'UPDATED EMPLOYEE DATA FOR DA EVALUATION | ASSESSMENT: '.$da_evaluation->assessment_mark;
    
                    Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);
                }

                $activity = 'UPDATED EMPLOYEE DATA REQUEST FOR DA EVALUATION - STATUS: '.$da_evaluation->current_status_mark;
                Helpers::LogActivity($da_evaluation->id, 'FORM REQUEST - DA EVALUATION', $activity);

                $form_history = new FormHistory();
                $form_history->form_id = $da_evaluation->id;
                $form_history->code = $da_evaluation->code;
                $form_history->form_type = $da_evaluation->form_type;
                $form_history->form_data = $da_evaluation->toJson();
                $form_history->status = 'approved';
                $form_history->status_mark = 'APPROVED';
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'file';
                $form_history->remarks = $remarks;
                $form_history->level = $da_evaluation->level;
                $form_history->requestor_id = $da_evaluation->requestor_id;
                $form_history->employee_id = $da_evaluation->employee_id;
                $form_history->is_fulfilled = $da_evaluation->is_fulfilled;
                $form_history->date_fulfilled = $da_evaluation->date_fulfilled;
                $form_history->description = 'UPDATING OF EMPLOYEE DATA FOR DA-EVALUATION';
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $da_evaluation->id;
                $transaction_statuses->code = $da_evaluation->code;
                $transaction_statuses->form_type = $da_evaluation->form_type;
                $transaction_statuses->form_data = $da_evaluation->toJson();
                $transaction_statuses->status = 'approved';
                $transaction_statuses->status_mark = 'APPROVED';
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'file';
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $da_evaluation->level;
                $transaction_statuses->requestor_id = $da_evaluation->requestor_id;
                $transaction_statuses->employee_id = $da_evaluation->employee_id;
                $transaction_statuses->is_fulfilled = $da_evaluation->is_fulfilled;
                $transaction_statuses->date_fulfilled = $da_evaluation->date_fulfilled;
                $transaction_statuses->description = 'UPDATING OF EMPLOYEE DATA FOR DA-EVALUATION';
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($da_evaluation);
        }
    }

    public function updateDataChangeForm(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $filenames = [];
        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');
        $employee_id = $request->input('employee_id');
        $effectivity_date = $request->input('effectivity_date');
        $remarks = $request->input('remarks');

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
            $employee_datachange = EmployeeDataChange::findOrFail($form_id);
            $employee_datachange->current_status = 'approved';
            $employee_datachange->current_status_mark = 'APPROVED';
            $employee_datachange->is_fulfilled = 'FILED';
            $employee_datachange->employee_data_status = 'UPDATED';
            // $employee_datachange->effectivity_date = Carbon::parse($effectivity_date);
            $employee_datachange->date_fulfilled = Carbon::now()->format('M d, Y h:i a');


            if ($request->input('datachange_salary_structure')) {
                $structures = explode('|', $request->input('datachange_salary_structure'));
                $job_level = trim($structures[0]);
                $salary_structure = trim($structures[1]);
                $jobrate_name = trim($structures[2]);

                $employee_datachange->new_jobrate_name = $jobrate_name;
                $employee_datachange->new_salary_structure = $salary_structure;
                $employee_datachange->new_job_level = $job_level;

                $employee_datachange->new_additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
                $employee_datachange->new_allowance = (float) str_replace(',', '', $request->input('allowance'));
                $employee_datachange->new_job_rate = (float) str_replace(',', '', $request->input('job_rate'));
                $employee_datachange->new_salary = (float) str_replace(',', '', $request->input('salary'));
            }

            if ($employee_datachange->save()) {

                $employee_id = $employee_datachange->employee_id;

                $updateArray = [];

                $columns = [
                    'position_id', 
                    'location_id', 
                    'company_id', 
                    'division_cat_id', 
                    'division_id',
                    'additional_rate',
                    'jobrate_name',
                    'salary_structure',
                    'job_level',
                    'allowance',
                    'job_rate',
                    'salary'
                ];

                foreach ($columns as $column) {
                    $variableName = "new_" . $column;

                    if (!empty($employee_datachange->$variableName)) {
                        $updateArray[$column] = $employee_datachange->$variableName;
                    }
                }

                $update = DB::table('employee_positions')
                    ->where('employee_id', $employee_id)
                    ->update($updateArray);

                $form_history = new FormHistory();
                $form_history->form_id = $employee_datachange->id;
                $form_history->code = $employee_datachange->code;
                $form_history->form_type = $employee_datachange->form_type;
                $form_history->form_data = $employee_datachange->toJson();
                $form_history->status = 'approved';
                $form_history->status_mark = 'APPROVED';
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'file';
                $form_history->remarks = $remarks;
                $form_history->level = $employee_datachange->level;
                $form_history->requestor_id = $employee_datachange->requestor_id;
                $form_history->employee_id = $employee_datachange->employee_id;
                $form_history->is_fulfilled = $employee_datachange->is_fulfilled;
                $form_history->date_fulfilled = $employee_datachange->date_fulfilled;
                $form_history->description = 'UPDATING OF EMPLOYEE DATA FOR DATACHANGE REQUEST';
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $employee_datachange->id;
                $transaction_statuses->code = $employee_datachange->code;
                $transaction_statuses->form_type = $employee_datachange->form_type;
                $transaction_statuses->form_data = $employee_datachange->toJson();
                $transaction_statuses->status = 'approved';
                $transaction_statuses->status_mark = 'APPROVED';
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'file';
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $employee_datachange->level;
                $transaction_statuses->requestor_id = $employee_datachange->requestor_id;
                $transaction_statuses->employee_id = $employee_datachange->employee_id;
                $transaction_statuses->is_fulfilled = $employee_datachange->is_fulfilled;
                $transaction_statuses->date_fulfilled = $employee_datachange->date_fulfilled;
                $transaction_statuses->description = 'UPDATING OF EMPLOYEE DATA FOR DATACHANGE REQUEST';
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();
            }

            return response()->json($employee_datachange);
        }
    }

    public function updateManpowerForm(Request $request) {
        date_default_timezone_set('Asia/Manila');

        $form_id = $request->input('form_id');
        $form_type = $request->input('form_type');
        $remarks = $request->input('remarks');
        $tobe_hired = $request->input('tobe_hired');
        $hiring_type = $request->input('hiring_type');
        $effectivity_date = $request->input('effectivity_date');

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

        if ($form_type == 'manpower-form') {
            $manpower = ManpowerForms::findOrFail($form_id);
            $manpower->employee_data_status = 'UPDATED';
            // $manpower->tobe_hired = $tobe_hired;
            // $manpower->hiring_type = $hiring_type;
            // $manpower->effectivity_date = Carbon::parse($effectivity_date);
            $manpower->current_status = 'approved';
            $manpower->current_status_mark = 'APPROVED';
            $manpower->is_fulfilled = 'FILED';
            $manpower->date_fulfilled = Carbon::now();

            if ($manpower->save()) {
                $datachange = DatachangeForms::where('manpower_id', '=', $manpower->id)->first();
                
                if ($datachange) {
                    // $employee_position = DB::table('employee_positions')
                    //     ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                    //     ->select([
                    //         'employee_positions.position_id',
                    //         'positions.department_id'
                    //     ])
                    //     ->where('employee_positions.employee_id', '=', $datachange->employee_id)
                    //     ->get()
                    //     ->first();

                    // $datachange->prev_position_id = $employee_position->position_id;
                    // $datachange->prev_department_id = $employee_position->department_id;
                    $datachange->current_status = 'approved';
                    $datachange->current_status_mark = 'APPROVED';
                    $datachange->is_fulfilled = 'FILED';
                    $datachange->date_fulfilled = Carbon::now()->format('M d, Y');
                    $datachange->save();

                    // $prev_position = Position::where('id', '=', $employee_position->position_id)->first();
                    // $new_position = Position::where('id', '=', $datachange->change_position_id)->first();
                    // $prev_position = ($prev_position) ? $prev_position->position_name : '';
                    // $new_position = ($new_position) ? $new_position->position_name : '';

                    // $reference_id = $manpower->code;
                    // $record_id = $datachange->id;
                    // $record_type = $datachange->form_type;
                    // $employee_id = $datachange->employee_id;
                    // $details = 'UPDATED EMPLOYEE DATA CHANGE | POSITION FROM: '.$prev_position.' TO: '.$new_position;

                    // Helpers::LogHistory($reference_id, $record_id, $record_type, $employee_id, $details);

                    if ($request->for_da && $request->for_da == 'false' && $datachange->for_da == 'false') {
                        $structures = explode('|', $request->input('datachange_salary_structure'));
                        $job_level = trim($structures[0]);
                        $salary_structure = trim($structures[1]);
                        $jobrate_name = trim($structures[2]);
                        $additional_rate = (float) str_replace(',', '', $request->input('additional_rate'));
                        $allowance = (float) str_replace(',', '', $request->input('allowance'));
                        $job_rate = (float) str_replace(',', '', $request->input('job_rate'));
                        $salary = (float) str_replace(',', '', $request->input('salary'));

                        $datachange->jobrate_name = $jobrate_name;
                        $datachange->salary_structure = $salary_structure;
                        $datachange->job_level = $job_level;
                        $datachange->additional_rate = $additional_rate;
                        $datachange->allowance = $allowance;
                        $datachange->job_rate = $job_rate;
                        $datachange->salary = $salary;

                        if ($datachange->save()) {
                            $employee_id = $datachange->employee_id;
                            $change_position = $datachange->change_position_id;

                            $update = DB::table('employee_positions')
                                ->where('employee_id', $employee_id)
                                ->update([
                                    'position_id' => $change_position,
                                    'jobrate_name' => $datachange->jobrate_name,
                                    'salary_structure' => $datachange->salary_structure,
                                    'job_level' => $datachange->job_level,
                                    'additional_rate' => (float) str_replace(',', '', $datachange->additional_rate),
                                    'allowance' => (float) str_replace(',', '', $datachange->allowance),
                                    'job_rate' => (float) str_replace(',', '', $datachange->job_rate),
                                    'salary' => (float) str_replace(',', '', $datachange->salary),
                                ]);
                        }
                    }
                }

                $form_history = new FormHistory();
                $form_history->form_id = $manpower->id;
                $form_history->code = $manpower->code;
                $form_history->form_type = $manpower->form_type;
                $form_history->form_data = $manpower->toJson();
                $form_history->status = 'approved';
                $form_history->status_mark = 'APPROVED';
                $form_history->reviewer_id = Auth::user()->employee_id;
                $form_history->review_date = Carbon::now()->format('M d, Y h:i a');
                $form_history->reviewer_action = 'file';
                $form_history->remarks = $remarks;
                $form_history->level = $manpower->level;
                $form_history->requestor_id = $manpower->requestor_id;
                $form_history->is_fulfilled = $manpower->is_fulfilled;
                $form_history->date_fulfilled = $manpower->date_fulfilled;
                $form_history->description = 'UPDATING OF EMPLOYEE DATA FOR MANPOWER REQEUST FORM';
                $form_history->reviewer_attachment = implode(',', $filenames);
                $form_history->save();

                $transaction_statuses = new TransactionStatuses();
                $transaction_statuses->form_id = $manpower->id;
                $transaction_statuses->code = $manpower->code;
                $transaction_statuses->form_type = $manpower->form_type;
                $transaction_statuses->form_data = $manpower->toJson();
                $transaction_statuses->status = 'approved';
                $transaction_statuses->status_mark = 'APPROVED';
                $transaction_statuses->reviewer_id = Auth::user()->employee_id;
                $transaction_statuses->review_date = Carbon::now()->format('M d, Y h:i a');
                $transaction_statuses->reviewer_action = 'file';
                $transaction_statuses->remarks = $remarks;
                $transaction_statuses->level = $manpower->level;
                $transaction_statuses->requestor_id = $manpower->requestor_id;
                $transaction_statuses->is_fulfilled = $manpower->is_fulfilled;
                $transaction_statuses->date_fulfilled = $manpower->date_fulfilled;
                $transaction_statuses->description = 'UPDATING OF EMPLOYEE DATA FOR MANPOWER REQEUST FORM';
                $transaction_statuses->reviewer_attachment = implode(',', $filenames);
                $transaction_statuses->save();

                $activity = 'UPDATED EMPLOYEE DATA FORM REQUEST FOR MANPOWER FORM - STATUS: '.$manpower->current_status_mark;
                Helpers::LogActivity($manpower->id, 'FORM REQUEST - MANPOWER FORM', $activity);
            }

            return response()->json($manpower);
        }
    }

    public function getFormUpdateCount() {
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

        $target_date = Carbon::now();

        foreach ($formtypes as $value) {
            $form_type = $value->form_type;

            // if ($form_type == 'probi-evaluation') { 
            //     $getprobi = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('probi_evaluations', function($join) { 
            //             $join->on('forms.form_type', '=', 'probi_evaluations.form_type'); 
            //             $join->on('forms.level', '<', 'probi_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'probi_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'probi_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'probi_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'probi_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type'
            //         ])
            //         ->where('probi_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'probi-evaluation')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getprobi->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getprobi->count(),
            //             'requests' => '',
            //         ];
            //     } 
            // }

            // if ($form_type == 'annual-evaluation') { 
            //     $getannual = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('annual_evaluations', function($join) { 
            //             $join->on('forms.form_type', '=', 'annual_evaluations.form_type'); 
            //             $join->on('forms.level', '<', 'annual_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'annual_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'annual_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'annual_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'annual_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type'
            //         ])
            //         ->where('annual_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'annual-evaluation')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getannual->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getannual->count(),
            //             'requests' => '',
            //         ];
            //     }
            // }

            // if ($form_type == 'monthly-evaluation') { 
            //     $getmo = DB::table('transaction_receivers')
            //         ->leftJoin('transaction_approvers', function($join) { 
            //             $join->on('transaction_receivers.transaction_id', '=', 'transaction_approvers.transaction_id');
            //             $join->on('transaction_receivers.form_type', '=', 'transaction_approvers.form_type'); 
            //             $join->on('transaction_receivers.subunit_id', '=', 'transaction_approvers.subunit_id'); 
            //             $join->on('transaction_approvers.level', DB::raw('(SELECT MAX(transaction_approvers.level) FROM transaction_approvers WHERE transaction_approvers.subunit_id = transaction_receivers.subunit_id AND transaction_approvers.form_type = transaction_receivers.form_type AND transaction_approvers.transaction_id = transaction_receivers.transaction_id)'));
            //         })
            //         ->leftJoin('monthly_evaluations', function($join) { 
            //             $join->on('transaction_approvers.transaction_id', '=', 'monthly_evaluations.id'); 
            //             $join->on('transaction_approvers.form_type', '=', 'monthly_evaluations.form_type'); 
            //             $join->on('transaction_approvers.level', '<', 'monthly_evaluations.level'); 
            //         })
            //         ->leftJoin('employees', 'monthly_evaluations.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'monthly_evaluations.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'monthly_evaluations.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'monthly_evaluations.employee_id', '=', 'employee_positions.employee_id')
            //         // ->rightJoin('positions', function($join) { 
            //         //     $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //         //     $join->on('transaction_approvers.subunit_id', '=', 'positions.subunit_id'); 
            //         // })

            //         ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')

            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->where('monthly_evaluations.is_fulfilled', '!=', 'FILED')
            //         ->where('transaction_receivers.form_type', '=', 'monthly-evaluation')
            //         ->where('transaction_receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getmo->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getmo->count(),
            //             'requests' => '',
            //         ];
            //     } 
            // }

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
                    ->leftJoin('datachange_forms', 'manpower_forms.id', '=', 'datachange_forms.manpower_id')

                    ->leftJoin('positions as requested_position', 'manpower_forms.position_id', '=', 'requested_position.id')
                    ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->select([
                        'transaction_approvers.form_type'
                    ])
                    ->where('manpower_forms.is_fulfilled', '=', 'FILED')
                    ->where('datachange_forms.for_da', '=', 'false')
                    ->where(function ($query) {
                        $query->where('manpower_forms.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('manpower_forms.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'manpower-form')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('manpower_forms.effectivity_date', '<=', $target_date)
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
                    ->where('da_evaluations.is_fulfilled', '=', 'FILED')
                    ->where(function ($query) {
                        $query->where('da_evaluations.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('da_evaluations.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'da-evaluation')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('da_evaluations.effectivity_date', '<=', $target_date)
                    ->get();

                if ($getda->count()) {
                    $forms[] = [
                        'form_type' => $form_type,
                        'count' => $getda->count(),
                        'requests' => '',
                    ];
                }
            }

            // if ($form_type == 'merit-increase-form') { 
            //     $getmerit = DB::table('receivers')
            //         ->leftJoin('forms', function($join) { 
            //             $join->on('receivers.form_type', '=', 'forms.form_type'); 
            //             $join->on('receivers.subunit_id', '=', 'forms.subunit_id'); 
            //             $join->on('forms.level', DB::raw('(SELECT MAX(forms.level) FROM forms WHERE forms.subunit_id = receivers.subunit_id AND forms.form_type = receivers.form_type)'));
            //         })
            //         ->leftJoin('merit_increase_forms', function($join) { 
            //             $join->on('forms.form_type', '=', 'merit_increase_forms.form_type'); 
            //             $join->on('forms.level', '<', 'merit_increase_forms.level'); 
            //         })
            //         ->leftJoin('employees', 'merit_increase_forms.employee_id', '=', 'employees.id')
            //         ->leftJoin('employees as referrer', 'employees.referrer_id', '=', 'referrer.id')
            //         ->leftJoin('employees as requestor', 'merit_increase_forms.requestor_id', '=', 'requestor.id')
            //         ->leftJoin('employee_positions as requestor_employee_position', 'merit_increase_forms.requestor_id', '=', 'requestor_employee_position.employee_id')
            //         ->leftJoin('employee_positions', 'merit_increase_forms.employee_id', '=', 'employee_positions.employee_id')
            //         ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            //         ->leftJoin('jobrates as proposed_jobrate', 'merit_increase_forms.jobrate_id', '=', 'proposed_jobrate.id')
            //         ->rightJoin('positions', function($join) { 
            //             $join->on('employee_positions.position_id', '=', 'positions.id'); 
            //             $join->on('forms.subunit_id', '=', 'positions.subunit_id'); 
            //         })
            //         ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            //         ->leftJoin('positions as requestor_positions', 'requestor_employee_position.position_id', '=', 'requestor_positions.id')
            //         ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            //         ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            //         ->leftJoin('employee_statuses', function($join) { 
            //             $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            //         })
            //         ->select([
            //             'forms.form_type'
            //         ])
            //         ->where('merit_increase_forms.is_fulfilled', '!=', 'FILED')
            //         ->where('receivers.form_type', '=', 'merit-increase-form')
            //         ->where('receivers.employee_id', '=', $employee_id)
            //         ->get();

            //     if ($getmerit->count()) {
            //         $forms[] = [
            //             'form_type' => $form_type,
            //             'count' => $getmerit->count(),
            //             'requests' => '',
            //         ];
            //     }
            // }

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
                    ->where('employee_datachanges.is_fulfilled', '=', 'FILED')
                    ->where(function ($query) {
                        $query->where('employee_datachanges.employee_data_status', '!=', 'UPDATED')
                              ->orWhereNull('employee_datachanges.employee_data_status');
                    })
                    ->where('transaction_receivers.form_type', '=', 'employee-datachange')
                    ->where('transaction_receivers.employee_id', '=', $employee_id)
                    ->whereDate('employee_datachanges.effectivity_date', '<=', $target_date)
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
}
