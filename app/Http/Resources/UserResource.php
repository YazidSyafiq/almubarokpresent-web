<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nama' => $this->name,
            'email' => $this->email,
            'no. telp' => $this->phone,
            'image_url' => $this->image_url ? url('storage/'. $this->image_url) : url('images/default_image.png'),
            'face_embedding' => $this->face_embedding,
            'token' => $this->token,
            'fcm_token' => $this->fcm_token,
        ];
    }
}
