<?php

namespace App\Http\Controllers;

use App\Http\Resources\KPI as KPIResources;
use App\Models\KPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KPIController extends Controller
{
    public function store(Request $request) 
    {
        $this->validate($request, [
            'position_id' => 'required'
        ]);

        $position_id = $request->input('position_id');
        $measures = $request->input('measures');

        $kpi = DB::table('kpis')->updateOrInsert(
            ['position_id' => $position_id], 
            [
                'position_id' => $position_id, 
                'measures' => json_encode($measures), 
                'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
            ]
        );

        return response()->json($kpi);
    }

    public function getKPIs($position_id) 
    {
        $kpi = KPI::where('position_id', $position_id)->firstOrFail();
        return response()->json($kpi);
    }
}
