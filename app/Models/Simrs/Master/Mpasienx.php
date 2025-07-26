<?php

namespace App\Models\Simrs\Master;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpasienx extends Model
{
    use HasFactory;
    protected $table = 'rs15x';
    protected $appends = ['usia'];

    // public function agama()
    // {
    //     return $this->belongsTo(Magama::class, 'rs22', 'rs1');
    // }

    public function getRs31Attribute($value)
    {
        return $value > '1' ? 'Lama' : 'Baru';
    }

    public function getUsiaAttribute()
    {
        $dateOfBirth = $this->tgllahir;
        $years = Carbon::parse($dateOfBirth)->age;
        $month = Carbon::parse($dateOfBirth)->month;
        $day = Carbon::parse($dateOfBirth)->day;
        return $years . " Tahun " . $month . " Bulan " . $day . " Hari";
    }

    public function scopePasienx($data)
    {
        $data->select([
            'rs1 as norm',
            'rs2 AS nama',
            'rs3 AS sapaan',
            'rs4 as  alamat',
            'rs5 aS  kelurahan',
            'rs6 AS kecamatan',
            'rs7 as rt',
            'rs8 as rw',
            'rs10 as propinsi',
            'rs11 as kabupatenkota',
            'rs16 as tgllahir',
            'rs17 as kelamin',
            'rs19 as pendidikan',
            'rs22 as agama',
            'rs37 as templahir',
            'rs39 as suku',
            'rs49 as nik',
            'rs46 as nokabpjs',
            'rs40 as baru',
            'alamatdomisili as alamatdomisili',
            'kd_pekerjaan as pekerjaan',
            'kd_kel as kodekelurahan',
            'kd_kec as kodekecamatan',
            'kd_propinsi as kodepropinsi',
            'kd_kota as kodekabupatenkota',
            'kd_kelamin as kodekelamin',
            'kd_agama as kodemapagama',
            'rs55 as noteleponhp',
            'bahasa as bahasa',
            'noidentitaslain as nomoridentitaslain',
            'namaibu as namaibukandung',
            'kodepos as kodepos',
            'kd_negara as negara',
            'kd_rt_dom as rtdomisili',
            'kd_rw_dom as rwdomisili',
            'kd_kel_dom as kelurahandomisili',
            'kd_kec_dom as kecamatandomisili',
            'kd_kota_dom as kabupatenkotadomisili',
            'kodeposdom as kodeposdomisili',
            'kd_prov_dom as propinsidomisili',
            'kd_negara_dom as negaradomisili',
            'noteleponrumah as noteleponrumah',
            'flag_pernikahan as statuspernikahan',
            'gelardepan as gelardepan',
            'gelarbelakang as gelarbelakang',
            'bacatulis as bacatulis',
            'kdhambatan as kdhambatan'
        ]);
    }

    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('rs1', 'LIKE', '%' . $query . '%')
                    ->orWhere('rs2', 'LIKE', '%' . $query . '%')
                    ->orWhere('rs46', 'LIKE', '%' . $query . '%')
                    ->orWhere('rs49', 'LIKE', '%' . $query . '%')
                    ->orWhere('rs55', 'LIKE', '%' . $query . '%')
                    ->orderBy('rs1');
            }
        );
    }
}
