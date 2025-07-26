<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class MappingBarangResource extends JsonResource
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
            'kode_108' => $this->kode_108,
            'barang108' => $this->whenLoaded('barang108'),
            'kode_rs' => $this->kode_rs,
            'barangrs' => $this->whenLoaded('barangrs'),
            'kode_satuan' => $this->kode_satuan,
            'satuan' => $this->whenLoaded('satuan'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
