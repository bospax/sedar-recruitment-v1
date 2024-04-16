<?php

namespace App\Imports;

use App\Models\Position;
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

class JobRatesImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];

        foreach ($rows as $key => $row) {
            $code = trim($row['code']);
            $rate = trim($row['job_rate']);
            $rate = str_replace(',', '', $rate);
            $rate = number_format($rate, 2);

            $allowance = trim($row['allowance']);
            $allowance = str_replace(',', '', $allowance);
            $allowance = number_format($allowance, 2);

            $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $row = array_merge($row, ['message' => $error]);
                    }
                }
                $this->errors[] = $row;
            } else {
                $data[] = [
                    'code' => $code,
                    'job_level' => $row['job_level'],
                    'position_title' => $row['position'],
                    'job_rate' => $rate,
                    'allowance' => $allowance,
                    'salary_structure' => $row['salary_structure'],
                    'jobrate_name' => $row['description'],
                    'status' => 'active',
                    'status_description' => 'ACTIVE',
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $chunks = array_chunk($data, 2500);

        foreach ($chunks as $chunk) {
            DB::table('jobrates')->insert($chunk);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'unique:jobrates,code'],
            'description' => ['required'],
            'job_level' => ['required'],
            'salary_structure' => ['required'],
            'position' => ['required'],
            'job_rate' => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'description.required' => 'Description is required.'
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