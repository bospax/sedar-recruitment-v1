<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\DivisionCategoriesExport;
use App\Http\Requests\DivisionCategoryRequest;
use App\Http\Resources\DivisionCategory as DivisionCategoryResources;
use App\Models\DivisionCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DivisionCategoryController extends Controller
{
    public function index()
    {
        $division_categories = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'category_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $division_categories = DB::table('division_categories')
                    ->select([
                        'id', 
                        'code', 
                        'category_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    ->where('category_name', 'LIKE', $value)
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $division_categories = DB::table('division_categories')
                    ->select([
                        'id', 
                        'code', 
                        'category_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return DivisionCategoryResources::collection($division_categories);
    }

    public function show($id)
    {
        $division_category = DivisionCategory::findOrFail($id);
        return new DivisionCategoryResources($division_category);
    }

    public function store(DivisionCategoryRequest $request)
    {
        Helpers::checkDuplicate('division_categories', 'category_name', $request->input('category_name'));

        $division_category = new DivisionCategory();
        $division_category->code = Helpers::generateCodeNewVersion('division_categories', 'DC');
        $division_category->category_name = $request->input('category_name');
        $division_category->status = 'active';
        $division_category->status_description = 'ACTIVE';

        if ($division_category->save()) {
            Helpers::LogActivity($division_category->id, 'MASTERLIST - DIVISION CATEGORY', 'ADDED NEW DIVISION CATEGORY DATA');
            return new DivisionCategoryResources($division_category);
        }
    }

    public function update(DivisionCategoryRequest $request, $id)
    {
        Helpers::checkDuplicate('division_categories', 'category_name', $request->input('category_name'), $id);

        $division_category = DivisionCategory::findOrFail($id);
        $division_category->category_name = $request->input('category_name');

        // return the updated or newly added article
        if ($division_category->save()) {
            Helpers::LogActivity($division_category->id, 'MASTERLIST - DIVISION CATEGORY', 'UPDATED DIVISION CATEGORY DATA');
            return new DivisionCategoryResources($division_category);
        }
    }

    public function destroy($id)
    {
        $division_category = DivisionCategory::findOrFail($id);

        if ($division_category->delete()) {
            Helpers::LogActivity($division_category->id, 'MASTERLIST - DIVISION CATEGORY', 'DELETED DIVISION CATEGORY DATA');
            return new DivisionCategoryResources($division_category);
        }
    }

    public function export() 
    {
        $query = DB::table('division_categories')
            ->select([
                'id', 
                'code', 
                'category_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->orderBy('id', 'desc');
        $filename = 'division_categories-exportall.xlsx';
        $division_category_export = new DivisionCategoriesExport($query);
        $division_category_export->store('public/files/'.$filename);
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

            $query = DB::table('division_categories')
                ->select([
                    'id', 
                    'code', 
                    'category_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'division_categories-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $division_category_export = new DivisionCategoriesExport($query);
                $division_category_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'category_name' : 'created_at';            

            $division_categories = DB::table('division_categories')->select(['id', 'code', 'category_name', 'created_at'])->orderBy($field, $sort)->paginate(15);
            return DivisionCategoryResources::collection($division_categories);
        }
    }

    public function getCategories()
    {
        $division_categories = DB::table('division_categories')
            ->select([
                'id', 
                'code', 
                'category_name', 
                'status',
                'status_description',
                'created_at'
            ])
            // ->whereNull('deleted_at')
            ->where('status', '!=', 'inactive')
            ->orderBy('category_name', 'desc')
            ->get();

        return DivisionCategoryResources::collection($division_categories);
    }
}
