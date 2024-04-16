<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Exports\JobRatesExport;
use App\Http\Requests\JobRateRequest;
use App\Http\Resources\JobRate as JobRateResources;
use App\Models\JobRate;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class JobRateController extends Controller
{
    public function index()
    {
        $jobrates = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'jobrate_name' : 'created_at';

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $jobrates = DB::table('jobrates')
                    ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
                    ->select([
                        'jobrates.id', 
                        'jobrates.code',
                        'jobrates.position_id', 
                        'jobrates.position_title',
                        'jobrates.job_level', 
                        'jobrates.job_rate', 
                        'jobrates.allowance',
                        'jobrates.salary_structure', 
                        'jobrates.jobrate_name', 
                        'jobrates.status',
                        'jobrates.status_description',
                        'jobrates.created_at',
                        'positions.position_name'
                    ])
                    ->where('jobrates.position_title', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $jobrates = DB::table('jobrates')
                    ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
                    ->select([
                        'jobrates.id', 
                        'jobrates.code',
                        'jobrates.position_id', 
                        'jobrates.position_title',
                        'jobrates.job_level', 
                        'jobrates.job_rate', 
                        'jobrates.allowance',
                        'jobrates.salary_structure', 
                        'jobrates.jobrate_name', 
                        'jobrates.status',
                        'jobrates.status_description',
                        'jobrates.created_at',
                        'positions.position_name'
                    ])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return JobRateResources::collection($jobrates);
    }

    public function show($id)
    {
        $jobrate = DB::table('jobrates')
            ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
            ->select([
                'jobrates.id', 
                'jobrates.code',
                'jobrates.position_id', 
                'jobrates.position_title',
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.allowance',
                'jobrates.salary_structure', 
                'jobrates.jobrate_name', 
                'jobrates.status',
                'jobrates.status_description',
                'jobrates.created_at',
                'positions.position_name'
            ])
            ->where('jobrates.id', '=', $id)
            ->get();

        return JobRateResources::collection($jobrate);
        // return new JobRateResources($jobrate);
    }

    public function store(JobRateRequest $request)
    {
        $jobrate = new JobRate();

        $duplicate = DB::table('jobrates')->select(['id', 'position_id', 'job_level', 'job_rate', 'salary_structure', 'jobrate_name', 'created_at'])
            ->where('position_title', '=', $request->input('position_title'))
            ->where('job_level', '=', $request->input('job_level'))
            ->where('salary_structure', '=', $request->input('salary_structure'))
            ->where('allowance', '=', $request->input('allowance'))
            ->where('job_rate', '=', $request->input('job_rate'))
            ->where('jobrate_name', '=', $request->input('job_rate'))
            ->get();
        
        if ($duplicate->isNotEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Job Rate duplication is not allowed.'
            ]);
        }

        $rate = trim($request->input('job_rate'));
        $rate = str_replace(',', '', $rate);
        $rate = number_format($rate, 2);

        $jobrate->code = Helpers::generateCodeNewVersion('jobrates', 'RT');
        $jobrate->position_title = $request->input('position_title');
        $jobrate->job_level = $request->input('job_level');
        $jobrate->job_rate = $rate;
        $jobrate->allowance = $request->input('allowance');
        $jobrate->salary_structure = $request->input('salary_structure');
        $jobrate->jobrate_name = $request->input('jobrate_name');
        $jobrate->status = 'active';
        $jobrate->status_description = 'ACTIVE';

        if ($jobrate->save()) {
            Helpers::LogActivity($jobrate->id, 'MASTERLIST - JOB RATE', 'ADDED NEW JOB RATE DATA');
            return new JobRateResources($jobrate);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'jobrate_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u']
        ]);

        $duplicate = DB::table('jobrates')->select(['id', 'position_id', 'job_level', 'job_rate', 'salary_structure', 'jobrate_name', 'created_at'])
            ->where('position_title', '=', $request->input('position_title'))
            ->where('job_level', '=', $request->input('job_level'))
            ->where('salary_structure', '=', $request->input('salary_structure'))
            ->where('job_rate', '=', $request->input('job_rate'))
            ->where('allowance', '=', $request->input('allowance'))
            ->where('jobrate_name', '=', $request->input('job_rate'))
            ->where('id', '!=', $id)
            ->get();
        
        if ($duplicate->isNotEmpty()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Job Rate duplication is not allowed.'
            ]);
        }

        $rate = trim($request->input('job_rate'));
        $rate = str_replace(',', '', $rate);
        $rate = number_format($rate, 2);

        $jobrate = JobRate::findOrFail($id);
        $jobrate->position_title = $request->input('position_title');
        $jobrate->job_level = $request->input('job_level');
        $jobrate->job_rate = $rate;
        $jobrate->allowance = $request->input('allowance');
        $jobrate->salary_structure = $request->input('salary_structure');
        $jobrate->jobrate_name = $request->input('jobrate_name');

        // return the updated or newly added article
        if ($jobrate->save()) {
            Helpers::LogActivity($jobrate->id, 'MASTERLIST - JOB RATE', 'UPDATED JOB RATE DATA');

            $jobrates = DB::table('jobrates')
                ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
                ->select([
                    'jobrates.id', 
                    'jobrates.code',
                    'jobrates.position_id', 
                    'jobrates.position_title',
                    'jobrates.job_level', 
                    'jobrates.job_rate', 
                    'jobrates.allowance',
                    'jobrates.salary_structure', 
                    'jobrates.jobrate_name', 
                    'jobrates.status',
                    'jobrates.status_description',
                    'jobrates.created_at',
                    'positions.position_name'
                ])
                ->where('jobrates.id', '=', $jobrate->id)
                ->get();

            return JobRateResources::collection($jobrates);
        }
    }

    public function destroy($id)
    {
        $jobrate = JobRate::findOrFail($id);

        if ($jobrate->delete()) {
            Helpers::LogActivity($jobrate->id, 'MASTERLIST - JOB RATE', 'DELETED JOB RATE DATA');
            return new JobRateResources($jobrate);
        }
    }

    public function export() 
    {
        $query = DB::table('jobrates')
            ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
            ->select([
                'jobrates.id', 
                'jobrates.code',
                'jobrates.position_id', 
                'jobrates.position_title',
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.allowance',
                'jobrates.salary_structure', 
                'jobrates.jobrate_name', 
                'jobrates.status',
                'jobrates.status_description',
                'jobrates.created_at',
                'positions.position_name'
            ])
            ->orderBy('jobrates.id', 'desc');
        $filename = 'jobrates-exportall.xlsx';
        $jobrate_export = new JobRatesExport($query);
        $jobrate_export->store('public/files/'.$filename);
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

            $query = DB::table('jobrates')
                ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
                ->select([
                    'jobrates.id', 
                    'jobrates.code',
                    'jobrates.position_id', 
                    'jobrates.position_title',
                    'jobrates.job_level', 
                    'jobrates.job_rate', 
                    'jobrates.allowance',
                    'jobrates.salary_structure', 
                    'jobrates.jobrate_name', 
                    'jobrates.status',
                    'jobrates.status_description',
                    'jobrates.created_at',
                    'positions.position_name'
                ])
                ->whereBetween('jobrates.created_at', [$dateFrom, $dateTo])
                ->orderBy('jobrates.id', 'desc');

            $count = $query->count();
            $filename = 'jobrates-export.xlsx';
            $link = ($count) ? '/storage/files/'.$filename : null;

            if ($count) {
                $jobrate_export = new JobRatesExport($query);
                $jobrate_export->store('public/files/'.$filename);
            }

            return response()->json([
                'link' => $link,
                'count' => $count
            ]);
        }
    }

    public function getJobRates()
    {
        $jobrates = DB::table('jobrates')
            ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
            ->select([
                'jobrates.id', 
                'jobrates.code',
                'jobrates.position_id', 
                'jobrates.position_title',
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.allowance',
                'jobrates.salary_structure', 
                'jobrates.jobrate_name', 
                'jobrates.status',
                'jobrates.status_description',
                'jobrates.created_at',
                'positions.position_name'
            ])
            ->where('jobrates.status', '!=', 'inactive')
            ->get();

        return JobRateResources::collection($jobrates);
    }

    public function getJobRate($position_id)
    {
        $position = Position::findOrFail($position_id);
        $position_title = ($position) ? $position->position_name : '';

        $jobrate = DB::table('jobrates')
            ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
            ->select([
                'jobrates.id', 
                'jobrates.code',
                'jobrates.position_id', 
                'jobrates.position_title',
                'jobrates.job_level', 
                'jobrates.job_rate', 
                'jobrates.allowance',
                'jobrates.salary_structure', 
                'jobrates.jobrate_name', 
                'jobrates.status',
                'jobrates.status_description',
                'jobrates.created_at',
                'positions.position_name'
            ])
            ->where('jobrates.status', '!=', 'inactive')
            ->where('jobrates.position_title', '=', $position_title)
            ->get();

        return JobRateResources::collection($jobrate);
    }

    public function sortData() 
    {
        $field = request('field');
        $sort = request('sort');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'jobrates.jobrate_name' : 'jobrates.created_at';            

            $jobrates = DB::table('jobrates')
                ->leftJoin('positions', 'jobrates.position_id', '=', 'positions.id')
                ->select([
                    'jobrates.id', 
                    'jobrates.code',
                    'jobrates.position_id', 
                    'jobrates.position_title',
                    'jobrates.job_level', 
                    'jobrates.job_rate', 
                    'jobrates.allowance',
                    'jobrates.salary_structure', 
                    'jobrates.jobrate_name', 
                    'jobrates.status',
                    'jobrates.status_description',
                    'jobrates.created_at',
                    'positions.position_name'
                ])
                ->orderBy($field, $sort)->paginate(15);
                
            return JobRateResources::collection($jobrates);
        }
    }
}
