<?php

namespace App\Http\Controllers;

use App\Custom\Helpers;
use App\Http\Requests\StaticMasterlist as RequestsStaticMasterlist;
use App\Http\Resources\StaticMasterlist;
use App\Models\Attainment;
use App\Models\Cabinet;
use App\Models\Course;
use App\Models\FileType;
use App\Models\Objective;
use App\Models\Prefix;
use App\Models\Religion;
use App\Models\Schedule;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaticMasterlistController extends Controller
{
    private function tableArray($table) {
        $table_list = [
            'COURSES' => 'courses',
            'ATTAINMENTS' => 'attainments',
            'FILE TYPES' => 'filetypes',
            'RELIGIONS' => 'religions',
            'SCHEDULES' => 'schedules', 
            'CABINETS' => 'cabinets',
            'OBJECTIVES' => 'objectives',
            'TEAMS' => 'teams',
            'PREFIXES' => 'prefixes'
        ];

        $table = $table_list[$table];

        return $table;
    }

    private function tableModel($table, $id = '') {
        switch ($table) {
            case 'courses':
                $data = (!$id) ? new Course() : Course::findOrFail($id);
                break;
            
            case 'attainments':
                $data = (!$id) ? new Attainment() : Attainment::findOrFail($id);
                break;

            case 'filetypes':
                $data = (!$id) ? new FileType() : FileType::findOrFail($id);
                break;

            case 'religions' :
                $data = (!$id) ? new Religion() : Religion::findOrFail($id);
                break;

            case 'schedules' :
                $data = (!$id) ? new Schedule() : Schedule::findOrFail($id);
                break; 

            case 'cabinets' :
                $data = (!$id) ? new Cabinet() : Cabinet::findOrFail($id);
                break;

            case 'objectives' :
                $data = (!$id) ? new Objective() : Objective::findOrFail($id);
                break;

            case 'teams' :
                $data = (!$id) ? new Team() : Team::findOrFail($id);
                break;

            case 'prefixes' :
                $data = (!$id) ? new Prefix() : Prefix::findOrFail($id);
                break;

            default:
                $data = (!$id) ? new Course() : Course::findOrFail($id);
                break;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $data = [];
        $field = request('field');
        $sort = request('sort');
        $keyword = request('keyword');
        $table = request('table');
        $table = $this->tableArray($table);

        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'name' : 'created_at';   

            if (!empty($keyword)) {
                $value = '%'.$keyword.'%';
                $data = DB::table($table)
                    ->select([
                        'id',
                        'name',
                        'created_at'
                    ])
                    ->where('name', 'LIKE', $value)
                    ->orderBy($field, $sort)
                    ->paginate(15);
            } else {
                $data = DB::table($table)
                    ->select([
                        'id',
                        'name',
                        'created_at'
                    ])
                    ->orderBy($field, $sort)
                    ->paginate(15);
            }
        }

        return StaticMasterlist::collection($data);
    }

    public function show($id)
    {
        // $location = Location::findOrFail($id);
        // return new StaticMasterlist($location);
    }

    public function store(RequestsStaticMasterlist $request)
    {
        $table = request('table');
        $table = $this->tableArray($table);

        Helpers::checkDuplicate($table, 'name', $request->input('name'));
         
        $data = $this->tableModel($table);
        $data->name = $request->input('name');

        if ($data->save()) {
            Helpers::LogActivity($data->id, 'MASTERLIST - '.request('table'), 'ADDED NEW EXTRA MASTERLIST DATA');
            return new StaticMasterlist($data);
        }
    }

    public function update(RequestsStaticMasterlist $request, $id)
    {
        $table = request('table');
        $table = $this->tableArray($table);

        Helpers::checkDuplicate($table, 'name', $request->input('name'), $id);

        $data = $this->tableModel($table, $id);
        $data->name = $request->input('name');

        if ($data->save()) {
            Helpers::LogActivity($data->id, 'MASTERLIST - '.request('table'), 'UPDATED LOCATION DATA');
            return new StaticMasterlist($data);
        }
    }

    public function destroy($id)
    {
        $table = request('table');
        $table = $this->tableArray($table);
        $data = $this->tableModel($table, $id);

        if ($data->delete()) {
            Helpers::LogActivity($data->id, 'MASTERLIST - LOCATION', 'DELETED LOCATION DATA');
            return new StaticMasterlist($data);
        }
    }

    public function sortData() 
    {
        $table = request('table');
        $table = $this->tableArray($table);

        $field = request('field');
        $sort = request('sort');
        
        if (in_array($field, ['name', 'date']) && in_array($sort, ['desc', 'asc'])) {
            $field = ($field == 'name') ? 'name' : 'created_at';            

            $data = DB::table($table)
                ->select([
                    'id', 
                    'name',
                    'created_at'
                ])
                ->orderBy($field, $sort)->paginate(15);

            return StaticMasterlist::collection($data);
        }
    }
}
