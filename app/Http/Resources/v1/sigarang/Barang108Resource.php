<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class Barang108Resource extends JsonResource
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
            'akun' => $this->akun,
            'kelompok' => $this->kelompok,
            'jenis' => $this->jenis,
            'objek' => $this->objek,
            'rincian_objek' => $this->rincian_objek,
            'sub_rincian_objek' => $this->sub_rincian_objek,
            'kode' => $this->kode,
            'uraian' => $this->uraian,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
