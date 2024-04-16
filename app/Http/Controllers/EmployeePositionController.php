<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Requests\EmployeePositionRequest;
use App\Http\Resources\EmployeePosition AS EmployeePositionResources;
use App\Http\Resources\EmployeePositionWithSuperior;
use App\Http\Resources\EmployeePositionWithUnits;
use App\Models\EmployeePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeePositionController extends Controller
{
    public function index() {
        $employee_positions = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                'employee_positions.id',
                'employee_positions.employee_id',
                'employee_positions.position_id',
                'employee_positions.jobrate_id',
                'employee_positions.additional_rate',
                'employee_positions.additional_tool',
                'employee_positions.remarks',
                'employee_positions.schedule',
                'employee_positions.emp_shift',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'positions.position_name',
                'jobrates.salary_structure',
                'jobrates.job_level',
                'jobrates.jobrate_name',
                'departments.department_name',
                'subunits.subunit_name',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
            ])
            ->get()
            ->map(function ($employee_positions) {
                $employee_positions->superior_name = $employee_positions->s_first_name.' '.$employee_positions->s_middle_name.' '.$employee_positions->s_last_name;
                return $employee_positions;
            });

        return EmployeePositionResources::collection($employee_positions);
        // return response()->json($employee_positions);
    }

    public function show($id) {

    }

    public function store(EmployeePositionRequest $request) {
        $duplicate = DB::table('employee_positions')
            ->select(['id'])
            ->where('employee_id', '=', $request->input('employee_id'))
            ->get();

        if ($duplicate->count()) {
            throw ValidationException::withMessages([
                'employee_id' => ['The employee has already assigned position.']
            ]);
        }

        $employee_position = new EmployeePosition();

        $employee_position->employee_id = $request->input('employee_id');
        $employee_position->position_id = $request->input('position_id');
        $employee_position->jobrate_id = $request->input('jobrate_id');
        $employee_position->additional_rate = $request->input('additional_rate');
        $employee_position->additional_tool = implode(',', $request->input('additional_tool'));
        $employee_position->schedule = $request->input('schedule');
        $employee_position->emp_shift = $request->input('emp_shift');
        $employee_position->remarks = $request->input('remarks');

        if ($employee_position->save()) {
            Helpers::LogActivity($employee_position->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - POSITION SECTION');
            return response()->json($employee_position);
        }
    }

    public function update(EmployeePositionRequest $request, $id) {
        $employee_position = EmployeePosition::findOrFail($id);

        $employee_position->employee_id = $request->input('employee_id');
        $employee_position->position_id = $request->input('position_id');
        $employee_position->jobrate_id = $request->input('jobrate_id');
        $employee_position->additional_rate = $request->input('additional_rate');
        $employee_position->additional_tool = implode(',', $request->input('additional_tool'));
        $employee_position->schedule = $request->input('schedule');
        $employee_position->emp_shift = $request->input('emp_shift');
        $employee_position->remarks = $request->input('remarks');

        if ($employee_position->save()) {
            Helpers::LogActivity($employee_position->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - POSITION SECTION');
            
            $employee_position = DB::table('employee_positions')
                ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'employee_positions.id',
                    'employee_positions.employee_id',
                    'employee_positions.position_id',
                    'employee_positions.jobrate_id',
                    'employee_positions.additional_rate',
                    'employee_positions.additional_tool',
                    'employee_positions.schedule',
                    'employee_positions.emp_shift',
                    'employee_positions.remarks',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                    'positions.position_name',
                    'jobrates.salary_structure',
                    'jobrates.job_level',
                    'jobrates.jobrate_name',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'superior.prefix_id AS s_prefix_id',
                    'superior.id_number AS s_id_number',
                    'superior.first_name AS s_first_name',
                    'superior.middle_name AS s_middle_name',
                    'superior.last_name AS s_last_name',
                    'superior.suffix AS s_suffix',
                ])
                ->where('employee_positions.id', '=', $employee_position->id)
                ->get()
                ->map(function ($employee_positions) {
                    $employee_positions->superior_name = $employee_positions->s_first_name.' '.$employee_positions->s_middle_name.' '.$employee_positions->s_last_name;
                    return $employee_positions;
                });

            return EmployeePositionResources::collection($employee_position);
            // return response()->json($employee_position);
        }
    }

    public function updateWithUnits(Request $request, $id) {
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
            Helpers::LogActivity($employee_position->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - POSITION SECTION');
            
            $employee_position = DB::table('employee_positions')
                ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
                ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
                ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
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
                    'employee_positions.schedule',
                    'employee_positions.emp_shift',
                    'employee_positions.remarks',
                    'employees.prefix_id',
                    'employees.id_number',
                    'employees.first_name',
                    'employees.middle_name',
                    'employees.last_name',
                    'employees.suffix',
                    'employees.gender',
                    'employees.image',
                    'positions.position_name',
                    'positions.team',
                    // 'jobrates.salary_structure',
                    // 'jobrates.job_level',
                    // 'jobrates.jobrate_name',
                    // 'jobrates.job_rate',
                    'departments.department_name',
                    'subunits.subunit_name',
                    'superior.prefix_id AS s_prefix_id',
                    'superior.id_number AS s_id_number',
                    'superior.first_name AS s_first_name',
                    'superior.middle_name AS s_middle_name',
                    'superior.last_name AS s_last_name',
                    'superior.suffix AS s_suffix',
                    'divisions.division_name',
                    'division_categories.category_name',
                    'companies.company_name',
                    'locations.location_name'
                ])
                ->where('employee_positions.id', '=', $employee_position->id)
                ->get()
                ->map(function ($employee_positions) {
                    $employee_positions->superior_name = $employee_positions->s_first_name.' '.$employee_positions->s_middle_name.' '.$employee_positions->s_last_name;
                    return $employee_positions;
                });

            return EmployeePositionWithUnits::collection($employee_position);
            // return response()->json($employee_position);
        }
    }

    public function destroy($id) {
        $employee_position = EmployeePosition::findOrFail($id);

        if ($employee_position->delete()) {
            Helpers::LogActivity($employee_position->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - POSITION SECTION');
            return new EmployeePositionResources($employee_position);
        }
    }

    public function getEmployeePosition(Request $request, $id) {
        $employee_positions = DB::table('employee_positions')
            ->leftJoin('employees', 'employee_positions.employee_id', '=', 'employees.id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('jobrates', 'employee_positions.jobrate_id', '=', 'jobrates.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
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
                'employee_positions.schedule',
                'employee_positions.emp_shift',
                'employee_positions.remarks',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
                'positions.position_name',
                'positions.team',
                // 'jobrates.salary_structure',
                // 'jobrates.job_level',
                // 'jobrates.jobrate_name',
                // 'jobrates.job_rate',
                'departments.department_name',
                'subunits.subunit_name',
                'superior.prefix_id AS s_prefix_id',
                'superior.id_number AS s_id_number',
                'superior.first_name AS s_first_name',
                'superior.middle_name AS s_middle_name',
                'superior.last_name AS s_last_name',
                'superior.suffix AS s_suffix',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'locations.location_name'
            ])
            ->where('employee_id', '=', $id)
            ->get()
            ->map(function ($employee_positions) {
                $employee_positions->superior_name = $employee_positions->s_first_name.' '.$employee_positions->s_middle_name.' '.$employee_positions->s_last_name;
                return $employee_positions;
            });

        return EmployeePositionWithUnits::collection($employee_positions);
        // return response()->json($employee_positions);
    }
}
