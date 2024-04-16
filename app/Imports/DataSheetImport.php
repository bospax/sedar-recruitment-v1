<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Importable;

class DataSheetImport implements ToCollection, WithMultipleSheets
{
    use Importable;

    private $section;
    private $import;

    public function __construct($section)
    {
        $this->section = $section;
    }

    public function collection(Collection $collection)
    {
        //
    }

    public function sheets(): array
    {
        $sheet = '';
        $this->import = new EmployeeDataImport($this->section);

        switch ($this->section) {
            case 'general':
                $sheet = 'GENERAL INFO';
                break;

            case 'position':
                $sheet = 'POSITION';
                break;

            case 'employment':
                $sheet = 'EMPLOYMENT TYPE';
                break;

            case 'status':
                $sheet = 'STATUS';
                break;

            case 'address':
                $sheet = 'ADDRESS';
                break;

            case 'attainment':
                $sheet = 'ATTAINMENT';
                break;

            case 'account':
                $sheet = 'ACCOUNT';
                break;

            case 'contacts':
                $sheet = 'CONTACT';
                break;
            
            default:
                $sheet = '';
                break;
        }

        return [
            $sheet => $this->import
        ];
    }

    public function getErrors() 
    {
        return $this->import->getErrors();
    }

    public function getFailures() 
    {
        return $this->import->failures();
    }
}
