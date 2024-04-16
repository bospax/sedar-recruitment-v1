<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepartmentsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_department' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_department.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
