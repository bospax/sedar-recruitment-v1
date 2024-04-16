<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Location extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'location_name' => $this->location_name,
            'status' => $this->status,
            'status_description' => $this->status_description,
            'created_at' => $created
        ];
    }
}
