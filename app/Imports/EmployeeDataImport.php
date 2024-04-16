<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Department;
use App\Models\Division;
use App\Models\DivisionCategory;
use App\Models\Employee;
use App\Models\EmployeePosition;
use App\Models\EmployeeState;
use App\Models\EmployeeStatus;
use App\Models\JobRate;
use App\Models\Location;
use App\Models\Position;
use Carbon\Carbon;
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
use Illuminate\Validation\Rule;

class EmployeeDataImport implements ToCollection, WithHeadingRow, SkipsOnFailure, WithValidation, WithChunkReading
{
    use Importable, SkipsFailures;

    private $errors = [];
    private $section;
    private $employees;
    private $jobrates;
    private $positions;
    private $employee_positions;
    private $employee_statuses;
    private $employee_states;
    private $divisions;
    private $categories;
    private $companies;
    private $locations;

    public function __construct($section)
    {
        $this->section = $section;
        $this->employees = Employee::select('id', 'prefix_id', 'id_number')->get();
        $this->positions = Position::select('id', 'code')->get();
        $this->employee_positions = EmployeePosition::select('employee_id')->get()->pluck('employee_id')->toArray();
        $this->employee_statuses = EmployeeStatus::select('employee_id')->get()->pluck('employee_id')->toArray();
        $this->employee_states = EmployeeState::select('employee_id')->get()->pluck('employee_id')->toArray();
        $this->jobrates = JobRate::select('id', 'code')->get();
        $this->divisions = Division::select('id', 'division_name')->get();
        $this->categories = DivisionCategory::select('id', 'category_name')->get();
        $this->companies = Company::select('id', 'company_name')->get();
        $this->locations = Location::select('id', 'location_name')->get();
    }

