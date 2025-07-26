<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'alamat' => $this->alamat,
            'telepon' => $this->telepon,
            'npwp' => $this->npwp,
            'norek' => $this->norek,
            'cp' => $this->cp,
            'bank' => $this->bank,
            'hidden' => $this->hidden,
            'kodemapingrs' => $this->kodemapingrs,
            'namasuplier' => $this->namasuplier,
        ];
    }
}
