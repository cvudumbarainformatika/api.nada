<?php

namespace App\Http\Resources\v1\sigarang\Transaksi;

use Illuminate\Http\Resources\Json\JsonResource;

class PemesananResource extends JsonResource
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
            'kontrak' => $this->kontrak,
            'tanggal' => $this->tanggal,
            'kode_perusahaan' => $this->kode_perusahaan,
            'perusahaan' => $this->whenLoaded('perusahaan'),
            'details' => $this->whenLoaded('details'),
            'details_kontrak' => $this->whenLoaded('details_kontrak'),
            'total' => $this->total,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
