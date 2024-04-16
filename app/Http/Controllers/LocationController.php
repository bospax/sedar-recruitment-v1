<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\LocationsExport;
use App\Http\Requests\LocationRequest;
use App\Http\Resources\Location as LocationResources;
use App\Http\Resources\LocationWithStatus;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function index()
    {
        $locations = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'location_name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $locations = DB::table('locations')
                    ->select([
                        'id', 
                        'code', 
                        'location_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    ->where('location_name', 'LIKE', $value)
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $locations = DB::table('locations')
                    ->select([
                        'id', 
                        'code', 
                        'location_name', 
                        'status',
                        'status_description',
                        'created_at'
                    ])
                    // ->whereNull('deleted_at')
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return LocationResources::collection($locations);
    }

    public function show($id)
    {
        $location = Location::findOrFail($id);
        return new LocationResources($location);
    }

    public function store(LocationRequest $request)
    {
        Helpers::checkDuplicate('locations', 'location_name', $request->input('location_name'));
         
        $location = new Location();
        $location->code = Helpers::generateCodeNewVersion('locations', 'LOC');
        $location->location_name = $request->input('location_name');
        $location->status = 'active';
        $location->status_description = 'ACTIVE';

        if ($location->save()) {
            Helpers::LogActivity($location->id, 'MASTERLIST - LOCATION', 'ADDED NEW LOCATION DATA');
            return new LocationResources($location);
        }
    }

    public function update(LocationRequest $request, $id)
    {
        Helpers::checkDuplicate('locations', 'location_name', $request->input('location_name'), $id);

        $location = Location::findOrFail($id);
        $location->location_name = $request->input('location_name');

        // return the updated or newly added article
        if ($location->save()) {
            Helpers::LogActivity($location->id, 'MASTERLIST - LOCATION', 'UPDATED LOCATION DATA');
            return new LocationResources($location);
        }
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        if ($location->delete()) {
            Helpers::LogActivity($location->id, 'MASTERLIST - LOCATION', 'DELETED LOCATION DATA');
            return new LocationResources($location);
        }
    }

    public function export() 
    {
        $query = DB::table('locations')
            ->select([
                'id', 
                'code', 
                'location_name', 
                'status',
                'status_description',
                'created_at'
            ])
            ->orderBy('id', 'desc');
        $filename = 'locations-exportall.xlsx';
        $location_export = new LocationsExport($query);
        $location_export->store('public/files/'.$filename);
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

            $query = DB::table('locations')
                ->select([
                    'id', 
                    'code', 
                    'location_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'locations-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $location_export = new LocationsExport($query);
                $location_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'location_name' : 'created_at';            

            $locations = DB::table('locations')
                ->select([
                    'id', 
                    'code', 
                    'location_name', 
                    'status',
                    'status_description',
                    'created_at'
                ])
                ->orderBy($field, $sort)->paginate(15);
            return LocationResources::collection($locations);
        }
    }

    public function getLocations() {
        $locations = DB::table('locations')
            ->select([
                'id', 
                'code', 
                'location_name', 
                'status',
                'status_description',
                'created_at'
            ])
            // ->whereNull('deleted_at')
            ->where('status', '!=', 'inactive')
            ->orderBy('location_name', 'desc')
            ->get();
            
        return LocationResources::collection($locations);
    }
}
