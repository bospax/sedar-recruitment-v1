<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobHistory as JobHistoryResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobHistoryController extends Controller
{
    public function getJobHistory($id) {
        $employee_history = DB::table('job_history')
            ->leftJoin('employees', 'job_history.employee_id', '=', 'employees.id')
            ->select([
                'job_history.id',
                'job_history.employee_id',
                'job_history.reference_number',
                'job_history.record_id',
                'job_history.record_type',
                'job_history.details',
                'job_history.created_at',
                'employees.prefix_id',
                'employees.id_number',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                'employees.gender',
                'employees.image',
            ])
            ->where('job_history.employee_id', '=', $id)
            ->orderBy('job_history.id', 'asc')
            ->get();

        return JobHistoryResource::collection($employee_history);
    }
}
