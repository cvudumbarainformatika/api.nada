<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class DetailTransactionResource extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'kode_rs' => $this->kode_rs,
            'barangrs' => $this->whenLoaded('barangrs'),
            'kode_108' => $this->kode_108,
            'barang108' => $this->whenLoaded('barang108'),
            'kode_satuan' => $this->kode_satuan,
            'satuan' => $this->whenLoaded('satuan'),
            'qty' => $this->qty,
            'harga' => $this->harga,
            'price' => $this->harga,
            'sub_total' => $this->sub_total,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
