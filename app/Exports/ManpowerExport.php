<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;

class ManpowerExport implements ShouldAutoSize, WithMapping, WithHeadings, FromQuery, WithEvents
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
        $date_fullfilled = ($model->date_fulfilled) ? Carbon::createFromFormat('Y-m-d', $model->date_fulfilled)->format('M d, Y') : '';
        $review_date = ($model->review_date) ? Carbon::createFromFormat('M d, Y h:i a', $model->review_date)->format('M d, Y') : '';
        $effectivity_date = ($model->effectivity_date) ? Carbon::createFromFormat('Y-m-d', $model->effectivity_date)->format('M d, Y') : '';
        $hired_date =  ($model->hired_date_fix) ? Carbon::createFromFormat('Y-m-d', $model->hired_date_fix)->format('M d, Y') : '';
        $startDate = Carbon::parse($review_date);
        $endDate = ($model->hiring_type == 'EXTERNAL HIRE') ? Carbon::parse($hired_date) : Carbon::parse($effectivity_date);
        $duration = 0;

        if ($model->hired_date && $model->hiring_type == 'EXTERNAL HIRE') {
            $duration = $endDate->diffInDays($startDate);
        }

        if ($model->effectivity_date && $model->hiring_type == 'INTERNAL HIRE') {
            $duration = $endDate->diffInDays($startDate);
        }

        $requestor_name = $model->first_name.' '.$model->middle_name.' '.$model->last_name.' '.$model->suffix;
        $tobe_hired = $model->tobe_hired_first_name.' '.$model->tobe_hired_middle_name.' '.$model->tobe_hired_last_name.' '.$model->tobe_hired_suffix;
        
        return [
            $model->code,
            $model->requisition_type_mark,
            $created,
            $model->is_fulfilled,
            $date_fullfilled,
            $model->hiring_type,
            $tobe_hired,
            $model->hired_date,
            $review_date,
            $effectivity_date,
            $duration,
            $model->requested_position_name,
            $model->subunit_name,
            $model->department_name,
            $model->job_level,
            $model->expected_salary,
            $model->employment_type,
            $model->requestor_position.' - '.$requestor_name,
        ];
    }

    public function headings(): array
    {
        // specify the heading names
        return [
            'REFERENCE NUMBER',
            'REQUISITION TYPE',
            'DATE REQUESTED',
            'STATUS',
            'DATE SERVED',
            'HIRING TYPE',
            'EMPLOYEE TO BE HIRED',
            'DATE HIRED',
            'LAST APPROVAL DATE',
            'EFFECTIVITY DATE',
            'DURATION (DAYS)',
            'REQUESTED POSITION',
            'SUBUNIT',
            'DEPARTMENT',
            'JOB LEVEL',
            'EXPECTED SALARY',
            'EMPLOYMENT TYPE',
            'REQUESTOR',
        ];
    }

    public function registerEvents(): array
    {
        $style = [
            'font' => [
                'bold' => true
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => 'faf878'],
            ],
        ];

        return [
            AfterSheet::class => function(AfterSheet $event) use ($style) {
                $event->sheet->getStyle('A1:T1')->applyFromArray($style);
            }
        ];
    }
}
