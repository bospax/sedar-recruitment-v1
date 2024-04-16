<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobRatesImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_jobrate' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_jobrate.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
