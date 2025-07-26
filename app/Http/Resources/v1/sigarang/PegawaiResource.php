<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class PegawaiResource extends JsonResource
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
            'nip' => $this->nip,
            'nip_baru' => $this->nip_baru,
            'nik' => $this->nik,
            'nama' => $this->nama,
            // 'alamat' => $this->alamat,
            // 'kelamin' => $this->kelamin,
            // 'templahir' => $this->templahir,
            // 'tgllahir' => $this->tgllahir,
            // 'jenispegawai' => $this->jenispegawai,
            // 'flag' => $this->flag,
            // 'jabatan' => $this->jabatan,
            // 'profesi' => $this->profesi,
            // 'jabatan_tmb' => $this->jabatan_tmb,
            // 'golruang' => $this->golruang,
            // 'pendidikan' => $this->pendidikan,
            'aktif' => $this->aktif,
            // 'foto' => $this->foto,
            // 'bagian' => $this->bagian,
            // 'ruang' => $this->ruang,
            // 'tgl_masuk' => $this->tgl_masuk,
            // 'tgl_tmt' => $this->tgl_tmt,
            // 'id_simrs' => $this->id_simrs,
            // 'kategoripegawai' => $this->kategoripegawai,
            // 'pass': '81dc9bdb52d04dc20036dbd8313ed055',
            // 'alamat_detil' => $this->alamat_detil,
            // 'rt' => $this->rt,
            // 'rw' => $this->rw,
            // 'kelurahan' => $this->kelurahan,
            // 'kecamatan' => $this->kecamatan,
            // 'kota' => $this->kota,
            // 'agama' => $this->agama,
            // 'tmt_cpns' => $this->cpns,
            // 'gaji_total' => $this->gaji_total,
            // 'gaji_pokok' => $this->gaji_pokok,
            // 'kel_ttg' => $this->kel_ttg,
            // 'th_mk_tmb' => $this->th_mk_tmb,
            // 'bln_mk_tmb' => $this->bln_mk_tmb,
            // 'jurusan' => $this->jurusan,
            // 'flagpas' => $this->flagpas,
            // 'telp' => $this->tlp,
            'email' => $this->email,
            // 'id_absen' => $this->id_absen,
            // 'jadwalkerja' => $this->jadwalkerja
        ];
    }
}
