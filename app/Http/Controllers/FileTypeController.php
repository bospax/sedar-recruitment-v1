<?php

namespace App\Http\Controllers;

use App\Exports\FileTypesExport;
use App\Http\Requests\FileTypeRequest;
use App\Http\Resources\FileType as FileTypeResources;
use App\Models\FileType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FileTypeController extends Controller
{
    public function index()
    {
        $filetypes = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'filetype_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $filetypes = DB::table('filetypes')->select(['id', 'filetype_name', 'created_at'])
                    ->where('filetype_name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $filetypes = DB::table('filetypes')->select(['id', 'filetype_name', 'created_at'])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return FileTypeResources::collection($filetypes);
    }

    public function show($id)
    {
        $filetype = FileType::findOrFail($id);
        return new FileTypeResources($filetype);
    }

    public function store(FileTypeRequest $request)
    {
        $filetype = new FileType();
        $filetype->filetype_name = $request->input('filetype_name');

        if ($filetype->save()) {
            return new FileTypeResources($filetype);
        }
    }

    public function update(FileTypeRequest $request, $id)
    {
        $filetype = FileType::findOrFail($id);
        $filetype->filetype_name = $request->input('filetype_name');

        // return the updated or newly added article
        if ($filetype->save()) {
            return new FileTypeResources($filetype);
        }
    }

    public function destroy($id)
    {
        $filetype = FileType::findOrFail($id);

        if ($filetype->delete()) {
            return new FileTypeResources($filetype);
        }
    }

    public function export() 
    {
        $query = DB::table('filetypes')->select(['id', 'filetype_name', 'created_at'])->orderBy('id', 'desc');
        $filename = 'filetypes-exportall.xlsx';
        $filetype_export = new FileTypesExport($query);
        $filetype_export->store('public/files/'.$filename);
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

            $query = DB::table('filetypes')->select(['id', 'filetype_name', 'created_at'])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'filetypes-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $filetype_export = new FileTypesExport($query);
                $filetype_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'filetype_name' : 'created_at';            

            $filetypes = DB::table('filetypes')->select(['id', 'filetype_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return FileTypeResources::collection($filetypes);
        }
    }
}
