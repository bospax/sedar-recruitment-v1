<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAttainment extends JsonResource
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
            'employee_id' => $this->employee_id,
            'attainment' => $this->attainment,
            'course' => $this->course,
            'degree' => $this->degree,
            'honorary' => $this->honorary,
            'institution' => $this->institution,
            'attachment' => explode(',', $this->attachment),
            'years' => $this->years,
            'gpa' => $this->gpa,
            'academic_year_from' => $this->academic_year_from,
            'academic_year_to' => $this->academic_year_to,
            'attainment_remarks' => $this->attainment_remarks,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'gender' => $this->gender,
            'image' => $this->image,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
        ];
    }
}
