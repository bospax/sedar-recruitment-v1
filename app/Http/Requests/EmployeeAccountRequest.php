<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeAccountRequest extends FormRequest
{
    public function rules()
    {
        return [
            'employee_id' => ['required'],
            'sss_no' => ['required', 'regex:/^\d{2}-\d{7}-\d{1}$/'],
            'pagibig_no' => ['required', 'regex:/^\d{4}-\d{4}-\d{4}$/'],
            'philhealth_no' => ['required', 'regex:/^\d{2}-\d{9}-\d{1}$/'],
            'tin_no' => ['nullable', 'regex:/^\d{3}-\d{3}-\d{3}-\d{5}$/']
        ];
    }

    public function messages()
    {
        return [
            'sss_no.required' => 'The SSS # field is required.',
            'pagibig_no.required' => 'The Pag-IBIG # field is required.',
            'philhealth_no.required' => 'The PhilHealth # field is required.',
            'tin_no.required' => 'The TIN # field is required.',
            'sss_no.regex' => 'The sss number format is invalid. (Valid Format: ##-#######-#)',
            'pagibig_no.regex' => 'The pagibig number format is invalid. (Valid Format: ####-####-####)',
            'philhealth_no.regex' => 'The philhealth number format is invalid. (Valid Format: ##-#########-#)',
            'tin_no.regex' => 'The tin number format is invalid. (Valid Format: ###-###-###-00000)',
        ];
    }
}
