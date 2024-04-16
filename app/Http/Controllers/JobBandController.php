<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\JobBandsExport;
use App\Http\Requests\JobBandRequest;
use App\Http\Resources\JobBand as JobBandResources;
use App\Models\JobBand;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Http\Request;

class JobBandController extends Controller
{
    public function index()
    {
        $jobbands = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        // if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
        //     $field = ($field == 'name') ? 'jobband_name' : 'created_at';   

        //     if (!empty($keyword)) {
        //         $value = '%'.$keyword.'%';
        //         $jobbands = DB::table('jobbands')->select(['id', 'jobband_name', 'order', 'created_at'])
        //             ->where('jobband_name', 'LIKE', $value)
        //             ->orderBy($field, $sort)
        //             ->paginate(100);
        //     } else {
        //         $jobbands = DB::table('jobbands')->select(['id', 'jobband_name', 'order', 'created_at'])
        //             ->orderBy($field, $sort)
        //             ->paginate(100);
        //     }
        // }

        $jobbands = DB::table('jobbands')
            ->select([
                'id', 
                'code', 
                'jobband_name', 
                'order', 
                'created_at',
                'status',
                'status_description',
            ])
            // ->whereNull('jobbands.deleted_at')
            ->orderBy('order', 'asc')
            ->paginate(15);

        return JobBandResources::collection($jobbands);
    }

    public function show($id)
    {
        $jobband = JobBand::findOrFail($id);
        return new JobBandResources($jobband);
    }

    public function store(JobBandRequest $request)
    {
        $faker = Faker::create();

        $last_row = DB::table('jobbands')->latest('order')->first();
        
        $jobband = new JobBand();
        $jobband->code = Helpers::generateCodeNewVersion('jobbands', 'JB');
        $jobband->jobband_name = $request->input('jobband_name');
        $jobband->status = 'active';
        $jobband->status_description = 'ACTIVE';
        $jobband->order = (!empty($last_row)) ? $last_row->order + 1 : 1;

        if ($jobband->save()) {
            Helpers::LogActivity($jobband->id, 'MASTERLIST - JOB BAND', 'ADDED NEW JOB BAND DATA');
            return new JobBandResources($jobband);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'jobband_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u', 'unique:jobbands,jobband_name,'.$id]
        ]);
        
        $jobband = JobBand::findOrFail($id);
        $jobband->jobband_name = $request->input('jobband_name');

        // return the updated or newly added article
        if ($jobband->save()) {
            Helpers::LogActivity($jobband->id, 'MASTERLIST - JOB BAND', 'UPDATED JOB BAND DATA');
            return new JobBandResources($jobband);
        }
    }

    public function destroy($id)
    {
        $jobband = JobBand::findOrFail($id);

        if ($jobband->delete()) {
            Helpers::LogActivity($jobband->id, 'MASTERLIST - JOB BAND', 'DELETED JOB BAND DATA');
            return new JobBandResources($jobband);
        }
    }

    public function export() 
    {
        $query = DB::table('jobbands')
            ->select([
                'id', 
                'code', 
                'jobband_name', 
                'order', 
                'created_at',
                'status',
                'status_description',
            ])
            ->orderBy('id', 'desc');
        $filename = 'jobbands-exportall.xlsx';
        $jobband_export = new JobBandsExport($query);
        $jobband_export->store('public/files/'.$filename);
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

            $query = DB::table('jobbands')
                ->select([
                    'id', 
                    'code', 
                    'jobband_name', 
                    'order', 
                    'created_at',
                    'status',
                    'status_description',
                ])
                ->whereBetween('created_at', [$dateFrom, $dateTo])
                ->orderBy('id', 'desc');

            $count = $query->count();
            $filename = 'jobbands-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $jobband_export = new JobBandsExport($query);
                $jobband_export->store('public/files/'.$filename);
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
            $field = ($field == 'name') ? 'jobband_name' : 'created_at';            

            $jobbands = DB::table('jobbands')
                ->select(['id', 'code', 'jobband_name', 'created_at'])
                ->orderBy($field, $sort)
                ->paginate(15);
            return JobBandResources::collection($jobbands);
        }
    }

    public function changeOrder()
    {
        $jobbands = request('jobbands');

        foreach ($jobbands as $jobband) {
            JobBand::find($jobband['id'])->update(['order' => $jobband['order']]);
        }

        return response()->json($jobbands);
    }

    public function getJobBands()
    {
        $jobbands = DB::table('jobbands')
            ->select([
                'id', 
                'code', 
                'jobband_name', 
                'order', 
                'created_at',
                'status',
                'status_description',
            ])
            // ->whereNull('jobbands.deleted_at')
            ->where('status', '!=', 'inactive')
            ->orderBy('order', 'asc')
            ->get();

        return JobBandResources::collection($jobbands);
    }
}
