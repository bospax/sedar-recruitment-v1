<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubunitRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'subunit_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u", 'unique:subunits,subunit_name'],
            'department_id' => ['required', Rule::notIn(['null'])]
        ];
    }

    function messages()
    {
        return [
            'subunit_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            'department_id.not_in' => 'The department must have a valid value'
        ];
    }
}
