<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    // public function authorize()
    // {
    //     return false;
    // }

    public function rules()
    {
        return [
            'position_name' => ['required', "regex:/^[0-9\pL\s\()&\/.,'_-]+$/u"],
            'department_id' => ['required', Rule::notIn(['null'])],
            'subunit_id' => ['required', Rule::notIn(['null'])],
            // 'location_id' => ['required', Rule::notIn(['null'])],
            // 'jobrate_id' => ['required', Rule::notIn(['null'])],
            'jobband_id' => ['required', Rule::notIn(['null'])],
            // 'custom_schedule' => ['required', Rule::notIn(['null'])],
            'tools' => ['required']
        ];
    }

    function messages()
    {
        return [
            'position_name.regex' => 'The title may only contain letters, numbers, spaces, dashes or underscores.',
            'department_id.required' => 'The department must have a valid value',
            'subunit_id.required' => 'The department must have a valid value',
            // 'location_id.required' => 'The location must have a valid value',
            // 'jobrate_id.required' => 'The department must have a valid value',
            'jobband_id.required' => 'The department must have a valid value'
        ];
    }
}
