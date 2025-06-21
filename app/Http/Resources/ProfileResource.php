<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->name,
            'email' => $this->email,
            'no. telp' => $this->phone,
            'kelas' => $this->group ? $this->group->name : null,
            'lkm' => $this->group ? $this->group->location->office->name : 'LKM',
            'image_url' => $this->image_url ? url('storage/'. $this->image_url) : url('images/default_image.png'),
            'fcm_token' => $this->fcm_token,
        ];
    }
}
