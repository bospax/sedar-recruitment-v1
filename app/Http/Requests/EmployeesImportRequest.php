<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_employee' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_employee.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
