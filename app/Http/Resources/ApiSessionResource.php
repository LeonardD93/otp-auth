<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApiSessionResource extends JsonResource
{
    
    public function toArray($request)
    {
        $user = $this->user;
        $arr_out = [
            'id'=> $this->id,
            'user_id'=> $user->user_id,
            'token'=> $this->token,
            'expired_at'=> $this->expired_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        return $arr_out;
    }
    
}