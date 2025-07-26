<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class KontrakPengerjaanResource extends JsonResource
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
            'nokontrak' => $this->nokontrak,
            'kodeperusahaan' => $this->kodeperusahaan,
            'namaperusahaan' => $this->namaperusahaan,
            'tglmulaikontrak' => $this->tglmulaikontrak,
            'tglakhirkontrak' => $this->tglakhirkontrak,
            'tgltrans' => $this->tgltrans,
            'kodepptk' => $this->kodepptk,
            'namapptk' => $this->namapptk,
            'program' => $this->program,
            'kegiatan' => $this->kegiatan,
            'kodekegiatanblud' => $this->kodekegiatanblud,
            'kegiatanblud' => $this->kegiatanblud,
            'kode50' => $this->kode50,
            'uraianpekerjaan' => $this->uraianpekerjaan,
            'nilaikegiatan' => $this->nilaikegiatan,
            // 'userentry' => $this->userentry,
            // 'tglentry' => $this->tglentry,
            // 'kunci' => $this->kunci,
            // 'flag' => $this->flag,
            // 'kodemapingrs' => $this->kodemapingrs,
            'namasuplier' => $this->namasuplier,
            'nilaikontrak' => $this->nilaikontrak,

        ];
    }
}
