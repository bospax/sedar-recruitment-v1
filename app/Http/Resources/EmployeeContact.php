<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeContact extends JsonResource
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
            'tid' => $this->id,
            'temployee_id' => $this->employee_id,
            'tcontact_type' => $this->contact_type,
            'tcontact_details' => $this->contact_details,
            'tdescription' => $this->description,
        ];
    }
}
