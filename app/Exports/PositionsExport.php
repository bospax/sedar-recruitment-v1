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

class PositionsExport implements ShouldAutoSize, WithMapping, WithHeadings, FromQuery, WithEvents
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
        $full_name = ($model->s_prefix_id) ? $model->s_last_name.', '.$model->s_first_name.' '.$model->s_suffix.' '.$model->s_middle_name : '';
        
        return [
            $model->code,
            $model->position_name,
            $model->department_name,
            $model->subunit_name,
            // $model->location_name,
            $model->jobband_name,
            $model->s_prefix_id,
            $model->s_id_number,
            $full_name,
            $model->payrate,
            $model->schedule,
            $model->team,
            $model->tools,
            $created
        ];
    }

    public function headings(): array
    {
        // specify the heading names
        return [
            'CODE',
            'POSITION NAME',
            'DEPARTMENT',
            'SUBUNIT',
            // 'LOCATION',
            'JOBBAND',
            'SUPERIOR ID PREFIX',
            'SUPERIOR ID NUMBER',
            'IMMEDIATE SUPERIOR',
            'PAYRATE',
            'SCHEDULE',
            'TEAM',
            'TOOLS',
            'CREATED AT'
        ];
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
                $event->sheet->getStyle('A1:P1')->applyFromArray($style);
            }
        ];
    }
}
