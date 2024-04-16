<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Location as LocationResources;

class ApiController extends Controller
{
    public function fetchEmployeeData(Request $request) {
        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('employee_contacts', function ($join) {
                $join->on('employees.id', '=', 'employee_contacts.employee_id')
                     ->whereNotNull('employee_contacts.contact_details');
            })
            // ->leftJoin('employee_statuses', function($join) { 
            //     $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            // })
            // ->leftJoin('employee_states', function($join) { 
            //     $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            // })
            ->select([
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
                'ref.prefix_id AS referrer_prefix_id',
                'ref.id_number AS referrer_id_number',
                'ref.first_name AS referrer_first_name',
                'ref.middle_name AS referrer_middle_name',
                'ref.last_name AS referrer_last_name',
                'ref.suffix AS referrer_suffix',
                'positions.position_name',
                'positions.schedule',
                'positions.shift',
                'positions.team',
                'positions.tools',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name',
                // 'employee_statuses.employment_type_label',
                // 'employee_states.employee_state_label',
                'locations.location_name',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'employee_contacts.contact_details'
            ])
            ->where('employees.current_status', '=', 'approved');

        if ($request->id) {
            $employees = $query->where('employees.id_number', '=', $request->id);
        }

        $employees = $query->get();

        return EmployeeData::collection($employees);
    }

    public function fetchEmployeeDataByNumber(Request $request) {
        $prefix_id = urlencode($request->prefix_id);
        $id_number = urlencode($request->id_number); 

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('employee_contacts', function ($join) {
                $join->on('employees.id', '=', 'employee_contacts.employee_id')
                     ->whereNotNull('employee_contacts.contact_details');
            })
            // ->leftJoin('employee_statuses', function($join) { 
            //     $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            // })
            // ->leftJoin('employee_states', function($join) { 
            //     $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            // })
            ->select([
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
                'ref.prefix_id AS referrer_prefix_id',
                'ref.id_number AS referrer_id_number',
                'ref.first_name AS referrer_first_name',
                'ref.middle_name AS referrer_middle_name',
                'ref.last_name AS referrer_last_name',
                'ref.suffix AS referrer_suffix',
                'positions.position_name',
                'positions.schedule',
                'positions.shift',
                'positions.team',
                'positions.tools',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name',
                // 'employee_statuses.employment_type_label',
                // 'employee_states.employee_state_label',
                'locations.location_name',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'employee_contacts.contact_details'
            ])
            ->where('employees.current_status', '=', 'approved');

        if ($id_number && $prefix_id) {
            $employees = $query->where('employees.id_number', '=', $id_number)
                ->where('employees.prefix_id', '=', $prefix_id);
        }

        $employees = $query->get();

        return EmployeeData::collection($employees);
    }

