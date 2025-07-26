<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinMaxPengguna extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
    }


    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['barang'] || $reqs['pengguna'] ?? false, function ($search, $query) use ($reqs) {
            $barang = $reqs['barang'] ? $reqs['barang'] : '';
            $pengguna = $reqs['pengguna'] ? $reqs['pengguna'] : '';
            return $search->whereHas('barang', function ($q) use ($barang) {
                $q->where('nama', 'like', '%' . $barang . '%');
            })->whereHas('pengguna', function ($q) use ($pengguna) {
                $q->where('jabatan', 'like', '%' . $pengguna . '%');
            });
        });
        // $search->when($reqs['q'] ?? false, function ($search, $query) {
        //     return $search->whereHas('barang', function ($q) use ($query) {
        //         $q->where('nama', 'like', '%' . $query . '%');
        //     })->OrWhereHas('pengguna', function ($q) use ($query) {
        //         $q->where('jabatan', 'like', '%' . $query . '%');
        //     });
        //     // return $search->where('uraian', 'LIKE', '%' . $query . '%')
        //     //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        // });

        // $search->when($reqs['barang'] ?? false, function ($search, $query) {
        //     return $search->whereHas('barang', function ($q) use ($query) {
        //         $q->where('nama', 'like', '%' . $query . '%');
        //     });
        //     // return $search->where('uraian', 'LIKE', '%' . $query . '%')
        //     //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        // });
        // $search->when($reqs['pengguna'] ?? false, function ($search, $query) {
        //     return $search->whereHas('pengguna', function ($q) use ($query) {
        //         $q->where('jabatan', 'like', '%' . $query . '%');
        //     });
        //     // return $search->where('uraian', 'LIKE', '%' . $query . '%')
        //     //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        // });
    }
}
