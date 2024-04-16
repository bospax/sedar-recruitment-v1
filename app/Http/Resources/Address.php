<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Address extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'region' => $this->region,
            'province' => $this->province,
            'municipal' => $this->municipal,
            'barangay' => $this->barangay,
            'street' => $this->street,
            'zip_code' => $this->zip_code,
            'detailed_address' => $this->detailed_address,
            'foreign_address' => $this->foreign_address,
            'address_remarks' => $this->address_remarks,
            'reg_desc' => $this->reg_desc,
            'reg_code' => $this->reg_code,
            'prov_desc' => $this->prov_desc,
            'prov_code' => $this->prov_code,
            'citymun_desc' => $this->citymun_desc,
            'citymun_code' => $this->citymun_code,
            'brgy_desc' => $this->brgy_desc,
            'brgy_code' => $this->brgy_code,
            'prefix_id' => $this->prefix_id,
            'id_number' => $this->id_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->last_name,
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