    public function fetchEmployeeActive(Request $request) {
        $status = 'ACTIVE';

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('employee_contacts', function ($join) {
                $join->on('employees.id', '=', 'employee_contacts.employee_id')
                     ->whereNotNull('employee_contacts.contact_details');
            })
            // ->leftJoin('employee_statuses', function($join) { 
            //     $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            // })
            // ->leftJoin('employee_states', function($join) { 
            //     $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            // })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)'));
            })
            ->select([
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
                'ref.prefix_id AS referrer_prefix_id',
                'ref.id_number AS referrer_id_number',
                'ref.first_name AS referrer_first_name',
                'ref.middle_name AS referrer_middle_name',
                'ref.last_name AS referrer_last_name',
                'ref.suffix AS referrer_suffix',
                'positions.position_name',
                'positions.schedule',
                'positions.shift',
                'positions.team',
                'positions.tools',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name',
                // 'employee_statuses.employment_type_label',
                // 'employee_states.employee_state_label',
                'locations.location_name',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'employee_contacts.contact_details'
            ])
            ->where('employees.current_status', '=', 'approved');

        // if ($id_number && $prefix_id) {
        //     $employees = $query->where('employees.id_number', '=', $id_number)
        //         ->where('employees.prefix_id', '=', $prefix_id);
        // }

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

        if ($status == 'ACTIVE') {
            $employees = $query->where(function ($q) use ($inactive) {
                $q->where(function ($innerQ) use ($inactive) {
                    $innerQ->whereNotIn('employee_states.employee_state_label', $inactive)
                        ->orWhereNull('employee_states.employee_state');
                })
                ->orWhere(function ($innerQ) use ($inactive) {
                    $innerQ->whereIn('employee_states.employee_state_label', $inactive)
                        ->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '>', date('Y-m-d'));
                });
            });
        } 

        $employees = $query->get();

        return EmployeeData::collection($employees);
    }

    public function fetchEmployeeInActive(Request $request) {
        $status = 'INACTIVE';

        $query = DB::table('employees')
            ->leftJoin('employees AS ref', 'employees.referrer_id', '=', 'ref.id')
            ->leftJoin('employee_positions', 'employees.id', '=', 'employee_positions.employee_id')
            ->leftJoin('positions', 'employee_positions.position_id', '=', 'positions.id')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('locations', 'employee_positions.location_id', '=', 'locations.id')
            ->leftJoin('divisions', 'employee_positions.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'employee_positions.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'employee_positions.company_id', '=', 'companies.id')
            ->leftJoin('employee_contacts', function ($join) {
                $join->on('employees.id', '=', 'employee_contacts.employee_id')
                     ->whereNotNull('employee_contacts.contact_details');
            })
            // ->leftJoin('employee_statuses', function($join) { 
            //     $join->on('employee_statuses.created_at', DB::raw('(SELECT MAX(employee_statuses.created_at) FROM employee_statuses WHERE employee_statuses.employee_id = employees.id)')); 
            // })
            // ->leftJoin('employee_states', function($join) { 
            //     $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)')); 
            // })
            ->leftJoin('employee_states', function($join) { 
                $join->on('employee_states.created_at', DB::raw('(SELECT MAX(employee_states.created_at) FROM employee_states WHERE employee_states.employee_id = employees.id)'));
            })
            ->select([
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
                'ref.prefix_id AS referrer_prefix_id',
                'ref.id_number AS referrer_id_number',
                'ref.first_name AS referrer_first_name',
                'ref.middle_name AS referrer_middle_name',
                'ref.last_name AS referrer_last_name',
                'ref.suffix AS referrer_suffix',
                'positions.position_name',
                'positions.schedule',
                'positions.shift',
                'positions.team',
                'positions.tools',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name',
                // 'employee_statuses.employment_type_label',
                // 'employee_states.employee_state_label',
                'locations.location_name',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'employee_contacts.contact_details'
            ])
            ->where('employees.current_status', '=', 'approved');

        // if ($id_number && $prefix_id) {
        //     $employees = $query->where('employees.id_number', '=', $id_number)
        //         ->where('employees.prefix_id', '=', $prefix_id);
        // }

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

        if ($status == 'INACTIVE') {
            $employees = $query->whereIn('employee_states.employee_state_label', $inactive)
                ->where(function ($query) {
                    $query->whereDate(DB::raw("STR_TO_DATE(employee_states.state_date, '%M %e, %Y')"), '<=', date('Y-m-d'));
                });
        }

        $employees = $query->get();

        return EmployeeData::collection($employees);
    }

    public function fetchLocationData() {
        $locations = DB::table('locations')
            ->select(['code', 'location_name'])
            ->orderBy('location_name', 'desc')
            ->get();
            
        return response()->json(['data' => $locations]);
    }

    public function fetchDivisionData() {
        $divisions = DB::table('divisions')
            ->select(['code', 'division_name'])
            ->orderBy('division_name', 'desc')
            ->get();
            
        return response()->json(['data' => $divisions]);
    }

    public function fetchDepartmentData() {
        $departments = DB::table('departments')
            ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
            ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
            ->select([
                'departments.id',
                // 'departments.department_code', 
                'departments.department_name', 
                // 'divisions.division_name',
                // 'division_categories.category_name',
                // 'companies.company_name',
                // 'locations.location_name'
                'departments.status'
            ])
            ->orderBy('departments.department_name', 'desc')
            ->get();

        return response()->json(['data' => $departments]);
    }

    public function fetchSubunitData() {
        $subunits = DB::table('subunits')
            ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
            ->select([
                'subunits.id',
                'subunits.subunit_name',
                'departments.department_name',
                'subunits.status'
            ])
            ->get();

        return response()->json(['data' => $subunits]);
    }

    public function fetchPositionData() {
        $positions = DB::table('positions')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('kpis', 'positions.id', '=', 'kpis.position_id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                'positions.position_name',
                'positions.payrate',
                'positions.no_of_months',
                'positions.schedule',
                'positions.shift',
                'positions.team',
                'positions.attachments',
                'positions.tools',
                'superior.prefix_id AS superior_prefix_id',
                'superior.id_number AS superior_id_number',
                'superior.first_name AS superior_first_name',
                'superior.middle_name AS superior_middle_name',
                'superior.last_name AS superior_last_name',
                'superior.suffix AS superior_suffix',
                'departments.department_name',
                'subunits.subunit_name',
                'jobbands.jobband_name',
            ])
            ->get();

        return response()->json(['data' => $positions]);
    }

    public function fetchBarangays($citymun_code) {
        $barangays = DB::table('barangays')
            ->select([
                'id', 
                'brgy_code', 
                'brgy_desc', 
                'reg_code',
                'prov_code',
                'citymun_code'
            ])
            ->where('citymun_code', '=', $citymun_code)
            ->get();

        $response = ($barangays->count()) ? response()->json($barangays) : response()->json(['no data found']);

        return $response;
    }

    public function fetchRegions() {
        $regions = DB::table('regions')
            ->select([
                'id', 
                'reg_desc',
                'reg_code'
            ])
            ->get();

        return response()->json($regions);
    }

    public function fetchProvinces($reg_code) {
        $provinces = DB::table('provinces')
            ->select([
                'id', 
                'prov_desc',
                'prov_code',
                'reg_code'
            ])
            ->where('reg_code', '=', $reg_code)
            ->get();

        $response = ($provinces->count()) ? response()->json($provinces) : response()->json(['no data found']);

        return $response;
    }

    public function fetchMunicipals($prov_code) {
        $municipals = DB::table('municipals')
            ->select([
                'id', 
                'citymun_desc',
                'citymun_code',
                'reg_code',
                'prov_code'
            ])
            ->where('prov_code', '=', $prov_code)
            ->get();

        $response = ($municipals->count()) ? response()->json($municipals) : response()->json(['no data found']);

        return $response;
    }

    public function fetchAddress() {
        $regions = DB::table('regions')
            ->select([
                // 'id', 
                'reg_desc',
                'reg_code'
            ])
            ->get();

        $provinces = DB::table('provinces')
            ->select([
                // 'id', 
                'prov_desc',
                'prov_code',
                'reg_code'
            ])
            ->get();

        $municipals = DB::table('municipals')
            ->select([
                // 'id', 
                'citymun_desc',
                'citymun_code',
                // 'reg_code',
                'prov_code'
            ])
            ->get();

        $barangays = DB::table('barangays')
            ->select([
                // 'id', 
                // 'brgy_code', 
                'brgy_desc', 
                // 'reg_code',
                // 'prov_code',
                'citymun_code'
            ])
            ->get();

        $data = [
            'regions' => $regions,
            'provinces' => $provinces,
            'municipals' => $municipals,
            'barangays' => $barangays
        ];

        return response()->json($data);
    }
}
