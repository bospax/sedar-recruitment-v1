<?php

namespace App\Exports;

use App\Models\Role;
use Maatwebsite\Excel\Concerns\FromCollection;
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
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

// WithColumnFormatting
class EmployeeDataExport implements ShouldAutoSize, WithMapping, WithHeadings, FromQuery, WithEvents
{
    use Exportable;

    private $query;
    private $current_user;
    private $role;
    private $fields = [];

    public function __construct($query, $fields = [])
    {
        $this->query = $query;
        $this->fields = ($fields) ? $fields : [];
        $this->current_user = Auth::user();
        $this->role = Role::findOrFail($this->current_user->role_id);
    }
    
    public function query()
    {
        return $this->query;
    }

    public function map($model): array
    {
        $full_name = $model->last_name.', '.$model->suffix.' '.$model->first_name.' '.$model->middle_name;
        $referer = ($model->r_last_name) ? $model->r_last_name.', '.$model->r_suffix.' '.$model->r_first_name.' '.$model->r_middle_name : '';
        $age = ($model->birthdate) ? Carbon::parse($model->birthdate)->age : '';
        // $years = Carbon::parse($model->hired_date_fix)->age;
        $fields = $this->fields;

        // $age = '';
        // $years = '';
        
        $export = [];

        // $current_user = Auth::user();

        if ($this->current_user->role_id) {
            // $role = Role::findOrFail($current_user->role_id);
            $permissions = ($this->role->permissions) ? explode(',', $this->role->permissions) : [];
            $position_name = ($model->position_name) ? $model->position_name : '';
            $subunit_name = ($model->subunit_name) ? $model->subunit_name : '';
            $department_name = ($model->department_name) ? $model->department_name : '';
            $division_name = ($model->division_name) ? $model->division_name : '';
            $category_name = ($model->category_name) ? $model->category_name : '';
            $location_name = ($model->location_name) ? $model->location_name : '';
            $company_name = ($model->company_name) ? $model->company_name : '';
            $jobband_name = ($model->jobband_name) ? $model->jobband_name : '';
            $salary_structure = ($model->salary_structure) ? $model->salary_structure : '';
            $job_level = ($model->job_level) ? $model->job_level : '';
            $job_rate = ($model->job_rate) ? $model->job_rate : '';
            $allowance = ($model->allowance) ? $model->allowance : '';
            $superior = ($model->s_id_number) ? $model->s_first_name.' '.$model->s_middle_name.' '.$model->s_last_name : '';
            $converted_created_at = Date::dateTimeToExcel(Carbon::parse($model->created_at));

            $export = [
                $model->id,
                $model->prefix_id,
                $model->id_number,
                $full_name,
                $model->last_name,
                $model->first_name,
                $model->middle_name,
                $model->suffix,
                $position_name,
                $model->team,
                $superior,
                $subunit_name,
                $department_name,
                $division_name,
                $category_name,
                $location_name,
                $company_name,
                $model->schedule,
                $job_level,
                $salary_structure,
                $model->jobrate_name,
                $model->salary,
                $job_rate,
                $allowance,
                $model->additional_rate,
                $jobband_name,
                $model->employment_type_label,
                (($model->employment_date_start) && ($model->employment_date_start != '--')) ? date('m/d/Y', strtotime($model->employment_date_start)) : '',
                (($model->employment_date_end) && ($model->employment_date_end != '--')) ? date('m/d/Y', strtotime($model->employment_date_end)) : '',
                (($model->regularization_date) && ($model->regularization_date != '--')) ? date('m/d/Y', strtotime($model->regularization_date)) : '',
                (($model->hired_date) && ($model->hired_date != '--')) ? date('m/d/Y', strtotime($model->hired_date)) : '',
                $model->employee_state_label,
                (($model->state_date) && ($model->state_date != '--')) ? date('m/d/Y', strtotime($model->state_date)) : '',
                $model->tenure,
                $model->status_remarks,
                $referer,
                (($model->birthdate) && ($model->birthdate != '--')) ? date('m/d/Y', strtotime($model->birthdate)) : '',
                $age,
                $model->gender,
                $model->civil_status,
                $model->religion,
                $model->detailed_address,
                // $model->brgy_desc,
                // $model->citymun_desc,
                // $model->prov_desc,
                $model->zip_code,
                $model->contact_details,
                $model->attainment,
                $model->course,
                $model->sss_no,
                $model->pagibig_no,
                $model->philhealth_no,
                $model->tin_no,
                $model->created_at,
                // $converted_created_at,
            ];

            // if (in_array('job_level', $permissions)) { array_splice($export, 15, 0, $job_level); }
            // if (in_array('job_rate', $permissions)) { array_splice($export, 16, 0, $job_rate); }

            // array_push($export, $model->created_at);
        }

        if (count($fields) != 0 && !in_array('SYSTEM ID', $fields)) { unset($export[0]); }
        if (count($fields) != 0 && !in_array('ID PREFIX', $fields)) { unset($export[1]); }
        if (count($fields) != 0 && !in_array('ID NUMBER', $fields)) { unset($export[2]); }
        if (count($fields) != 0 && !in_array('FULL NAME', $fields)) { unset($export[3]); }
        if (count($fields) != 0 && !in_array('LAST NAME', $fields)) { unset($export[4]); }
        if (count($fields) != 0 && !in_array('FIRST NAME', $fields)) { unset($export[5]); }
        if (count($fields) != 0 && !in_array('MIDDLE NAME', $fields)) { unset($export[6]); }
        if (count($fields) != 0 && !in_array('SUFFIX', $fields)) { unset($export[7]); }
        if (count($fields) != 0 && !in_array('POSITION', $fields)) { unset($export[8]); }
        if (count($fields) != 0 && !in_array('TEAM', $fields)) { unset($export[9]); }
        if (count($fields) != 0 && !in_array('IMMEDIATE SUPERIOR', $fields)) { unset($export[10]); }
        if (count($fields) != 0 && !in_array('SUBUNIT', $fields)) { unset($export[11]); }
        if (count($fields) != 0 && !in_array('DEPARTMENT', $fields)) { unset($export[12]); }
        if (count($fields) != 0 && !in_array('DIVISION', $fields)) { unset($export[13]); }
        if (count($fields) != 0 && !in_array('CATEGORY', $fields)) { unset($export[14]); }
        if (count($fields) != 0 && !in_array('LOCATION', $fields)) { unset($export[15]); }
        if (count($fields) != 0 && !in_array('COMPANY', $fields)) { unset($export[16]); }
        if (count($fields) != 0 && !in_array('SCHEDULE', $fields)) { unset($export[17]); }
        if ((!in_array('job_level', $permissions)) || count($fields) != 0 && !in_array('JOB LEVEL', $fields)) { unset($export[18]); }
        if ((!in_array('salary_structure', $permissions)) || count($fields) != 0 && !in_array('SALARY STRUCTURE', $fields)) { unset($export[19]); }
        if (count($fields) != 0 && !in_array('PAYRATE', $fields)) { unset($export[20]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('TOTAL SALARY', $fields)) { unset($export[21]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('JOB RATE', $fields)) { unset($export[22]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('ALLOWANCE', $fields)) { unset($export[23]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('ADDITIONAL RATE', $fields)) { unset($export[24]); }
        if (count($fields) != 0 && !in_array('HIERARCHY', $fields)) { unset($export[25]); }
        if (count($fields) != 0 && !in_array('EMPLOYMENT TYPE', $fields)) { unset($export[26]); }
        if (count($fields) != 0 && !in_array('DATE STARTED', $fields)) { unset($export[27]); }
        if (count($fields) != 0 && !in_array('DATE END', $fields)) { unset($export[28]); }
        if (count($fields) != 0 && !in_array('REGULARIZATION DATE', $fields)) { unset($export[29]); }
        if (count($fields) != 0 && !in_array('DATE HIRED', $fields)) { unset($export[30]); }
        if (count($fields) != 0 && !in_array('STATUS', $fields)) { unset($export[31]); }
        if (count($fields) != 0 && !in_array('EFFECTIVITY DATE', $fields)) { unset($export[32]); }
        if (count($fields) != 0 && !in_array('YEARS IN RDF', $fields)) { unset($export[33]); }
        if (count($fields) != 0 && !in_array('REASON OF SEPARATION', $fields)) { unset($export[34]); }
        if (count($fields) != 0 && !in_array('RECOMMENDED BY', $fields)) { unset($export[35]); }
        if (count($fields) != 0 && !in_array('BIRTHDATE', $fields)) { unset($export[36]); }
        if (count($fields) != 0 && !in_array('AGE', $fields)) { unset($export[37]); }
        if (count($fields) != 0 && !in_array('GENDER', $fields)) { unset($export[38]); }
        if (count($fields) != 0 && !in_array('CIVIL STATUS', $fields)) { unset($export[39]); }
        if (count($fields) != 0 && !in_array('RELIGION', $fields)) { unset($export[40]); }
        if (count($fields) != 0 && !in_array('DETAILED ADDRESS', $fields)) { unset($export[41]); }
        if (count($fields) != 0 && !in_array('ZIP CODE', $fields)) { unset($export[42]); }
        if (count($fields) != 0 && !in_array('CONTACT NUMBER', $fields)) { unset($export[43]); }
        if (count($fields) != 0 && !in_array('ATTAINMENT', $fields)) { unset($export[44]); }
        if (count($fields) != 0 && !in_array('COURSE', $fields)) { unset($export[45]); }
        if (count($fields) != 0 && !in_array('SSS NO', $fields)) { unset($export[46]); }
        if (count($fields) != 0 && !in_array('PAGIBIG NO', $fields)) { unset($export[47]); }
        if (count($fields) != 0 && !in_array('PHILHEALTH', $fields)) { unset($export[48]); }
        if (count($fields) != 0 && !in_array('TIN', $fields)) { unset($export[49]); }
        if (count($fields) != 0 && !in_array('CREATED AT', $fields)) { unset($export[50]); }

        return $export;
    }

    public function headings(): array
    {
        // specify the heading names
        $export = [];
        $fields = $this->fields;

        if ($this->current_user->role_id) {
            $permissions = ($this->role->permissions) ? explode(',', $this->role->permissions) : [];
            
            $export = [
                'SYSTEM ID',
                'ID PREFIX',
                'ID NUMBER',
                'FULL NAME',
                'LAST NAME',
                'FIRST NAME',
                'MIDDLE NAME',
                'SUFFIX',
                'POSITION',
                'TEAM',
                'IMMEDIATE SUPERIOR',
                'SUBUNIT',
                'DEPARTMENT',
                'DIVISION',
                'CATEGORY',
                'LOCATION',
                'COMPANY',
                'SCHEDULE',
                'JOB LEVEL',
                'SALARY STRUCTURE',
                'PAYRATE',
                'TOTAL SALARY',
                'JOB RATE',
                'ALLOWANCE',
                'ADDITIONAL RATE',
                'HIERARCHY',
                'EMPLOYMENT TYPE',
                'DATE STARTED',
                'DATE END',
                'REGULARIZATION DATE',
                'DATE HIRED',
                'STATUS',
                'EFFECTIVITY DATE',
                'YEARS IN RDF',
                'REASON OF SEPARATION',
                'RECOMMENDED BY',
                'BIRTHDATE',
                'AGE',
                'GENDER',
                'CIVIL STATUS',
                'RELIGION',
                'DETAILED ADDRESS',
                // 'BARANGAY',
                // 'MUNICIPALITY',
                // 'PROVINCE',
                'ZIP CODE',
                'CONTACT NUMBER',
                'ATTAINMENT',
                'COURSE',
                'SSS NO',
                'PAGIBIG NO',
                'PHILHEALTH',
                'TIN',
                'CREATED AT'
            ];

            // if (in_array('job_level', $permissions)) { array_splice($export, 15, 0, 'JOB LEVEL'); }
            // if (in_array('job_rate', $permissions)) { array_splice($export, 16, 0, 'JOB RATE'); }

            // array_push($export, 'CREATED AT');
        }

        if (count($fields) != 0 && !in_array('SYSTEM ID', $fields)) { unset($export[0]); }
        if (count($fields) != 0 && !in_array('ID PREFIX', $fields)) { unset($export[1]); }
        if (count($fields) != 0 && !in_array('ID NUMBER', $fields)) { unset($export[2]); }
        if (count($fields) != 0 && !in_array('FULL NAME', $fields)) { unset($export[3]); }
        if (count($fields) != 0 && !in_array('LAST NAME', $fields)) { unset($export[4]); }
        if (count($fields) != 0 && !in_array('FIRST NAME', $fields)) { unset($export[5]); }
        if (count($fields) != 0 && !in_array('MIDDLE NAME', $fields)) { unset($export[6]); }
        if (count($fields) != 0 && !in_array('SUFFIX', $fields)) { unset($export[7]); }
        if (count($fields) != 0 && !in_array('POSITION', $fields)) { unset($export[8]); }
        if (count($fields) != 0 && !in_array('TEAM', $fields)) { unset($export[9]); }
        if (count($fields) != 0 && !in_array('IMMEDIATE SUPERIOR', $fields)) { unset($export[10]); }
        if (count($fields) != 0 && !in_array('SUBUNIT', $fields)) { unset($export[11]); }
        if (count($fields) != 0 && !in_array('DEPARTMENT', $fields)) { unset($export[12]); }
        if (count($fields) != 0 && !in_array('DIVISION', $fields)) { unset($export[13]); }
        if (count($fields) != 0 && !in_array('CATEGORY', $fields)) { unset($export[14]); }
        if (count($fields) != 0 && !in_array('LOCATION', $fields)) { unset($export[15]); }
        if (count($fields) != 0 && !in_array('COMPANY', $fields)) { unset($export[16]); }
        if (count($fields) != 0 && !in_array('SCHEDULE', $fields)) { unset($export[17]); }
        if ((!in_array('job_level', $permissions)) || count($fields) != 0 && !in_array('JOB LEVEL', $fields)) { unset($export[18]); }
        if ((!in_array('salary_structure', $permissions)) || count($fields) != 0 && !in_array('SALARY STRUCTURE', $fields)) { unset($export[19]); }
        if (count($fields) != 0 && !in_array('PAYRATE', $fields)) { unset($export[20]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('TOTAL SALARY', $fields)) { unset($export[21]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('JOB RATE', $fields)) { unset($export[22]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('ALLOWANCE', $fields)) { unset($export[23]); }
        if ((!in_array('job_rate', $permissions)) || count($fields) != 0 && !in_array('ADDITIONAL RATE', $fields)) { unset($export[24]); }
        if (count($fields) != 0 && !in_array('HIERARCHY', $fields)) { unset($export[25]); }
        if (count($fields) != 0 && !in_array('EMPLOYMENT TYPE', $fields)) { unset($export[26]); }
        if (count($fields) != 0 && !in_array('DATE STARTED', $fields)) { unset($export[27]); }
        if (count($fields) != 0 && !in_array('DATE END', $fields)) { unset($export[28]); }
        if (count($fields) != 0 && !in_array('REGULARIZATION DATE', $fields)) { unset($export[29]); }
        if (count($fields) != 0 && !in_array('DATE HIRED', $fields)) { unset($export[30]); }
        if (count($fields) != 0 && !in_array('STATUS', $fields)) { unset($export[31]); }
        if (count($fields) != 0 && !in_array('EFFECTIVITY DATE', $fields)) { unset($export[32]); }
        if (count($fields) != 0 && !in_array('YEARS IN RDF', $fields)) { unset($export[33]); }
        if (count($fields) != 0 && !in_array('REASON OF SEPARATION', $fields)) { unset($export[34]); }
        if (count($fields) != 0 && !in_array('RECOMMENDED BY', $fields)) { unset($export[35]); }
        if (count($fields) != 0 && !in_array('BIRTHDATE', $fields)) { unset($export[36]); }
        if (count($fields) != 0 && !in_array('AGE', $fields)) { unset($export[37]); }
        if (count($fields) != 0 && !in_array('GENDER', $fields)) { unset($export[38]); }
        if (count($fields) != 0 && !in_array('CIVIL STATUS', $fields)) { unset($export[39]); }
        if (count($fields) != 0 && !in_array('RELIGION', $fields)) { unset($export[40]); }
        if (count($fields) != 0 && !in_array('DETAILED ADDRESS', $fields)) { unset($export[41]); }
        if (count($fields) != 0 && !in_array('ZIP CODE', $fields)) { unset($export[42]); }
        if (count($fields) != 0 && !in_array('CONTACT NUMBER', $fields)) { unset($export[43]); }
        if (count($fields) != 0 && !in_array('ATTAINMENT', $fields)) { unset($export[44]); }
        if (count($fields) != 0 && !in_array('COURSE', $fields)) { unset($export[45]); }
        if (count($fields) != 0 && !in_array('SSS NO', $fields)) { unset($export[46]); }
        if (count($fields) != 0 && !in_array('PAGIBIG NO', $fields)) { unset($export[47]); }
        if (count($fields) != 0 && !in_array('PHILHEALTH', $fields)) { unset($export[48]); }
        if (count($fields) != 0 && !in_array('TIN', $fields)) { unset($export[49]); }
        if (count($fields) != 0 && !in_array('CREATED AT', $fields)) { unset($export[50]); }

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
                $event->sheet->getStyle('A1:AZ1')->applyFromArray($style);
            }
        ];
    }

    // public function columnFormats(): array
    // {
    //     return [
    //         'AY' => NumberFormat::FORMAT_DATE_DMYSLASH,
    //     ];
    // }
}
