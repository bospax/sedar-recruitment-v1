<?php

namespace App\Http\Controllers;

use App\Exports\HonorariesExport;
use App\Http\Requests\HonoraryRequest;
use App\Http\Resources\Honorary as HonoraryResources;
use App\Models\Honorary;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HonoraryController extends Controller
{
    public function index()
    {
        $honoraries = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'honorary_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $honoraries = DB::table('honoraries')->select(['id', 'honorary_name', 'created_at'])
                    ->where('honorary_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $honoraries = DB::table('honoraries')->select(['id', 'honorary_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return HonoraryResources::collection($honoraries);
    }

    public function show($id)
    {
        $honorary = Honorary::findOrFail($id);
        return new HonoraryResources($honorary);
    }

    public function store(HonoraryRequest $request)
    {
        $honorary = new Honorary();
        $honorary->honorary_name = $request->input('honorary_name');

        if ($honorary->save()) {
            return new HonoraryResources($honorary);
        }
    }

    public function update(HonoraryRequest $request, $id)
    {
        $honorary = Honorary::findOrFail($id);
        $honorary->honorary_name = $request->input('honorary_name');

        // return the updated or newly added article
        if ($honorary->save()) {
            return new HonoraryResources($honorary);
        }
    }

    public function destroy($id)
    {
        $honorary = Honorary::findOrFail($id);

        if ($honorary->delete()) {
            return new HonoraryResources($honorary);
        }
    }

    public function export() 
    {
        $query = DB::table('honoraries')->select(['id', 'honorary_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'honoraries-exportall.xlsx';
        $honorary_export = new HonorariesExport($query);
        $honorary_export->store('public/files/'.$filename);
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

            $query = DB::table('honoraries')->select(['id', 'honorary_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'honoraries-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $honorary_export = new HonorariesExport($query);
                $honorary_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'honorary_name' : 'created_at';            

            $honoraries = DB::table('honoraries')->select(['id', 'honorary_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return HonoraryResources::collection($honoraries);
        }
    }
}
