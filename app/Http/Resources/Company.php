<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Company extends JsonResource
{
    public function toArray($request)
    {
        // modifying the returned resource
        
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'company_name' => $this->company_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'created_at' => $created
        ];
    }
}
