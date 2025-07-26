<?php

namespace App\Models\Sigarang\Transaksi;

use App\Models\Barang108;
use App\Models\BarangRS;
use App\Models\KontrakPengerjaan;
use App\Models\Pengguna;
use App\Models\Satuan;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];
    public function barang108()
    {                               // deploy   // enviro
        return $this->hasManyThrough(
            Barang108::class,
            DetailTransaction::class,
            'transaction_id',
            'kode',
            'id',
            'kode_108',
            // 'kode'
        );
        // return $this->belongsTo(Barang108::class, 'kode', 'kode_108');
    }

    public function barangrs()
    {
        return  $this->hasManyThrough(BarangRS::class, DetailTransaction::class, 'transaction_id', 'kode_rs',  'id', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    // public function satuan()
    // {
    //     return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    //     // return $this->belongsTo(Satuan::class, 'kode', 'kode_satuan');
    // }

    public function perusahaan()
    {
        return  $this->belongsTo(Supplier::class, 'kode_perusahaan', 'kode');
    }
    public function pengguna()
    {
        return  $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }
    public function ruang()
    {
        return  $this->belongsTo(Supplier::class, 'kode_ruang', 'kode');
    }

    public function details()
    {
        return $this->hasMany(DetailTransaction::class);
    }

    public function details_kontrak()
    {
        return $this->belongsTo(KontrakPengerjaan::class, 'kontrak', 'nokontrak');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('details.barangrs', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            })->orWhere('nomor', 'LIKE', '%' . $query . '%');
            // return $search->whereHas('barang108', function ($q) use ($query) {
            //     $q->where('uraian', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // })->orWhereHas('barangrs', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            //     // })->orWhereHas('satuan', function ($q) use ($query) {
            //     //     $q->where('nama', 'like', '%' . $query . '%')
            //     //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // })->orWhere('pemesanan', 'LIKE', '%' . $query . '%');
        });
    }
}
