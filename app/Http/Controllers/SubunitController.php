<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\SubunitsExport;
use App\Http\Requests\SubunitRequest;
use App\Http\Resources\Subunit as SubunitResources;
use App\Models\Subunit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubunitController extends Controller
{
    public function index()
    {
        $subunits = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'subunit_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $subunits = DB::table('subunits')
                    ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
                    ->select([
                        'subunits.id', 
                        'subunits.code',
                        'subunits.department_id', 
                        'subunits.subunit_name', 
                        'subunits.status',
                        'subunits.status_description',
                        'subunits.created_at',
                        'departments.department_name'
                    ])
                    ->where('subunits.subunit_name', 'LIKE', $value)
                    // ->whereNull('subunits.deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $subunits = DB::table('subunits')
                    ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
                    ->select([
                        'subunits.id', 
                        'subunits.code',
                        'subunits.department_id', 
                        'subunits.subunit_name', 
                        'subunits.status',
                        'subunits.status_description',
                        'subunits.created_at',
                        'departments.department_name'
                    ])
                    // ->whereNull('subunits.deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return SubunitResources::collection($subunits);
    }

    public function show($id)
    {
        $subunit = Subunit::findOrFail($id);
        return new SubunitResources($subunit);
    }

    public function store(SubunitRequest $request)
    {
        $subunit = new Subunit();
        $subunit->code = Helpers::generateCodeNewVersion('subunits', 'SU');
        $subunit->department_id = $request->input('department_id');
        $subunit->subunit_name = $request->input('subunit_name');
        $subunit->status = 'active';
        $subunit->status_description = 'ACTIVE';

        if ($subunit->save()) {
            Helpers::LogActivity($subunit->id, 'MASTERLIST - SUBUNIT', 'ADDED NEW SUBUNIT DATA');
            return new SubunitResources($subunit);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'subunit_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u', 'unique:subunits,subunit_name,'.$id]
        ]);

        $subunit = Subunit::findOrFail($id);
        $subunit->department_id = $request->input('department_id');
        $subunit->subunit_name = $request->input('subunit_name');

        // return the updated or newly added article
        if ($subunit->save()) {
            Helpers::LogActivity($subunit->id, 'MASTERLIST - SUBUNIT', 'UPDATED SUBUNIT DATA');

            $subunits = DB::table('subunits')
                ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
                ->select([
                    'subunits.id', 
                    'subunits.code',
                    'subunits.department_id', 
                    'subunits.subunit_name', 
                    'subunits.status',
                    'subunits.status_description',
                    'subunits.created_at',
                    'departments.department_name'
                ])
                ->where('subunits.id', '=', $subunit->id)
                ->get();
            
            return SubunitResources::collection($subunits);
        }
    }

    public function destroy($id)
    {
        $subunit = Subunit::findOrFail($id);

        if ($subunit->delete()) {
            Helpers::LogActivity($subunit->id, 'MASTERLIST - SUBUNIT', 'DELETED SUBUNIT DATA');
            return new SubunitResources($subunit);
        }
    }

    public function export() 
    {
        $query = DB::table('subunits')
                ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
                ->select([
                    'subunits.id', 
                    'subunits.code',
                    'subunits.department_id', 
                    'subunits.subunit_name', 
                    'subunits.status',
                    'subunits.status_description',
                    'subunits.created_at',
                    'departments.department_name'
                ])
                ->orderBy('subunits.id', 'desc');
        $filename = 'subunits-exportall.xlsx';
        $subunit_export = new SubunitsExport($query);
        $subunit_export->store('public/files/'.$filename);
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

            $query = DB::table('subunits')
                ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
                ->select([
                    'subunits.id', 
                    'subunits.code',
                    'subunits.department_id', 
                    'subunits.subunit_name', 
                    'subunits.status',
                    'subunits.status_description',
                    'subunits.created_at',
                    'departments.department_name'
                ])
                ->whereBetween('subunits.created_at', [$dateFrom, $dateTo])
                ->orderBy('subunits.id', 'desc');

            $count = $query->count();
            $filename = 'subunits-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $subunit_export = new SubunitsExport($query);
                $subunit_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'subunit_name' : 'created_at';            

            $subunits = DB::table('subunits')->select(['id', 'code', 'department_id', 'subunit_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return SubunitResources::collection($subunits);
        }
    }

    public function fetchSubunit($department_id)
    {
        $subunits = DB::table('subunits')
            ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
            ->select([
                'subunits.id', 
                'subunits.code',
                'subunits.department_id', 
                'subunits.subunit_name', 
                'subunits.status',
                'subunits.status_description',
                'subunits.created_at',
                'departments.department_name'
            ])
            // ->whereNull('subunits.deleted_at')
            ->where('department_id', '=', $department_id)
            ->get();

        return SubunitResources::collection($subunits);
    }

    public function fetchAllSubunit() 
    {
        $subunits = DB::table('subunits')
            ->select([
                'id', 
                'code',
                'department_id', 
                'subunit_name', 
                'status',
                'status_description',
                'created_at',
            ])
            // ->whereNull('subunits.deleted_at')
            ->get();

        return response()->json($subunits);
    }

    public function fetchAllAvailableSubunit(Request $request) 
    {
        $form_type = $request->input('form_type');

        $subunits = DB::table('subunits')
            ->leftJoin('departments', 'subunits.department_id', '=', 'departments.id')
            ->select([
                'subunits.id', 
                'subunits.code',
                'subunits.department_id', 
                'subunits.subunit_name', 
                'subunits.status',
                'subunits.status_description',
                'subunits.created_at',
                'departments.department_name',
            ])
            ->whereNotExists(function ($query) use ($form_type) {
                $query->select(DB::raw(1))
                ->from('forms')
                ->whereRaw('subunits.id = forms.subunit_id')
                ->where('forms.form_type', '=', $form_type);
            })
            // ->whereNull('subunits.deleted_at')
            ->get()
            ->map(function ($subunits) {
                $subunits->subunit_name = $subunits->department_name.' - '.$subunits->subunit_name;
                return $subunits;
            });

        return response()->json($subunits);
    }

    public function fetchAllAvailableSubunitReceiver(Request $request) 
    {
        $form_type = $request->input('form_type');

        $subunits = DB::table('subunits')
            ->select([
                'subunits.id', 
                'subunits.code',
                'subunits.department_id', 
                'subunits.subunit_name', 
                'subunits.status',
                'subunits.status_description',
                'subunits.created_at',
            ])
            ->whereNotExists(function ($query) use ($form_type) {
                $query->select(DB::raw(1))
                ->from('receivers')
                ->whereRaw('subunits.id = receivers.subunit_id')
                ->where('receivers.form_type', '=', $form_type);
            })
            // ->whereNull('subunits.deleted_at')
            ->get();

        return response()->json($subunits);
    }
}
