<?php

namespace App\Http\Controllers;

use App\Exports\DegreesExport;
use App\Http\Requests\DegreeRequest;
use App\Http\Resources\Degree as DegreeResources;
use App\Models\Degree;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DegreeController extends Controller
{
    public function index()
    {
        $degrees = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'degree_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $degrees = DB::table('degrees')->select(['id', 'degree_name', 'created_at'])
                    ->where('degree_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $degrees = DB::table('degrees')->select(['id', 'degree_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return DegreeResources::collection($degrees);
    }

    public function show($id)
    {
        $degree = Degree::findOrFail($id);
        return new DegreeResources($degree);
    }

    public function store(DegreeRequest $request)
    {
        $degree = new Degree();
        $degree->degree_name = $request->input('degree_name');

        if ($degree->save()) {
            return new DegreeResources($degree);
        }
    }

    public function update(DegreeRequest $request, $id)
    {
        $degree = Degree::findOrFail($id);
        $degree->degree_name = $request->input('degree_name');

        // return the updated or newly added article
        if ($degree->save()) {
            return new DegreeResources($degree);
        }
    }

    public function destroy($id)
    {
        $degree = Degree::findOrFail($id);

        if ($degree->delete()) {
            return new DegreeResources($degree);
        }
    }

    public function export() 
    {
        $query = DB::table('degrees')->select(['id', 'degree_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'degrees-exportall.xlsx';
        $degree_export = new DegreesExport($query);
        $degree_export->store('public/files/'.$filename);
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

            $query = DB::table('degrees')->select(['id', 'degree_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'degrees-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $degree_export = new DegreesExport($query);
                $degree_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'degree_name' : 'created_at';            

            $degrees = DB::table('degrees')->select(['id', 'degree_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return DegreeResources::collection($degrees);
        }
    }
}
