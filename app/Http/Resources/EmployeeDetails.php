<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDetails extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'birthdate' => $this->birthdate,
            'religion' => $this->religion,
            'civil_status' => $this->civil_status,
            'gender' => $this->gender,
            'referrer_id' => $this->referrer_id,
            'image' => $this->image
        ];
    }
}
