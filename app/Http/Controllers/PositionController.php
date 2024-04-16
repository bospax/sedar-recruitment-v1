<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\PositionsExport;
use App\Http\Requests\PositionRequest;
use App\Http\Resources\Position as PositionResources;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    public function index()
    {
        $positions = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'positions.position_name' : 'positions.created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $positions = DB::table('positions')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                    ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                    ->select([
                        'positions.id', 
                        'positions.code',
                        'positions.department_id',
                        'positions.subunit_id',
                        'positions.location_id',
                        'positions.jobband_id',
                        'positions.position_name',
                        'positions.status',
                        'positions.status_description',
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
                        'locations.location_name',
                        'jobbands.jobband_name',
                        'jobbands.order'
                    ])
                    // ->where('positions.position_name', 'LIKE', $value)
                    ->where(function($query) use ($value){
                        $query->where('positions.position_name', 'LIKE', $value)
                            ->orWhere('superior.first_name', 'LIKE', $value)
                            ->orWhere('superior.middle_name', 'LIKE', $value)
                            ->orWhere('superior.last_name', 'LIKE', $value)
                            ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.last_name) LIKE '{$value}'")
                            ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name) LIKE '{$value}'");
                    })
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $positions = DB::table('positions')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                    ->select([
                        'positions.id', 
                        'positions.code',
                        'positions.department_id',
                        'positions.subunit_id',
                        'positions.location_id',
                        'positions.jobband_id',
                        'positions.position_name',
                        'positions.status',
                        'positions.status_description',
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
                        'locations.location_name',
                        'jobbands.jobband_name',
                        'jobbands.order'
                    ])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return PositionResources::collection($positions);
    }

    public function loadFilterPosition(Request $request) {
        $positions = DB::table('positions')
            ->select([
                'positions.position_name',
            ])
            ->orderBy('positions.created_at', 'desc')
            ->groupBy('position_name')
            ->pluck('position_name');

        return response()->json($positions);
    }

    public function loadFilterDepartment(Request $request) {
        $departments = DB::table('positions')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->select([
                'departments.department_name',
            ])
            ->orderBy('positions.created_at', 'desc')
            ->groupBy('department_name')
            ->pluck('department_name');

        return response()->json($departments);
    }

    public function loadFilterSubunit(Request $request) {
        $subunits = DB::table('positions')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->select([
                'subunits.subunit_name',
            ])
            ->orderBy('positions.created_at', 'desc')
            ->groupBy('subunit_name')
            ->pluck('subunit_name');

        return response()->json($subunits);
    }

    public function loadFilterLocation(Request $request) {
        $locations = DB::table('positions')
            ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
            ->select([
                'locations.location_name',
            ])
            ->orderBy('positions.created_at', 'desc')
            ->groupBy('location_name')
            ->pluck('location_name');

        return response()->json($locations);
    }

    public function loadFilterSuperior(Request $request) {
        $superiors = DB::table('positions')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                DB::raw('CONCAT(first_name, " ", middle_name, " ", last_name, " ", suffix) AS superior_name')
            ])
            ->orderBy('first_name', 'asc')
            ->groupBy('superior_name')
            ->get()
            ->pluck('superior_name');

        return response()->json($superiors);
    }

    public function getPositionsUnpaginated() {
        $positions = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $filter_position = request('position');
        $filter_department = request('department');
        $filter_subunit = request('subunit');
        $filter_location = request('location');
        $filter_superior = request('superior');

        // dd(request());
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'positions.position_name' : 'positions.created_at';

            $query = DB::table('positions')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                    ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                    ->select([
                        'positions.id', 
                        'positions.code',
                        'positions.department_id',
                        'positions.subunit_id',
                        'positions.location_id',
                        'positions.jobband_id',
                        'positions.position_name',
                        'positions.status',
                        'positions.status_description',
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
                        'locations.location_name',
                        'jobbands.jobband_name',
                        'jobbands.order'
                    ]);

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $positions = $query->where(function($query) use ($value) {
                    $query->where('positions.position_name', 'LIKE', $value)
                        ->orWhere('superior.first_name', 'LIKE', $value)
                        ->orWhere('superior.middle_name', 'LIKE', $value)
                        ->orWhere('superior.last_name', 'LIKE', $value)
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name) LIKE '{$value}'");
                });
            }

            if ($filter_position) {
                $positions = $query->where('positions.position_name', '=', $filter_position);
            }

            if ($filter_department) {
                $positions = $query->where('departments.department_name', '=', $filter_department);
            }

            if ($filter_subunit) {
                $positions = $query->where('subunits.subunit_name', '=', $filter_subunit);
            }

            if ($filter_location) {
                $positions = $query->where('locations.location_name', '=', $filter_location);
            }

            if ($filter_superior) {
                $filter_superior = trim($filter_superior);
                $value = '%'.$filter_superior.'%';

                $positions = $query->where(function($query) use ($value) {
                    $query->whereRaw("CONCAT(superior.first_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name, ' ', superior.suffix) LIKE '{$value}'");
                });
            }

            $positions = $query->orderBy($field, $sort)->get();
        }

        return PositionResources::collection($positions);
    }

    public function getPositionsWithFilters() {
        $positions = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $filter_position = request('position');
        $filter_department = request('department');
        $filter_subunit = request('subunit');
        $filter_location = request('location');
        $filter_superior = request('superior');

        // dd(request());
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'positions.position_name' : 'positions.created_at';

            $query = DB::table('positions')
                    ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                    ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                    ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                    ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                    ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                    ->select([
                        'positions.id', 
                        'positions.code',
                        'positions.department_id',
                        'positions.subunit_id',
                        'positions.location_id',
                        'positions.jobband_id',
                        'positions.position_name',
                        'positions.status',
                        'positions.status_description',
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
                        'locations.location_name',
                        'jobbands.jobband_name',
                        'jobbands.order'
                    ]);

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                
                $positions = $query->where(function($query) use ($value) {
                    $query->where('positions.position_name', 'LIKE', $value)
                        ->orWhere('superior.first_name', 'LIKE', $value)
                        ->orWhere('superior.middle_name', 'LIKE', $value)
                        ->orWhere('superior.last_name', 'LIKE', $value)
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name) LIKE '{$value}'");
                });
            }

            if ($filter_position) {
                $positions = $query->where('positions.position_name', '=', $filter_position);
            }

            if ($filter_department) {
                $positions = $query->where('departments.department_name', '=', $filter_department);
            }

            if ($filter_subunit) {
                $positions = $query->where('subunits.subunit_name', '=', $filter_subunit);
            }

            if ($filter_location) {
                $positions = $query->where('locations.location_name', '=', $filter_location);
            }

            if ($filter_superior) {
                $filter_superior = trim($filter_superior);
                $value = '%'.$filter_superior.'%';

                $positions = $query->where(function($query) use ($value) {
                    $query->whereRaw("CONCAT(superior.first_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name) LIKE '{$value}'")
                        ->orWhereRaw("CONCAT(superior.first_name, ' ', superior.middle_name, ' ', superior.last_name, ' ', superior.suffix) LIKE '{$value}'");
                });
            }

            $positions = $query->orderBy($field, $sort)
                ->paginate(15);
        }

        return PositionResources::collection($positions);
    }

    public function changeSuperior(Request $request) {
        $superior_id = $request->superior_id;
        $list = $request->list;

        $update = Position::whereIn('id', $list)->update(['superior' => $superior_id]);

        return response()->json(['success']);
    }

    public function show($id)
    {
        $position = Position::findOrFail($id);
        return new PositionResources($position);
    }

    public function store(PositionRequest $request)
    {
        // dd($request);
        $filenames = [];
        $position = new Position();

        $position->code = Helpers::generateCodeNewVersion('positions', 'PO');
        $position->department_id = $request->input('department_id');
        $position->subunit_id = $request->input('subunit_id');
        // $position->location_id = $request->input('location_id');
        // $position->jobrate_id = $request->input('jobrate_id');
        $position->jobband_id = $request->input('jobband_id');
        $position->position_name = $request->input('position_name');
        $position->status = 'active';
        $position->status_description = 'ACTIVE';
        $position->payrate = $request->input('payrate');
        $position->employment = $request->input('employment');
        $position->no_of_months = $request->input('no_of_months');
        // $position->schedule = $request->input('schedule');
        $position->schedule = ($request->input('custom_schedule')) ? $request->input('custom_schedule') : '';
        $position->shift = $request->input('shift');
        $position->team = $request->input('team');
        $position->tools = $request->input('tools');
        $position->superior = $request->input('superior');

        // handle file upload
        // if ($request->hasFile('job_profile')) {
        //     $filenameWithExt = $request->file('job_profile')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('job_profile')->storeAs('public/job_profiles', $fileNameToStore);
        //     $position->job_profile = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/position_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $position->attachments = implode(',', $filenames);
            }
        } else {
            throw ValidationException::withMessages([
                'attachment' => ['Please attach the Job Profile for this Position.']
            ]);
        }

        if ($position->save()) {
            Helpers::LogActivity($position->id, 'MASTERLIST - POSITIONS', 'ADDED NEW POSITIONS DATA');
            return new PositionResources($position);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'position_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u']
        ]);

        $filenames = [];
        $position = Position::findOrFail($id);
        $position->department_id = $request->input('department_id');
        $position->subunit_id = $request->input('subunit_id');
        // $position->location_id = $request->input('location_id');
        // $position->jobrate_id = $request->input('jobrate_id');
        $position->jobband_id = $request->input('jobband_id');
        $position->position_name = $request->input('position_name');
        $position->payrate = $request->input('payrate');
        $position->employment = $request->input('employment');
        $position->no_of_months = $request->input('no_of_months');
        // $position->schedule = $request->input('schedule');
        $position->schedule = ($request->input('custom_schedule')) ? $request->input('custom_schedule') : '';
        $position->shift = $request->input('shift');
        $position->team = $request->input('team');
        $position->tools = $request->input('tools');
        $position->superior = $request->input('superior');

        // handle file upload
        // if ($request->hasFile('job_profile')) {
        //     // $old_file = $position->job_profile;
        //     // $old_path = 'storage/job_profiles/'.$old_file;

        //     // if (is_file($old_path)) {
        //     //     unlink($old_path);
        //     // }

        //     $filenameWithExt = $request->file('job_profile')->getClientOriginalName();
        //     $fileNameToStore = time().'_'.$filenameWithExt;
        //     $path = $request->file('job_profile')->storeAs('public/job_profiles', $fileNameToStore);
        //     $position->job_profile = $fileNameToStore;
        // }

        if ($request->hasFile('attachments')) {
            $files = $request->attachments;

            foreach ($files as $file) {
                $filenameWithExt = $file->getClientOriginalName();
                $fileNameToStore = time().'_'.str_replace(',', '', $filenameWithExt);
                $path = $file->storeAs('public/position_attachments', $fileNameToStore);
                $filenames[] = str_replace(',', '', $fileNameToStore);
            }

            if ($filenames) {
                $position->attachments = implode(',', $filenames);
            }
        }

        // return the updated or newly added article
        if ($position->save()) {
            Helpers::LogActivity($position->id, 'MASTERLIST - POSITIONS', 'UPDATED POSITIONS DATA');

            $positions = DB::table('positions')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'positions.id', 
                    'positions.code',
                    'positions.department_id',
                    'positions.subunit_id',
                    'positions.location_id',
                    'positions.jobband_id',
                    'positions.position_name',
                    'positions.status',
                    'positions.status_description',
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
                    'locations.location_name',
                    'jobbands.jobband_name',
                    'jobbands.order'
                ])
                ->where('positions.id', '=', $position->id)
                ->get();

            return PositionResources::collection($positions);
            // return new PositionResources($position);
        }
    }

    public function destroy($id)
    {
        $position = Position::findOrFail($id);

        if ($position->delete()) {
            Helpers::LogActivity($position->id, 'MASTERLIST - POSITIONS', 'DESTROY POSITIONS DATA');
            return new PositionResources($position);
        }
    }

    public function export() 
    {
        $query = DB::table('positions')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                'positions.id', 
                'positions.code',
                'positions.department_id',
                'positions.subunit_id',
                'positions.location_id',
                'positions.jobband_id',
                'positions.position_name',
                'positions.status',
                'positions.status_description',
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
                'locations.location_name',
                'jobbands.jobband_name',
                'jobbands.order'
            ])
            ->orderBy('positions.id', 'desc');
        $filename = 'positions-exportall.xlsx';
        $position_export = new PositionsExport($query);
        $position_export->store('public/files/'.$filename);
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

            $query = DB::table('positions')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'positions.id', 
                    'positions.code',
                    'positions.department_id',
                    'positions.subunit_id',
                    'positions.location_id',
                    'positions.jobband_id',
                    'positions.position_name',
                    'positions.status',
                    'positions.status_description',
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
                    'locations.location_name',
                    'jobbands.jobband_name',
                    'jobbands.order'
                ])
                ->whereBetween('positions.created_at', [$dateFrom, $dateTo])
                ->orderBy('positions.id', 'desc');

            $count = $query->count();
            $filename = 'positions-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $position_export = new PositionsExport($query);
                $position_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'positions.position_name' : 'positions.created_at';            

            $positions = DB::table('positions')
                ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
                ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
                ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
                ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
                ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
                ->select([
                    'positions.id', 
                    'positions.code',
                    'positions.department_id',
                    'positions.subunit_id',
                    'positions.location_id',
                    'positions.jobband_id',
                    'positions.position_name',
                    'positions.status',
                    'positions.status_description',
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
                    'locations.location_name',
                    'jobbands.jobband_name',
                    'jobbands.order'
                ])
                ->orderBy($field, $sort)
                ->paginate(15);
                
            return PositionResources::collection($positions);
        }
    }

    public function getPositions() {
        $positions = [];
        $query = DB::table('positions')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                'positions.id', 
                'positions.code',
                'positions.department_id',
                'positions.subunit_id',
                'positions.location_id',
                'positions.jobband_id',
                'positions.position_name',
                'positions.status',
                'positions.status_description',
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
                'locations.location_name',
                'jobbands.jobband_name',
                'jobbands.order'
            ])
            ->where('positions.status', '!=', 'inactive');

            if (Helpers::checkPermission('agency_only')) {
                $positions = $query->where('positions.team', '=', Helpers::loggedInUser()->team);
            }

            $positions = $query->get()
            ->map(function ($positions) {
                $positions->full_position = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team .' | '.$positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                $positions->superior_name = $positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                return $positions;
            });
        // return new PositionResources::collection($positions);
        return response()->json($positions);
    }

    public function getPosition($id) {
        $positions = [];
        $positions = DB::table('positions')
            ->leftJoin('departments', 'positions.department_id', '=', 'departments.id')
            ->leftJoin('subunits', 'positions.subunit_id', '=', 'subunits.id')
            ->leftJoin('locations', 'positions.location_id', '=', 'locations.id')
            ->leftJoin('jobbands', 'positions.jobband_id', '=', 'jobbands.id')
            ->leftJoin('employees as superior', 'positions.superior', '=', 'superior.id')
            ->select([
                'positions.id', 
                'positions.code',
                'positions.department_id',
                'positions.subunit_id',
                'positions.location_id',
                'positions.jobband_id',
                'positions.position_name',
                'positions.status',
                'positions.status_description',
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
                'locations.location_name',
                'jobbands.jobband_name',
                'jobbands.order'
            ])
            ->where('positions.id', '=', $id)
            ->get()
            ->map(function ($positions) {
                $positions->full_position = $positions->position_name.' | '.$positions->subunit_name;
                $positions->superior_name = $positions->s_first_name.' '.$positions->s_middle_name.' '.$positions->s_last_name;
                return $positions;
            });
        // return new PositionResources::collection($positions);
        return response()->json($positions);
    }

    public function getPositionsKPI() {
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
                    'positions.status',
                    'positions.status_description',
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
                ])
                ->where('positions.superior', '=', $requestor_id);
                // ->where('positions.department_id', '=', $department_id);

            if ($order->subunit_bound) {
                $positions = $query
                    // ->where('positions.subunit_id', '=', $subunit_id)
                    ->where('jobbands.order', '>', $order->order)
                    ->get()
                    ->map(function ($positions) {
                        $positions->position_name = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team;
                        return $positions;
                    });
            } else {
                $positions = $query
                    ->where('jobbands.order', '>', $order->order)
                    ->get()
                    ->map(function ($positions) {
                        $positions->position_name = $positions->position_name.' | '.$positions->subunit_name.' | '.$positions->team;
                        return $positions;
                    });
            }
        }

        return response()->json($positions);
    }

    public function getSuperiors(Request $request) {
        $department_id = $request->input('department_id');
        $subunit_id = $request->input('subunit_id');
        $order = $request->input('order');

        $positions = [];
        $positions = DB::table('positions')
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
                'positions.status',
                'positions.status_description',
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
            ])
            ->where('jobbands.order', '<', $order)
            ->get();
        // return new PositionResources::collection($positions);
        return response()->json($positions);
    }
}
