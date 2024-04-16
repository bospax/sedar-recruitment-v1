<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class FormSettingExportError implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
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
            'FORM TYPE',
            'LABEL',
            'BATCH',
            'SUBUNIT',
            'APPROVER ID PREFIX',
            'APPROVER ID NUMBER',
            'ACTION',
            'LEVEL',
            'RECEIVER ID PREFIX',
            'RECEIVER ID NUMBER',
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
                $event->sheet->getStyle('A1:L1')->applyFromArray($style);
            }
        ];
    }
}
