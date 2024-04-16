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

class JobBandsExport implements ShouldAutoSize, WithMapping, WithHeadings, FromQuery, WithEvents
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
        
        return [
            $model->id,
            $model->jobband_name,
            $created
        ];
    }

    public function headings(): array
    {
        // specify the heading names
        return [
            'ID',
            'JOBBAND NAME',
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
                $event->sheet->getStyle('A1:D1')->applyFromArray($style);
            }
        ];
    }
}
