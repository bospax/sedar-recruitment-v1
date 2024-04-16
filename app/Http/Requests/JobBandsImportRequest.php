<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JobBandsImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_jobband' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_jobband.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
