<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaRuangResource extends JsonResource
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
            'kode_ruang' => $this->kode_ruang,
            'ruang' => $this->whenLoaded('ruang'),
            'kode_penanggungjawab' => $this->kode_penanggungjawab,
            'penanggungjawab' => $this->whenLoaded('penanggungjawab'),
            'kode_pengguna' => $this->kode_pengguna,
            'pengguna' => $this->whenLoaded('pengguna'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
