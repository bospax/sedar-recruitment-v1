<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Position extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'department_id' => $this->department_id,
            'subunit_id' => $this->subunit_id,
            'location_id' => $this->location_id,
            'jobband_id' => $this->jobband_id,
            'position_name' => $this->position_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'payrate' => $this->payrate,
            'employment' => $this->employment,
            'no_of_months' => $this->no_of_months,
            'schedule' => $this->schedule,
            'shift' => $this->shift,
            'team' => $this->team,
            'job_profile' => $this->job_profile,
            'attachments' => explode(',', $this->attachments),
            'tools' => $this->tools,
            'department_name' => $this->department_name,
            'subunit_name' => $this->subunit_name,
            'location_name' => $this->location_name,
            'jobband_name' => $this->jobband_name,
            'order' => $this->order,
            'superior' => $this->superior,
            's_full_name_filter' => ($this->s_prefix_id) ? $this->s_first_name.' '.$this->s_middle_name.' '.$this->s_last_name.' '.$this->s_suffix : '',
            's_full_name' => ($this->s_prefix_id) ? $this->s_last_name.', '.$this->s_first_name.' '.$this->s_suffix.' '.$this->s_middle_name : '',
            's_full_id_number_full_name' => ($this->s_prefix_id) ? $this->s_prefix_id.'-'.$this->s_id_number.' '.$this->s_last_name.', '.$this->s_first_name.' '.$this->s_suffix.' '.$this->s_middle_name : '',
            'created_at' => $created
        ];
    }
}
