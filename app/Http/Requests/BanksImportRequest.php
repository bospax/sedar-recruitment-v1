<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BanksImportRequest extends FormRequest
{
    public function rules()
    {
        return [
            'import_bank' => ['required', 'mimes:xlsx,xls']
        ];
    }

    public function messages()
    {
        return [
            'import_bank.mimes' => 'Upload only a valid excel file type.'
        ];
    }
}
