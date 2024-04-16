<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'department_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u", 'unique:departments,department_name'],
            // 'department_code' => ['required', 'regex:/^[0-9\pL\s\()&.,_-]+$/u', 'unique:departments,department_code'],
            // 'division_id' => ['required', Rule::notIn(['null'])],
            // 'division_cat_id' => ['required', Rule::notIn(['null'])],
            // 'company_id' => ['required', Rule::notIn(['null'])],
            // 'location_id' => ['required', Rule::notIn(['null'])],
        ];
    }

    function messages()
    {
        return [
            'department_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            // 'department_code.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            // 'division_id.not_in' => 'The division must have a valid value',
            // 'division_cat_id.not_in' => 'The division category must have a valid value',
            // 'company_id.not_in' => 'The company must have a valid value',
            // 'location_id.not_in' => 'The location must have a valid value',
        ];
    }
}
