<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'user_id' => $this->user_id,
            'schedule_latitude' => $this->schedule_latitude,
            'schedule_longitude' => $this->schedule_longitude,
            'schedule_check_in' => $this->schedule_check_in,
            'schedule_check_out' => $this->schedule_check_out,
            'date' => $this->date,
            'check_in_latitude' => $this->check_in_latitude,
            'check_in_longitude' => $this->check_in_longitude,
            'check_in_time' => $this->check_in_time,
            'check_out_latitude' => $this->check_out_latitude,
            'check_out_longitude' => $this->check_out_longitude,
            'check_out_time' => $this->check_out_time,
            'status' => $this->status,
        ];
    }
}
