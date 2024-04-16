<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\DivisionCategory;
use App\Models\Location;
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

class DepartmentsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];
    private $divisions;
    private $categories;
    private $companies;
    private $locations;

    public function __construct()
    {
        // $this->divisions = Division::select('id', 'division_name')->get();
        // $this->categories = DivisionCategory::select('id', 'category_name')->get();
        // $this->companies = Company::select('id', 'company_name')->get();
        // $this->locations = Location::select('id', 'location_name')->get();
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];

        foreach ($rows as $key => $row) {
            $department_name = trim($row['department']);
            // $division_name = trim($row['division']);
            // $category_name = trim($row['category']);
            // $company_name = trim($row['company']);
            // $location_name = trim($row['location']);

            $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $row = array_merge($row, ['message' => $error]);
                    }
                }
                $this->errors[] = $row;
            } else {
                // $division = $this->divisions->where('division_name', $division_name)->first();
                // $category = $this->categories->where('category_name', $category_name)->first();
                // $company = $this->companies->where('company_name', $company_name)->first();
                // $location = $this->locations->where('location_name', $location_name)->first();

                $data[] = [
                    'department_name' => $department_name,
                    // 'division_id' => $division->id ?? null,
                    // 'division_cat_id' => $category->id ?? null,
                    // 'company_id' => $company->id ?? null,
                    // 'location_id' => $location->id ?? null,
                    'status' => 'active',
                    'status_description' => 'ACTIVE',
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $chunks = array_chunk($data, 2500);

        foreach ($chunks as $chunk) {
            DB::table('departments')->insert($chunk);
        }
    }

    public function rules(): array
    {
        return [
            'department' => ['required', 'unique:departments,department_name'],
            // 'division' => ['required', 'exists:divisions,division_name'],
            // 'category' => ['required', 'exists:division_categories,category_name'],
            // 'company' => ['required', 'exists:companies,company_name'],
            // 'location' => ['required', 'exists:locations,location_name']
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'department.required' => 'Department Name is required.',
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