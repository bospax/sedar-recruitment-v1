<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class FormHistory extends JsonResource
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
            'form_id' => $this->form_id,
            'form_type' => $this->form_type,
            'reviewer_action' => $this->reviewer_action,
            'form_data' => json_decode($this->form_data),
            'status' => $this->status,
            'status_mark' => $this->status_mark,
            'reviewer_id' => $this->reviewer_id,
            'review_date' => $this->review_date,
            'reviewer_attachment' => explode(',', $this->reviewer_attachment),
            'remarks' => $this->remarks,
            'level' => $this->level,
            'requestor_id' => $this->requestor_id,
            'employee_id' => $this->employee_id,
            'is_fulfilled' => $this->is_fulfilled,
            'date_fulfilled' => $this->date_fulfilled,
            'description' => $this->description,
            'created_at' => $created,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'reviewer_position' => $this->reviewer_position,
            'reviewer_department' => $this->reviewer_department,
            'rev_full_id_number' => $this->prefix_id.'-'.$this->id_number, 
            'rev_full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'rev_full_id_number_full_name' =>  $this->prefix_id.'-'.$this->id_number.' '.$this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
        ];
    }
}
