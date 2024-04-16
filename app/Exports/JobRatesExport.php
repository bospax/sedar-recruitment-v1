<?php

namespace App\Exports;

use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\Auth;

class JobRatesExport implements ShouldAutoSize, WithMapping, WithHeadings, FromQuery, WithEvents
{
    use Exportable;

    private $query;

    public function __construct($query)
    {
        $this->query = $query;
    }
    
    public function query()
    {
        return $this->query;
    }

    public function map($model): array
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $model->created_at)->format('M d, Y');
        $isLoggedIn = Auth::check();

        $export = [];

        if ($isLoggedIn) {
            $current_user = Auth::user();
            if ($current_user->role_id) {
                $role = Role::findOrFail($current_user->role_id);
                $permissions = ($role->permissions) ? explode(',', $role->permissions) : [];
                
                $export = [
                    $model->code,
                    $model->position_title
                ];

                if (in_array('job_level', $permissions)) { array_push($export, $model->job_level); }
                if (in_array('job_rate', $permissions)) { array_push($export, $model->job_rate); }
                if (in_array('job_rate', $permissions)) { array_push($export, $model->allowance); }
                if (in_array('salary_structure', $permissions)) { array_push($export, $model->salary_structure); }
                if (in_array('job_level', $permissions)) { array_push($export, $model->jobrate_name); }

                array_push($export, $created);
            }
        }

        return $export;
    }

    public function headings(): array
    {
        $isLoggedIn = Auth::check();

        $export = [];

        if ($isLoggedIn) {
            $current_user = Auth::user();
            if ($current_user->role_id) {
                $role = Role::findOrFail($current_user->role_id);
                $permissions = ($role->permissions) ? explode(',', $role->permissions) : [];
                
                $export = [
                    'CODE',
                    'POSITION'
                ];

                if (in_array('job_level', $permissions)) { array_push($export, 'JOB LEVEL'); }
                if (in_array('job_rate', $permissions)) { array_push($export, 'JOB RATE'); }
                if (in_array('job_rate', $permissions)) { array_push($export, 'ALLOWANCE'); }
                if (in_array('salary_structure', $permissions)) { array_push($export, 'SALARY STRUCTURE'); }
                if (in_array('job_level', $permissions)) { array_push($export, 'DESCRIPTION'); }

                array_push($export, 'CREATED AT');
            }
        }
        
        return $export;
    }

    public function registerEvents(): array
    {
        $style = [
            'font' => [
                'bold' => true
            ]
        ];

        return [
            AfterSheet::class => function(AfterSheet $event) use ($style) {
                $event->sheet->getStyle('A1:L1')->applyFromArray($style);
            }
        ];
    }
}
