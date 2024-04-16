<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeState extends JsonResource
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
            'employee_id' => $this->employee_id,
            'employee_state_label' => $this->employee_state_label,
            'employee_state' => $this->employee_state,
            'state_date_start' => $this->state_date_start,
            'state_date_end' => $this->state_date_end,
            'state_date' => $this->state_date,
            'created_at' => $created,
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
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name
        ];
    }
}
