<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProbationaryEmployee extends JsonResource
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
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->employment_type_label,
            'employment_date_start' => $this->employment_date_start,
            'employment_date_end' => $this->employment_date_end,
            'regularization_date' => $this->regularization_date,
            'hired_date' => $this->hired_date,
            'position_id' => $this->position_id,
            'position_name' => $this->position_name,
            'department_name' => $this->department_name,
            'subunit_name' => $this->subunit_name
        ];
    }
}
