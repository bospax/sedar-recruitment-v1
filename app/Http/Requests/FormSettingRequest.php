<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FormSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'form_type' => ['required'],
            'number_of_levels' => ['required'],
            'label' => ['required'],
        ];
    }
}
