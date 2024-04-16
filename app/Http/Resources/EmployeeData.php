<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeData extends JsonResource
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
            'general_info' => [
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
                // 'image' => $this->image,
                // 'current_status_mark' => $this->current_status_mark,
                // 'remarks' => $this->remarks,
                'full_id_number' => $this->prefix_id.'-'.$this->id_number, 
                'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
                'full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
                // 'referrer_prefix_id' => $this->referrer_prefix_id,
                // 'referrer_id_number' => $this->referrer_id_number,
                // 'referrer_first_name' => $this->referrer_first_name,
                // 'referrer_middle_name' => $this->referrer_middle_name,
                // 'referrer_last_name' => $this->referrer_last_name,
                // 'referrer_suffix' => $this->referrer_suffix,
                // 'referrer_full_id_number_full_name' => ($this->referrer_id) ? $this->referrer_prefix_id.'-'.$this->referrer_id_number.' '.$this->referrer_last_name.', '.$this->referrer_first_name.' '.$this->referrer_suffix.' '.$this->referrer_middle_name : '',
                // 'created_at' => $created,
                'contact_details' => (isset($this->contact_details)) ? $this->contact_details : '',
            ],

            'position_info' => [
                'position_name' => $this->position_name,
                'schedule' => $this->schedule,
                'shift' => $this->shift,
                'team' => $this->team,
                'tools' => $this->tools
            ],

            'unit_info' => [
                'department_name' => $this->department_name,
                'subunit_name' => $this->subunit_name,
                'jobband_name' => $this->jobband_name,
                'location_name' => $this->location_name,
                'division_name' => $this->division_name,
                'category_name' => $this->category_name,
                'company_name' => $this->company_name,
            ],

            // 'employment_info' => [
            //     'employment_type_label' => $this->employment_type_label
            // ],

            // 'status_info' => [
            //     'employee_state_label' => $this->employee_state_label
            // ]
        ];
    }
}
