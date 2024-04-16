<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class EmployeeDataExportError implements FromCollection, ShouldAutoSize, WithHeadings, WithEvents
{
    use Exportable;

    private $failed_rows;
    private $section;

    public function __construct($items, $section)
    {
        $this->failed_rows = $items;
        $this->section = $section;
    }

    public function collection()
    {
        return new Collection($this->failed_rows);
    }

    public function headings(): array
    {
        $headings = [];

        switch ($this->section) {
            case 'general':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'FIRST NAME',
                    'MIDDLE NAME',
                    'LAST NAME',
                    'SUFFIX',
                    'BIRTHDATE',
                    'RELIGION',
                    'CIVIL STATUS',
                    'GENDER',
                    'REMARKS'
                ];
                break;

            case 'position':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'UNIT CODE',
                    'JOB RATE CODE',
                    'DIVISION',
                    'DIVISION CATEGORY',
                    'COMPANY',
                    'LOCATION',
                    'SCHEDULE',
                    'TOOLS',
                    'REMARKS'
                ];
                break;

            case 'employment':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'EMPLOYMENT TYPE',
                    'EMPLOYMENT DATE START',
                    'EMPLOYMENT DATE END',
                    'REGULARIZATION DATE',
                    'HIRED DATE',
                    'REMARKS'
                ];
                break;

            case 'status':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'EMPLOYEE STATE',
                    'STATE DATE START',
                    'STATE DATE END',
                    'STATE DATE',
                    'STATUS REMARKS',
                    'REMARKS'
                ];
                break;

            case 'address':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'DETAILED ADDRESS',
                    'ZIP CODE',
                    'REMARKS'
                ];
                break;

            case 'attainment':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'ATTAINMENT',
                    'COURSE',
                    'DEGREE',
                    'INSTITUTION',
                    'HONORARY',
                    'GPA',
                    'REMARKS'
                ];
                break;

            case 'account':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'SSS NUMBER',
                    'PAGIBIG NUMBER',
                    'PHILHEALTH NUMBER',
                    'TIN NUMBER',
                    'BANK NAME',
                    'BANK ACCOUNT NUMBER',
                    'REMARKS'
                ];
                break;

            case 'contacts':
                $headings = [
                    'PREFIX ID',
                    'ID NUMBER',
                    'CONTACT TYPE',
                    'CONTACT DETAILS',
                    'REMARKS'
                ];
                break;
            
            default:
                $headings = [];
                break;
        }

        return $headings;
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
                $event->sheet->getStyle('A1:Q1')->applyFromArray($style);
            }
        ];
    }
}
