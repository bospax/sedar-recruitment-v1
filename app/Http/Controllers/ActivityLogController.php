<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLog as ActivityLogResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $activity_logs = [];
        $keyword = request('keyword');
        
        if (!empty($keyword)) {
            $value = '%'.$keyword.'%';

            $activity_logs = DB::table('activity_logs')
                ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
                ->select([
                    'activity_logs.id',
                    'activity_logs.user_id',
                    'activity_logs.reference_id',
                    'activity_logs.module',
                    'activity_logs.activity',
                    'activity_logs.created_at',
                    'users.name',
                ])
                ->where('users.name', 'LIKE', $value)
                ->orderBy('activity_logs.created_at', 'desc')
                ->paginate(15);
        } else {
            $activity_logs = DB::table('activity_logs')
                ->leftJoin('users', 'activity_logs.user_id', '=', 'users.id')
                ->select([
                    'activity_logs.id',
                    'activity_logs.user_id',
                    'activity_logs.reference_id',
                    'activity_logs.module',
                    'activity_logs.activity',
                    'activity_logs.created_at',
                    'users.name',
                ])
                ->orderBy('activity_logs.created_at', 'desc')
                ->paginate(15);
        }

        return ActivityLogResource::collection($activity_logs);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
