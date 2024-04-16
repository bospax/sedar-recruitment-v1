<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Subunit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class FormSettingImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];
    private $subunits;
    private $employees;

    public function __construct()
    {
        $this->subunits = Subunit::select('id', 'subunit_name')->get();
        $this->employees = Employee::select('id', 'prefix_id', 'id_number')->get();
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];
        $data_receiver = [];

        foreach ($rows as $key => $row) {
            $form_type = trim($row['form_type']);
            $label = trim($row['label']);
            $batch = trim($row['batch']);
            $sub = trim($row['subunit']);
            $approver_id_prefix = trim($row['approver_id_prefix']);
            $approver_id_number = trim($row['approver_id_number']);
            $action = trim($row['action']);
            $level = trim($row['level']);
            $receiver_id_prefix = trim($row['receiver_id_prefix']);
            $receiver_id_number = trim($row['receiver_id_number']);

            $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $row = array_merge($row, ['message' => $error]);
                    }
                }
                $this->errors[] = $row;
            } else {
                $subunit = $this->subunits->where('subunit_name', $sub)->first();

                $approver = $this->employees
                    ->where('prefix_id', $approver_id_prefix)
                    ->where('id_number', $approver_id_number)
                    ->first();

                $receiver = $this->employees
                    ->where('prefix_id', $receiver_id_prefix)
                    ->where('id_number', $receiver_id_number)
                    ->first();

                $levels = [
                    'APPROVER 1' => 1,
                    'APPROVER 2' => 2,
                    'APPROVER 3' => 3,
                    'APPROVER 4' => 4,
                    'APPROVER 5' => 5,
                    'APPROVER 6' => 6,
                    'APPROVER 7' => 7,
                    'APPROVER 8' => 8,
                    'APPROVER 9' => 9
                ];

                $data[] = [
                    'form_type' => $form_type,
                    'batch' => $batch,
                    'label' => $label,
                    'subunit_id' => $subunit->id ?? null,
                    'employee_id' => $approver->id ?? null,
                    'action' => $action,
                    'level' => $levels[$level] ?? null,
                    'receiver_id' => $receiver->id ?? null,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString()
                ];

                $data_receiver[] = [
                    'form_type' => $row['form_type'],
                    'batch' => $row['batch'],
                    'label' => $row['label'],
                    'subunit_id' => $subunit->id ?? null,
                    'employee_id' => $receiver->id ?? null,
                    'created_at' => '',
                    'updated_at' => ''
                ];
            }
        }

        $chunks = array_chunk($data, 2500);
        $chunks_receiver = array_chunk($data_receiver, 2500);

        foreach ($chunks as $chunk) {
            DB::table('forms')->insert($chunk);
        }

        foreach ($chunks_receiver as $chunk) {
            $chunk = array_unique($chunk, SORT_REGULAR);
            DB::table('receivers')->insert($chunk);
        }
    }

    public function rules(): array
    {
        $levels = [
            'APPROVER 1',
            'APPROVER 2',
            'APPROVER 3',
            'APPROVER 4',
            'APPROVER 5',
            'APPROVER 6',
            'APPROVER 7',
            'APPROVER 8',
            'APPROVER 9'
        ];

        $form_types = [
            'monthly-evaluation',
            'probi-evaluation',
            'annual-evaluation',
            'employee-registration',
            'manpower-form',
            'da-evaluation',
            'merit-increase-form'
        ];

        $actions = [
            'REQUEST',
            'ASSESS',
            'REVIEW',
            'NOTE',
            'APPROVE',
            'RECOMMEND'
        ];

        return [
            'form_type' => ['required', Rule::in($form_types)],
            'label' => ['required'],
            'batch' => ['required'],
            'subunit' => ['required', 'exists:subunits,subunit_name'],
            'approver_id_prefix' => ['required'],
            'approver_id_number' => ['required', 'exists:employees,id_number'],
            'action' => ['required', Rule::in($actions)],
            'level' => ['required', Rule::in($levels)],
            'receiver_id_prefix' => ['required'],
            'receiver_id_number' => ['required', 'exists:employees,id_number'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'approver_id_number.exists' => 'The employee id number does not exists.'
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