<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Approver extends JsonResource
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
            'employee_id' => $this->employee_id,
            'level' => $this->level, 
            'action' => $this->action, 
            'image' => $this->image,
            'full_name' => $this->last_name.', '.$this->first_name.' '.$this->suffix.' '.$this->middle_name,
            'position_name' => $this->position_name,
            'department_name' => $this->department_name
        ];
    }
}
