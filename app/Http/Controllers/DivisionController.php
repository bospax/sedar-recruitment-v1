<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\DivisionsExport;
use App\Http\Requests\DivisionRequest;
use App\Http\Resources\Division as DivisionResources;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DivisionController extends Controller
{
    public function index()
    {
        $divisions = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'division_name' : 'created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $divisions = DB::table('divisions')
                    ->select([
                        'id', 
                        'code', 
                        'division_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    ->where('division_name', 'LIKE', $value)
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $divisions = DB::table('divisions')
                    ->select([
                        'id', 
                        'code', 
                        'division_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return DivisionResources::collection($divisions);
    }

    public function show($id)
    {
        $division = Division::findOrFail($id);
        return new DivisionResources($division);
    }

    public function store(DivisionRequest $request)
    {
        Helpers::checkDuplicate('divisions', 'division_name', $request->input('division_name'));

        $division = new Division();
        $division->code = Helpers::generateCodeNewVersion('divisions', 'DI');
        $division->division_name = $request->input('division_name');
        $division->status = 'active';
        $division->status_description = 'ACTIVE';

        if ($division->save()) {
            Helpers::LogActivity($division->id, 'MASTERLIST - DIVISION', 'ADDED NEW DIVISION DATA');
            return new DivisionResources($division);
        }
    }

    public function update(DivisionRequest $request, $id)
    {
        Helpers::checkDuplicate('divisions', 'division_name', $request->input('division_name'), $id);
        
        $division = Division::findOrFail($id);
        $division->division_name = $request->input('division_name');

        // return the updated or newly added article
        if ($division->save()) {
            Helpers::LogActivity($division->id, 'MASTERLIST - DIVISION', 'UPDATED DIVISION DATA');
            return new DivisionResources($division);
        }
    }

    public function destroy($id)
    {
        $division = Division::findOrFail($id);

        if ($division->delete()) {
            Helpers::LogActivity($division->id, 'MASTERLIST - DIVISION', 'DELETED DIVISION DATA');
            return new DivisionResources($division);
        }
    }

    public function export() 
    {
        $query = DB::table('divisions')
            ->select([
                'id', 
                'code', 
                'division_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->orderBy('id', 'desc');
        $filename = 'divisions-exportall.xlsx';
        $division_export = new DivisionsExport($query);
        $division_export->store('public/files/'.$filename);
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

            $query = DB::table('divisions')
                ->select([
                    'id', 
                    'code', 
                    'division_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'divisions-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $division_export = new DivisionsExport($query);
                $division_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'division_name' : 'created_at';            

            $divisions = DB::table('divisions')
                ->select([
                    'id', 
                    'code', 
                    'division_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->orderBy($field, $sort)->paginate(15);
            return DivisionResources::collection($divisions);
        }
    }

    public function getDivisions() 
    {
        $divisions = DB::table('divisions')
            ->select([
                'id', 
                'code', 
                'division_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->where('status', '!=', 'inactive')
            ->orderBy('division_name', 'desc')
            ->get();
            
        return DivisionResources::collection($divisions);
    }
}
