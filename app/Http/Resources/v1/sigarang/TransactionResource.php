<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'reff' => $this->reff,
            'nomor' => $this->nomor,
            'faktur' => $this->faktur,
            'tanggal' => $this->tanggal,
            'nama' => $this->nama,
            'total' => $this->total,
            'tempo' => $this->tempo,
            'kontrak' => $this->kontrak,
            'details_kontrak' => $this->whenLoaded('details_kontrak'),
            'details' => $this->whenLoaded('details'),
            'kode_perusahaan' => $this->kode_perusahaan,
            'perusahaan' => $this->whenLoaded('perusahaan'),
            'kode_gudang' => $this->kode_gudang,
            'gudang' => $this->whenLoaded('gudang'),
            'pengguna_id' => $this->pengguna_id,
            'pengguna' => $this->whenLoaded('pengguna'),
            'ruang_id' => $this->ruang_id,
            'ruang' => $this->whenLoaded('ruang'),
            'kode_stok_minimum' => $this->kode_stok_minimum,
            'stok_minimum' => $this->whenLoaded('stok_minimum'),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
