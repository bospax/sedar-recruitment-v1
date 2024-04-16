<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeDataChange extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

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
            'image' => $this->image,
            'current_status_mark' => $this->current_status_mark,
            'remarks' => $this->remarks,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'created_at' => $created,
            'department_name' => $this->department_name,
            'position_name' => $this->position_name,
            'jobband_name' => $this->jobband_name,
            'position_id' => $this->position_id,
            'subunit_name' => $this->subunit_name,
            'location_name' => $this->location_name,
            'company_name' => $this->company_name,
            'division_name' => $this->division_name,
            'category_name' => $this->category_name
        ];
    }
}
