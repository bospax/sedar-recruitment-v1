<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\TitlesExport;
use App\Http\Requests\TitleRequest;
use App\Http\Resources\Title as TitleResources;
use App\Http\Resources\TitleWithStatus;
use App\Models\Position;
use App\Models\Title;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TitleController extends Controller
{
    public function index()
    {
        $titles = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'title_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $titles = DB::table('titles')
                    ->select([
                        'id', 
                        'code', 
                        'title_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    ->where('title_name', 'LIKE', $value)
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $titles = DB::table('titles')
                    ->select([
                        'id', 
                        'code', 
                        'title_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return TitleResources::collection($titles);
    }

    public function show($id)
    {
        $title = Title::findOrFail($id);
        return new TitleResources($title);
    }

    public function store(TitleRequest $request)
    {
        Helpers::checkDuplicate('titles', 'title_name', $request->input('title_name'));
         
        $title = new Title();
        $title->code = Helpers::generateCodeNewVersion('titles', 'LOC');
        $title->title_name = $request->input('title_name');
        $title->status = 'active';
        $title->status_description = 'ACTIVE';

        if ($title->save()) {
            Helpers::LogActivity($title->id, 'MASTERLIST - LOCATION', 'ADDED NEW LOCATION DATA');
            return new TitleResources($title);
        }
    }

    public function update(TitleRequest $request, $id)
    {
        Helpers::checkDuplicate('titles', 'title_name', $request->input('title_name'), $id);

        $title = Title::findOrFail($id);

        $position = DB::table('positions')->where('position_name', '=', $title->title_name)->update(['position_name' => $request->input('title_name')]);
        $jobrate = DB::table('jobrates')->where('position_title', '=', $title->title_name)->update(['position_title' => $request->input('title_name')]);

        $title->title_name = $request->input('title_name');

        // return the updated or newly added article
        if ($title->save()) {
            Helpers::LogActivity($title->id, 'MASTERLIST - LOCATION', 'UPDATED LOCATION DATA');
            return new TitleResources($title);
        }
    }

    public function destroy($id)
    {
        $title = Title::findOrFail($id);

        if ($title->delete()) {
            Helpers::LogActivity($title->id, 'MASTERLIST - LOCATION', 'DELETED LOCATION DATA');
            return new TitleResources($title);
        }
    }

    public function export() 
    {
        $query = DB::table('titles')
            ->select([
                'id', 
                'code', 
                'title_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->orderBy('id', 'desc');
        $filename = 'titles-exportall.xlsx';
        $title_export = new TitlesExport($query);
        $title_export->store('public/files/'.$filename);
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

            $query = DB::table('titles')
                ->select([
                    'id', 
                    'code', 
                    'title_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'titles-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $title_export = new TitlesExport($query);
                $title_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'title_name' : 'created_at';            

            $titles = DB::table('titles')
                ->select([
                    'id', 
                    'code', 
                    'title_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->orderBy($field, $sort)->paginate(15);
            return TitleResources::collection($titles);
        }
    }

    public function getTitles() {
        $titles = DB::table('titles')
            ->select(['title_name'])
            ->where('status', '!=', 'inactive')
            ->orderBy('title_name', 'desc')
            ->get();

        $array = [];

        foreach ($titles as $title) {
            $array[] = $title->title_name;
        }
            
        return response()->json($array);
    }
}
