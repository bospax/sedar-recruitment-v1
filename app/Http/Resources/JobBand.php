<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class JobBand extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'jobband_name' => $this->jobband_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'order' => $this->order,
            'created_at' => $created
        ];
    }
}
