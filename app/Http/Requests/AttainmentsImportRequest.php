<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttainmentsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_attainment' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_attainment.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
