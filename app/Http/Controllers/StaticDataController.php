<?php

namespace App\Http\Controllers;

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

class StaticDataController extends Controller
{
    private function tableModel($table) {
        switch ($table) {
            case 'courses':
                $data = Course::pluck('name')->toArray();
                break;
            
            case 'attainments':
                $data = Attainment::pluck('name')->toArray();
                break;

            case 'filetypes':
                $data = FileType::pluck('name')->toArray();
                break;

            case 'religions' :
                $data = Religion::pluck('name')->toArray();
                break;

            case 'schedules' :
                $data = Schedule::pluck('name')->toArray();
                break; 

            case 'cabinets' :
                $data = Cabinet::pluck('name')->toArray();
                break;

            case 'objectives' :
                $data = Objective::pluck('name')->toArray();
                break;

            case 'teams' :
                $data = Team::pluck('name')->toArray();
                break;

            case 'prefixes' :
                $data = Prefix::pluck('name')->toArray();
                break;

            default:
                break;
        }

        return $data;
    }

    public function getCourses() {
        $data = $this->tableModel('courses');

        return $data;
    }

    public function getReligions() {
        $data = $this->tableModel('religions');

        return $data;
    }

    public function getSchedules() {
        $data = $this->tableModel('schedules');

        return $data;
    }

    public function getAttainments() {
        $data = $this->tableModel('attainments');

        return $data;
    }

    public function getFileTypes() {
        $data = $this->tableModel('filetypes');

        return $data;
    }

    public function getCabinets() {
        $data = $this->tableModel('cabinets');

        return $data;
    }

    public function getObjectives() {
        $data = $this->tableModel('objectives');

        return $data;
    }

    public function getTeams() {
        $data = $this->tableModel('teams');

        return $data;
    }

    public function getPrefixes() {
        $data = $this->tableModel('prefixes');

        return $data;
    }

    public function getStaticLists() {
        return [
            'COURSES', 
            'RELIGIONS',
            'SCHEDULES',
            'ATTAINMENTS', 
            'FILE TYPES',
            'CABINETS',
            'OBJECTIVES',
            'TEAMS',
            'PREFIXES'
        ];
    }
}