    public function collection(Collection $rows)
    {
        $rows = $rows->toArray();
        $data = [];

        $last = $this->employees->last();
        $import_id = ($last) ? $last->id : 1;
        $minutes = 0;

        foreach ($rows as $key => $row) {
            $prefix_id = trim($row['prefix_id']);
            $id_number = trim($row['id_number']);
            $minutes++;

            $validator = Validator::make($row, $this->rules(), $this->customValidationMessages());

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $messages) {
                    foreach ($messages as $error) {
                        $row = array_merge($row, ['message' => $error]);
                    }
                }
                $this->errors[] = $row;
            } else {
                $employee = $this->employees
                    ->where('prefix_id', $prefix_id)
                    ->where('id_number', $id_number)
                    ->first();

                switch ($this->section) {
                    case 'general':
                        
                        $import_id++;
                        $import_prefix = 'IMT';
                        $code = $import_prefix.'-'.str_pad($import_id, 4, '0', STR_PAD_LEFT);

                        $data[] = [
                            'code' => $code,
                            'prefix_id' => $prefix_id,
                            'id_number' => $id_number,
                            'first_name' => $row['first_name'],
                            'middle_name' => $row['middle_name'],
                            'last_name' => $row['last_name'],
                            'suffix' => $row['suffix'],
                            'birthdate' => strtoupper($row['birthdate']),
                            'religion' => $row['religion'],
                            'civil_status' => $row['civil_status'],
                            'gender' => $row['gender'],
                            'image' => 'noimage.png',
                            'form_type' => 'employee-registration',
                            'level' => 2,
                            'current_status' => 'approved',
                            'current_status_mark' => 'APPROVED',
                            'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                            'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                        ];
                        break;
                    
                    case 'position':

                        $unit_code = trim($row['unit_code']);
                        // $job_rate_code = trim($row['job_rate_code']);
                        $schedule = trim($row['schedule']);
                        $tools = str_replace(' ', '', $row['tools']);
                        $division_name = trim($row['division']);
                        $category_name = trim($row['division_category']);
                        $company_name = trim($row['company']);
                        $location_name = trim($row['location']);

                        $additional_rate = trim($row['additional_rate']);
                        $jobrate_name = trim($row['payrate']);
                        $salary_structure = trim($row['salary_structure']);
                        $job_level = trim($row['job_level']);
                        $allowance = trim($row['allowance']);
                        $job_rate = trim($row['job_rate']);
                        $salary = trim($row['total_salary']);

                        $position = $this->positions
                            ->where('code', $unit_code)
                            ->first();

                        // $jobrate = $this->jobrates
                        //     ->where('code', $job_rate_code)
                        //     ->first();

                        $division = $this->divisions->where('division_name', $division_name)->first();
                        $category = $this->categories->where('category_name', $category_name)->first();
                        $company = $this->companies->where('company_name', $company_name)->first();
                        $location = $this->locations->where('location_name', $location_name)->first();

                        $ids = array_values($this->employee_positions);

                        if ($employee) {
                            $data[] = [
                                'employee_id' => (int) $employee->id,
                                'position_id' => (int) $position->id,
                                // 'jobrate_id' => $jobrate->id,
                                'division_id' => $division->id ?? null,
                                'division_cat_id' => $category->id ?? null,
                                'company_id' => $company->id ?? null,
                                'location_id' => $location->id ?? null,
                                'schedule' => $schedule,
                                'additional_tool' => $tools,
                                'additional_rate' => $additional_rate,
                                'jobrate_name' => $jobrate_name,
                                'salary_structure' => $salary_structure,
                                'job_level' => $job_level,
                                'allowance' => (float) $allowance,
                                'job_rate' => (float) $job_rate,
                                'salary' => (float) $salary,
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }

                        break;

                    case 'employment':
                        $ids = array_values($this->employee_statuses);
                        $type = trim($row['employment_type']);

                        $employment_type = [
                            'PROBATIONARY' => 'probationary',
                            'REGULAR' => 'regular',
                            'CONTRACTUAL' => 'contractual',
                            'CASUAL' => 'casual',
                            'SEASONAL' => 'seasonal',
                            'PROJECT BASED' => 'project_based',
                            'AGENCY HIRED' => 'agency_hired'
                        ];

                        if ($employee && !in_array($employee->id, $ids)) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'employment_type_label' => $type,
                                'employment_type' => $employment_type[$type],
                                'employment_date_start' => $row['employment_date_start'],
                                'employment_date_end' => $row['employment_date_end'],
                                'regularization_date' => $row['regularization_date'],
                                'hired_date' => $row['hired_date'],
                                'hired_date_fix' => Carbon::parse($row['hired_date']),
                                'reminder' => Carbon::parse($row['hired_date']),
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;

                    case 'status':
                        $ids = array_values($this->employee_states);
                        $state = trim($row['employee_state']);

                        $employee_state = [
                            'EXTENDED' => 'extended',
                            'SUSPENDED' => 'suspended',
                            'TERMINATED' => 'terminated',
                            'RESIGNED' => 'resigned',
                            'ABSENT WITHOUT LEAVE' => 'awol',
                            'END OF CONTRACT' => 'endo',
                            'BLACKLISTED' => 'blacklisted',
                            'DISMISSED' => 'dismissed',
                            'DECEASED' => 'deceased',
                            'BACK OUT' => 'backout',
                            'MATERNITY' => 'maternity'
                        ];

                        if ($employee && !in_array($employee->id, $ids)) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'employee_state_label' => $state,
                                'employee_state' => $employee_state[$state],
                                'state_date_start' => $row['state_date_start'],
                                'state_date_end' => $row['state_date_end'],
                                'state_date' => $row['state_date'],
                                'status_remarks' => $row['status_remarks'],
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;

                    case 'address':
                        if ($employee) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'detailed_address' => $row['detailed_address'],
                                'zip_code' => $row['zip_code'],
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;

                    case 'attainment':
                        if ($employee) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'attainment' => $row['attainment'],
                                'course' => $row['course'],
                                'degree' => $row['degree'],
                                'institution' => $row['institution'],
                                'honorary' => $row['honorary'],
                                'gpa' => $row['gpa'],
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;

                    case 'account':
                        if ($employee) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'sss_no' => $row['sss_number'],
                                'pagibig_no' => $row['pagibig_number'],
                                'philhealth_no' => $row['philhealth_number'],
                                'tin_no' => $row['tin_number'],
                                'bank_name' => $row['bank_name'],
                                'bank_account_no' => $row['bank_account_number'],
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;

                    case 'contacts':
                        if ($employee) {
                            $data[] = [
                                'employee_id' => $employee->id,
                                'contact_type' => $row['contact_type'],
                                'contact_details' => $row['contact_details'],
                                'created_at' => now()->addMinutes($minutes)->toDateTimeString(),
                                'updated_at' => now()->addMinutes($minutes)->toDateTimeString()
                            ];
                        }
                        break;
                    
                    default:
                        $data[] = [];
                        break;
                }
            }
        }

        $chunks = array_chunk($data, 2500);

        $table_name = '';

        switch ($this->section) {
            case 'general':
                $table_name = 'employees';
                break;

            case 'position':
                $table_name = 'employee_positions';
                break;

            case 'employment':
                $table_name = 'employee_statuses';
                break;

            case 'status':
                $table_name = 'employee_states';
                break;

            case 'address':
                $table_name = 'addresses';
                break;

            case 'attainment':
                $table_name = 'employee_attainments';
                break;

            case 'account':
                $table_name = 'employee_accounts';
                break;

            case 'contacts':
                $table_name = 'employee_contacts';
                break;
            
            default:
                $table_name = '';
                break;
        }

