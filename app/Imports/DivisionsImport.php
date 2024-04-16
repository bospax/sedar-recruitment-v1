<?php

namespace App\Imports;

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

class DivisionsImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];

        foreach ($rows as $key => $row) {
            $division_name = trim($row['division_name']);

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
                    'division_name' => $division_name,
                    'status' => 'active',
                    'status_description' => 'ACTIVE',
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];
            }
        }

        $chunks = array_chunk($data, 2500);

        foreach ($chunks as $chunk) {
            DB::table('divisions')->insert($chunk);
        }
    }

    public function rules(): array
    {
        return [
            'division_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u", 'unique:divisions,division_name']
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'division_name.required' => 'Division Name is required.',
            'division_name.regex' => 'Special characters are not allowed. Valid characters: (A-z 0-9 -,_.).',
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