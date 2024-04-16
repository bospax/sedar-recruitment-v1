<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobBand;
use App\Models\Location;
use App\Models\Position;
use App\Models\Subunit;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PositionsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];
    private $departments;
    private $subunits;
    private $jobbands;
    private $locations;
    private $employees;

    public function __construct()
    {
        $this->departments = Department::select('id', 'department_name')->get();
        $this->subunits = Subunit::select('id', 'subunit_name')->get();
        $this->jobbands = JobBand::select('id', 'jobband_name')->get();
        $this->locations = Location::select('id', 'location_name')->get();
        $this->employees = Employee::select('id', 'prefix_id', 'id_number')->get();
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];

        foreach ($rows as $key => $row) {
            $code = trim($row['code']);
            $department_name = trim($row['department']);
            $subunit_name = trim($row['subunit']);
            // $location_name = trim($row['location']);
            $jobband_name = trim($row['jobband']);
            $prefix_id = trim($row['superior_id_prefix']);
            $id_number = trim($row['superior_id_number']);

            $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $row = array_merge($row, ['message' => $error]);
                    }
                }
                $this->errors[] = $row;
            } else {
                $department = $this->departments->where('department_name', $department_name)->first();
                $subunit = $this->subunits->where('subunit_name', $subunit_name)->first();
                // $location = $this->locations->where('location_name', $location_name)->first();
                $jobband = $this->jobbands->where('jobband_name', $jobband_name)->first();
                $superior = $this->employees
                    ->where('prefix_id', $prefix_id)
                    ->where('id_number', $id_number)
                    ->first();
                
                $data[] = [
                    'code' => $code,
                    'department_id' => $department->id ?? null,
                    'subunit_id' => $subunit->id ?? null,
                    // 'location_id' => $location->id ?? null,
                    'jobband_id' => $jobband->id ?? null,
                    'position_name' => $row['position_name'],
                    'payrate' => $row['payrate'],
                    'team' => $row['team'],
                    'tools' => str_replace(' ', '', $row['tools']),
                    'superior' => $superior->id ?? null,
                    'status' => 'active',
                    'status_description' => 'ACTIVE',
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $chunks = array_chunk($data, 2500);

        foreach ($chunks as $chunk) {
            DB::table('positions')->insert($chunk);
        }
    }

    public function rules(): array
    {
        $payrate = ['HOURLY PAID', 'DAILY PAID', 'MONTHLY PAID'];

        return [
            'code' => ['required', 'unique:positions,code'],
            'position_name' => ['required'],
            'department' => ['required', 'exists:departments,department_name'],
            'subunit' => ['required', 'exists:subunits,subunit_name'],
            // 'location' => ['required', 'exists:locations,location_name'],
            'jobband' => ['required', 'exists:jobbands,jobband_name'],
            // 'superior_id_prefix' => ['required'],
            // 'superior_id_number' => ['required', 'exists:employees,id_number'],
            'payrate' => [Rule::in($payrate)],
            'team' => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'position_name.required' => 'Position Name is required.',
            'payrate.in' => 'Invalid inputted keyword. Allowed values: [HOURLY PAID, DAILY PAID, MONTHLY PAID]'
        ];
    }

    public function chunkSize(): int
    {
        return 2500;
    }

    public function getErrors() 
    {
        return $this->errors;
    }
}