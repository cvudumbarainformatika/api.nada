<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratLuar extends Model
{
    use HasFactory;
    protected $table = 'lab_luar';

    protected $fillable =
    [
        'nama',
        'kelamin',
        'alamat',
        'pengirim',
        'tgl_lahir',
        'tgl',
        'nota',
        'kd_lab',
        'jml',
        'tarif_sarana',
        'tarif_pelayanan',
        'jenispembayaran',
        'jam_sampel_selesai',
        'jam_sampel_diambil',
        'sampel_selesai',
        'sampel_diambil',
        'perusahaan_id',
        'noktp',
        'nosurat',
        'temp_lahir',
        'agama',
        'nohp',
        'kode_pekerjaan',
        'nama_pekerjaan',
        'metode'
    ];
    public $timestamps = false;

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class);
    }
    public function pemeriksaan_laborat() // data master
    {
        return $this->belongsTo(PemeriksaanLaborat::class, 'kd_lab', 'rs1');
    }
    public function catatan() // data master
    {
        return $this->belongsTo(Interpretasi::class, 'nota', 'rs5');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nota', $query)
                ->orWhere('nama', $query)
                ->orWhere('pengirim', $query)
                ->orWhere('alamat', $query);
            // return $search->where('rs2', 'LIKE', '%' . $query . '%');
            // ->orWhere('nip', 'LIKE', '%' . $query . '%')
            // ->orWhere('judul', 'LIKE', '%' . $query . '%');
        });
        // $search->when($reqs['periode'] ?? false, function ($search, $query) {
        //     if ($query == 2) {
        //         return $search->where('rs20', '<>', '');
        //     }
        //     elseif ($query == 3) {
        //         return $search->whereDate('rs3', '<', date('Y-m-d'))
        //                         ->where('rs20', '=', '');
        //     }
        //     elseif ($query == 4) {
        //         return $search->whereDate('rs3', '<', date('Y-m-d'))
        //                     ->where('rs20', '<>', '');
        //     }
        //     else {
        //         return $search->where('rs20', '=', '');
        //     }
        // });

        // $search->when($reqs['status'] ?? false, function ($search, $sta) {
        //     return $search->where(['status'=>$sta]);
        // });

        // $search->when($reqs['category'] ?? false, function ($search, $query) {
        //     return $search->whereHas('categories', function($finder) use ($query) {
        //         if ($query !== 'all') {
        //             $finder->where('url', $query);
        //         }

        //     });
        // });
    }
}
