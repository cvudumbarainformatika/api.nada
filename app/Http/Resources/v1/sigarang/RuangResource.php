<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class RuangResource extends JsonResource
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
            'gedung' => $this->gedung,
            'namagedung' => $this->whenLoaded('namagedung'),
            'lantai' => $this->lantai,
            'ruang' => $this->ruang,
            'kode' => $this->kode,
            'uraian' => $this->uraian,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
