<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'employee_id' => ['required'],
            'region' => ['required'],
            'province' => ['required'],
            'municipal' => ['required'],
            'barangay' => ['required'],
            // 'street' => ['required'],
            'zip_code' => ['required', 'numeric']
        ];
    }
}
