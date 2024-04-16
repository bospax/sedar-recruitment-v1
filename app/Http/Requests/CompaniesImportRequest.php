<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompaniesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_company' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_company.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
