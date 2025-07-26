<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'level_1' => $this->level_1,
            'level_2' => $this->level_2,
            'level_3' => $this->level_3,
            'level_4' => $this->level_4,
            'kode' => $this->kode,
            'jabatan' => $this->jabatan,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
