<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\AttainmentsExport;
use App\Http\Requests\AttainmentRequest;
use App\Http\Resources\Attainment as AttainmentResources;
use App\Models\Attainment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttainmentController extends Controller
{
    public function index()
    {
        $attainments = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'attainment_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $attainments = DB::table('attainments')->select(['id', 'attainment_name', 'created_at'])
                    ->where('attainment_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $attainments = DB::table('attainments')->select(['id', 'attainment_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return AttainmentResources::collection($attainments);
    }

    public function show($id)
    {
        $attainment = Attainment::findOrFail($id);
        return new AttainmentResources($attainment);
    }

    public function store(AttainmentRequest $request)
    {
        $attainment = new Attainment();
        $attainment->attainment_name = $request->input('attainment_name');

        if ($attainment->save()) {
            Helpers::LogActivity($attainment->id, 'EMPLOYEE MANAGEMENT', 'ADDED NEW EMPLOYEE DATA - ATTAINMENT SECTION');
            return new AttainmentResources($attainment);
        }
    }

    public function update(AttainmentRequest $request, $id)
    {
        $attainment = Attainment::findOrFail($id);
        $attainment->attainment_name = $request->input('attainment_name');

        // return the updated or newly added article
        if ($attainment->save()) {
            Helpers::LogActivity($attainment->id, 'EMPLOYEE MANAGEMENT', 'UPDATED EMPLOYEE DATA - ATTAINMENT SECTION');
            return new AttainmentResources($attainment);
        }
    }

    public function destroy($id)
    {
        $attainment = Attainment::findOrFail($id);

        if ($attainment->delete()) {
            Helpers::LogActivity($attainment->id, 'EMPLOYEE MANAGEMENT', 'DELETED EMPLOYEE DATA - ATTAINMENT SECTION');
            return new AttainmentResources($attainment);
        }
    }

    public function export() 
    {
        $query = DB::table('attainments')->select(['id', 'attainment_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'attainments-exportall.xlsx';
        $attainment_export = new AttainmentsExport($query);
        $attainment_export->store('public/files/'.$filename);
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

            $query = DB::table('attainments')->select(['id', 'attainment_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'attainments-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $attainment_export = new AttainmentsExport($query);
                $attainment_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'attainment_name' : 'created_at';            

            $attainments = DB::table('attainments')->select(['id', 'attainment_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return AttainmentResources::collection($attainments);
        }
    }
}