        // dd($this->section);

        if ($this->section != 'position') {
            foreach ($chunks as $chunk) {
                DB::table($table_name)->insert($chunk);
            }
        } else {
            foreach ($chunks as $chunk) {
                DB::table($table_name)->upsert($chunk, ['employee_id'], ['salary', 'job_rate', 'allowance', 'additional_rate']);
            }
        }
    }

    public function rules(): array
    {
        date_default_timezone_set('Asia/Manila');

        $rules = [];
        $employment_types = ['PROBATIONARY', 'REGULAR', 'CONTRACTUAL', 'CASUAL', 'SEASONAL', 'PROJECT_BASED', 'AGENCY HIRED'];
        $employee_states = ['EXTENDED', 'SUSPENDED', 'TERMINATED', 'RESIGNED', 'ABSENT WITHOUT LEAVE', 'END OF CONTRACT', 'BLACKLISTED', 'DISMISSED', 'DECEASED', 'BACK OUT', 'MATERNITY'];

        switch ($this->section) {
            case 'general':
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric'],
                    'first_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
                    'middle_name' => ['nullable', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
                    'last_name' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
                    'suffix' => ['nullable','regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
                    'gender' => ['required', 'regex:/^[0-9\pL\s\()&\/.,_-]+$/u'],
                ];
                break;

            case 'position':
                $rules = [
                    // 'unit_code' => ['required'],
                    // // 'job_rate_code' => ['required'],
                    // 'prefix_id' => ['required'],
                    // 'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    // 'division' => ['required', 'exists:divisions,division_name'],
                    // 'division_category' => ['required', 'exists:division_categories,category_name'],
                    // 'company' => ['required', 'exists:companies,company_name'],
                    // 'location' => ['required', 'exists:locations,location_name']
                ];
                break;

            case 'employment':
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    'employment_type' => ['required', Rule::in($employment_types)],
                    'employment_date_start'=> ['nullable', 'date'],
                    'employment_date_end'=> ['nullable', 'date'],
                    'regularization_date'=> ['nullable', 'date'],
                    'hired_date' => ['required', 'date'],
                ];
                break;

            case 'status': 
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    'employee_state' => ['required', Rule::in($employee_states)],
                    'state_date_start'=> ['nullable', 'date'],
                    'state_date_end'=> ['nullable', 'date'],
                    'state_date'=> ['nullable', 'date']
                ];
                break;

            case 'address': 
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    'detailed_address' => ['required']
                ];
                break;

            case 'attainment': 
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    'gpa' => ['nullable','regex:/^(?=.+)(?:[1-9]\d*|0)?(?:\.\d+)?$/']
                ];
                break;

            case 'account': 
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number'],
                    'sss_number' => ['nullable'],
                    'pagibig_number' => ['nullable'],
                    'philhealth_number' => ['nullable'],
                    'tin_number' => ['nullable']
                ];
                break;

            case 'contacts': 
                $rules = [
                    'prefix_id' => ['required'],
                    'id_number' => ['required', 'numeric', 'exists:employees,id_number']
                ];
                break;
            
            default:
                $rules = [];
                break;
        }

        return $rules;
    }

    public function customValidationMessages(): array
    {
        $messages = [];

        switch ($this->section) {
            case 'general':
                $messages = [
                    'first_name.required' => 'First Name is required.',
                    'first_name.regex' => 'Special characters are not allowed. Valid characters: (A-z 0-9 -,_.).',
                ];
                break;

            case 'position':
                $messages = [
                    'id_number.exists' => 'ID Number does not exists in the database.',
                    'position_name.duplicate_position' => 'Employee has already assigned Position.'
                ];
                break;

            case 'employment': 
                $messages = [
                    'employment_type.in' => 'Invalid inputted keyword. Allowed values: [PROBATIONARY, REGULAR, CONTRACTUAL, CASUAL, SEASONAL, PROJECT_BASED]',
                ];
                break;

            case 'status': 
                $messages = [
                    'employee_state.in' => 'Invalid inputted keyword. Allowed values: [EXTENDED, SUSPENDED, TERMINATED, RESIGNED, ABSENT WITHOUT LEAVE, END OF CONTRACT, BLACKLISTED, BACK OUT, DECEASED, MATERNITY]',
                ];
                break;

            default:
                $messages = [];
                break;
        }

        return $messages;
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
