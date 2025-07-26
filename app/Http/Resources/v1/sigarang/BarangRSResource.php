<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class BarangRSResource extends JsonResource
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
            'kode' => $this->kode,
            'nama' => $this->nama,
            'kode_satuan' => $this->kode_satuan,
            'kode_108' => $this->kode_108,
            'satuan' => $this->whenLoaded('satuan'),
            'barang108' => $this->whenLoaded('barang108'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
