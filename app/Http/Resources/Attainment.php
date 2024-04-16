<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class Attainment extends JsonResource
{
    public function toArray($request)
    {
        $created = Carbon::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y h:i a');

        return [
            'id' => $this->id,
            'attainment_name' => $this->attainment_name,
            'created_at' => $created
        ];
    }
}
