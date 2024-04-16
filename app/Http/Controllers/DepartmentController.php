<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\DepartmentsExport;
use App\Http\Requests\DepartmentRequest;
use App\Http\Resources\Department as DepartmentResources;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'department_name' : 'created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $departments = DB::table('departments')
                    ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
                    ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
                    ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
                    ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
                    ->select([
                        'departments.id', 
                        'departments.code', 
                        'departments.department_code', 
                        'departments.department_name', 
                        'departments.status',
                        'departments.status_description',
                        'departments.division_id', 
                        'departments.division_cat_id', 
                        'departments.location_id', 
                        'departments.company_id', 
                        'departments.created_at',
                        'divisions.division_name',
                        'division_categories.category_name',
                        'companies.company_name',
                        'locations.location_name'
                    ])
                    ->where('departments.department_name', 'LIKE', $value)
                    // ->whereNull('departments.deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $departments = DB::table('departments')
                    ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
                    ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
                    ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
                    ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
                    ->select([
                        'departments.id', 
                        'departments.code',
                        'departments.department_code', 
                        'departments.department_name', 
                        'departments.status',
                        'departments.status_description',
                        'departments.division_id', 
                        'departments.division_cat_id', 
                        'departments.location_id', 
                        'departments.company_id', 
                        'departments.created_at',
                        'divisions.division_name',
                        'division_categories.category_name',
                        'companies.company_name',
                        'locations.location_name'
                    ])
                    // ->whereNull('departments.deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return DepartmentResources::collection($departments);
    }

    public function show($id)
    {
        $department = Department::findOrFail($id);
        return new DepartmentResources($department);
    }

    public function store(DepartmentRequest $request)
    {        
        $department = new Department();
        $department->code = Helpers::generateCodeNewVersion('departments', 'DPT');
        // $department->department_code = $request->input('department_code');
        $department->department_name = $request->input('department_name');
        $department->division_id = $request->input('division_id');
        $department->division_cat_id = $request->input('division_cat_id');
        $department->company_id = $request->input('company_id');
        $department->location_id = $request->input('location_id');
        $department->status = 'active';
        $department->status_description = 'ACTIVE';

        if ($department->save()) {
            Helpers::LogActivity($department->id, 'MASTERLIST - DEPARTMENT', 'ADDED NEW DEPARTMENT DATA');
            return new DepartmentResources($department);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'department_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u', 'unique:departments,department_name,'.$id],
            // 'department_code' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u', 'unique:departments,department_code,'.$id],
            // 'division_id' => ['required', Rule::notIn(['null'])],
            // 'division_cat_id' => ['required', Rule::notIn(['null'])],
            // 'company_id' => ['required', Rule::notIn(['null'])],
            // 'location_id' => ['required', Rule::notIn(['null'])]
        ]);

        $department = Department::findOrFail($id);
        // $department->department_code = $request->input('department_code');
        $department->department_name = $request->input('department_name');
        $department->division_id = $request->input('division_id');
        $department->division_cat_id = $request->input('division_cat_id');
        $department->company_id = $request->input('company_id');
        $department->location_id = $request->input('location_id');

        // return the updated or newly added article
        if ($department->save()) {
            Helpers::LogActivity($department->id, 'MASTERLIST - DEPARTMENT', 'UPDATED DEPARTMENT DATA');

            $departments = DB::table('departments')
                ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
                ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
                ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
                ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
                ->select([
                    'departments.id', 
                    'departments.code',
                    'departments.department_code', 
                    'departments.department_name', 
                    'departments.status',
                    'departments.status_description',
                    'departments.division_id', 
                    'departments.division_cat_id', 
                    'departments.location_id', 
                    'departments.company_id', 
                    'departments.created_at',
                    'divisions.division_name',
                    'division_categories.category_name',
                    'companies.company_name',
                    'locations.location_name'
                ])
                ->where('departments.id', '=', $department->id)
                ->get();
            
            return DepartmentResources::collection($departments);
        }
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        if ($department->delete()) {
            Helpers::LogActivity($department->id, 'MASTERLIST - DEPARTMENT', 'DELETED DEPARTMENT DATA');
            return new DepartmentResources($department);
        }
    }

    public function export() 
    {
        $query = DB::table('departments')
            ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
            ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
            ->select([
                'departments.id', 
                'departments.code',
                'departments.department_code', 
                'departments.department_name', 
                'departments.status',
                'departments.status_description',
                'departments.division_id', 
                'departments.division_cat_id', 
                'departments.location_id', 
                'departments.company_id', 
                'departments.created_at',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'locations.location_name'
            ])
            ->orderBy('departments.id', 'desc');

        $filename = 'departments-exportall.xlsx';
        $department_export = new DepartmentsExport($query);
        $department_export->store('public/files/'.$filename);
        $link = '/storage/files/'.$filename;
        
        return response()->json([
            'link' => $link
        ]);
    }

    public function exportByDate($daterange)
    {
        if (!empty($daterange)) {
            $daterange = explode('-', $daterange);
            $from = $daterange[0];
            $to = $daterange[1];
            $dateFrom = (new Carbon($from))->format('Y-m-d')." 00:00:00";
            $dateTo = (new Carbon($to))->format('Y-m-d')." 23:59:59";
            
            $query = DB::table('departments')
                ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
                ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
                ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
                ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
                ->select([
                    'departments.id', 
                    'departments.code',
                    'departments.department_code', 
                    'departments.department_name', 
                    'departments.status',
                    'departments.status_description',
                    'departments.division_id', 
                    'departments.division_cat_id', 
                    'departments.location_id', 
                    'departments.company_id', 
                    'departments.created_at',
                    'divisions.division_name',
                    'division_categories.category_name',
                    'companies.company_name',
                    'locations.location_name'
                ])
                ->whereBetween('departments.created_at', [$dateFrom, $dateTo])
                ->orderBy('departments.id', 'desc');

            $count = $query->count();
            $filename = 'departments-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $department_export = new DepartmentsExport($query);
                $department_export->store('public/files/'.$filename);
            }

            return response()->json([
                'link' => $link,
                'count' => $count
            ]);
        }
    }

    public function sortData() 
    {
        $field = request('field');
        $sort = request('sort');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'department_name' : 'created_at';            

            $departments = DB::table('departments')->select(['id', 'code', 'department_code', 'department_name', 'division_id', 'division_cat_id', 'location_id', 'company_id', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return DepartmentResources::collection($departments);
        }
    }

    public function getDepartments() 
    {
        $departments = DB::table('departments')
            ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
            ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
            ->select([
                'departments.id', 
                'departments.code',
                'departments.department_code', 
                'departments.department_name', 
                'departments.status',
                'departments.status_description',
                'departments.division_id', 
                'departments.division_cat_id', 
                'departments.location_id', 
                'departments.company_id', 
                'departments.created_at',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'locations.location_name'
            ])
            // ->whereNull('departments.deleted_at')
            ->where('departments.status', '!=', 'inactive')
            ->orderBy('departments.department_name', 'desc')
            ->get();

        return DepartmentResources::collection($departments);
    }

    public function getDepartment($id) 
    {
        $department = DB::table('departments')
            ->leftJoin('divisions', 'departments.division_id', '=', 'divisions.id')
            ->leftJoin('division_categories', 'departments.division_cat_id', '=', 'division_categories.id')
            ->leftJoin('companies', 'departments.company_id', '=', 'companies.id')
            ->leftJoin('locations', 'departments.location_id', '=', 'locations.id')
            ->select([
                'departments.id', 
                'departments.code',
                'departments.department_code', 
                'departments.department_name', 
                'departments.status',
                'departments.status_description',
                'departments.division_id', 
                'departments.division_cat_id', 
                'departments.location_id', 
                'departments.company_id', 
                'departments.created_at',
                'divisions.division_name',
                'division_categories.category_name',
                'companies.company_name',
                'locations.location_name'
            ])
            // ->whereNull('departments.deleted_at')
            ->where('departments.id', '=', $id)
            ->get();

        return DepartmentResources::collection($department);
    }
}
