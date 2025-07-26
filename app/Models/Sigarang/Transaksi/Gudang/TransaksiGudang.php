<?php

namespace App\Models\Sigarang\Transaksi\Gudang;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiGudang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function perusahaan()
    {
        return  $this->belongsTo(Supplier::class, 'kode_perusahaan', 'kode');
        // return $this->belongsTo(Satuan::class, 'kode', 'kode_satuan');
    }
    public function details()
    {
        return $this->hasMany(DetailTransaksiGudang::class);
    }
    public function asal()
    {
        return $this->belongsTo(Gudang::class, 'asal', 'kode');
    }
    public function tujuan()
    {
        return $this->belongsTo(Gudang::class, 'tujuan', 'kode');
    }

    public function details_kontrak()
    {
        return $this->belongsTo(KontrakPengerjaan::class, 'kontrak', 'nokontrak');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nomor', 'LIKE', '%' . $query . '%')
                ->orWhere('tanggal', 'LIKE', '%' . $query . '%');

            // ->orWhereHas('barangrs', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // })->orWhereHas('satuan', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // });
        });
    }
}
