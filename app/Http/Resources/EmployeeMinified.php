<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeMinified extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            // 'prefix_id' => $this->prefix_id,
            // 'id_number' => $this->id_number,
            // 'first_name' => $this->first_name,
            // 'middle_name' => $this->middle_name,
            // 'last_name' => $this->last_name,
            // 'suffix' => $this->suffix,
            // 'birthdate' => $this->birthdate,
            // 'religion' => $this->religion,
            // 'civil_status' => $this->civil_status,
            // 'gender' => $this->gender,
            'referrer_id' => $this->referrer_id,
            // 'image' => $this->image,
            // 'current_status_mark' => $this->current_status_mark,
            // 'remarks' => $this->remarks,
            'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            // 'r_prefix_id' => $this->r_prefix_id,
            // 'r_id_number' => $this->r_id_number,
            // 'r_first_name' => $this->r_first_name,
            // 'r_middle_name' => $this->r_middle_name,
            // 'r_last_name' => $this->r_last_name,
            // 'r_suffix' => $this->r_suffix,
            'r_full_id_number_full_name' => ($this->referrer_id) ? $this->r_prefix_id.'-'.$this->r_id_number.' '.$this->r_last_name.', '.$this->r_first_name.' '.$this->r_suffix.' '.$this->r_middle_name : '',
            // 'created_at' => $created
        ];
    }
}
