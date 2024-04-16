<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class PositionsExportError implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    use Exportable;

    private $failed_rows;

    public function __construct($items)
    {
        $this->failed_rows = $items;
    }

    public function collection()
    {
        return new Collection($this->failed_rows);
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
            'PAYRATE',
            'TEAM',
            'TOOLS',
            'REMARKS'
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
