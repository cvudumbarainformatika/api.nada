<?php

namespace App\Models\Sigarang;

use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DetailDistribusiLangsung;
use App\Models\Sigarang\Transaksi\Gudang\DetailTransaksiGudang;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\Pemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemesanan\DetailPemesanan;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangRS extends Model
{
    use HasFactory, SoftDeletes;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }
    public function satuankecil()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan_kecil', 'kode');
    }
    public function barang108()
    {
        return $this->belongsTo(Barang108::class, 'kode_108', 'kode');
    }
    public function rekening50()
    {
        return $this->belongsTo(Rekening50::class, 'kode_50', 'kode');
    }

    public function mapingbarang()
    {
        return $this->hasOne(MapingBarang::class, 'kode_rs', 'kode');
    }

    public function mapingdepo()
    {
        return $this->hasOne(MapingBarangDepo::class, 'kode_rs', 'kode');
    }
    public function depo()
    {
        return $this->belongsTo(Gudang::class, 'kode_depo', 'kode');
    }
    public function detailPemesanan()
    {
        return $this->hasMany(DetailPemesanan::class, 'kode_rs', 'kode');
    }
    public function detailPenerimaan()
    {
        return $this->hasMany(DetailPenerimaan::class, 'kode_rs', 'kode');
    }
    public function detailDistribusiDepo()
    {
        return $this->hasMany(DetailDistribusiDepo::class, 'kode_rs', 'kode');
    }
    public function detailDistribusiLangsung()
    {
        return $this->hasMany(DetailDistribusiLangsung::class, 'kode_rs', 'kode');
    }
    public function detailTransaksiGudang()
    {
        return $this->hasMany(DetailTransaksiGudang::class, 'kode_rs', 'kode');
    }
    public function detailPermintaanruangan()
    {
        return $this->hasMany(DetailPermintaanruangan::class, 'kode_rs', 'kode');
    }
    public function detailPemakaianruangan()
    {
        return $this->hasMany(DetailsPemakaianruangan::class, 'kode_rs', 'kode');
    }
    public function detail_pemakaianruangan()
    {
        return $this->hasMany(DetailsPemakaianruangan::class, 'kode_rs', 'kode');
    }
    public function monthly()
    {
        return $this->hasMany(MonthlyStokUpdate::class, 'kode_rs', 'kode');
    }

    public function recent()
    {
        return $this->hasMany(RecentStokUpdate::class, 'kode_rs', 'kode');
    }
    public function fisik()
    {
        return $this->hasMany(StokOpname::class, 'kode_rs', 'kode');
    }


    public function stok_awal()
    {
        return $this->hasMany(MonthlyStokUpdate::class, 'kode_rs', 'kode');
    }

    public function masukgudang()
    {
        return $this->hasManyThrough(
            Penerimaan::class,
            DetailPenerimaan::class,
            'kode_rs',  // Kunci asing di tabel yang menghubungkan(tabel detail)...
            'id',  // Kunci asing di tabel yang di tuju(heder)...
            'kode',  // Kunci lokal pada tabel master(barang)...
            'penerimaan_id', // Kunci lokal di tabel penghubung(detail)...
        );
    }
    public function hargastok()
    {
        return $this->hasOneThrough(
            RecentStokUpdate::class,
            DetailsPemakaianruangan::class,
            'kode_rs',  // Kunci asing di tabel yang menghubungkan(tabel detail)...
            'no_penerimaan',  // Kunci asing di tabel yang di tuju(heder)...
            'kode',  // Kunci lokal pada tabel master(barang)...
            'no_penerimaan', // Kunci lokal di tabel penghubung(detail)...
        );
    }

    public function keluargudang()
    {
        return $this->hasManyThrough(
            DistribusiDepo::class,
            DetailDistribusiDepo::class,
            'kode_rs',
            'id',
            'kode',
            'distribusi_depo_id'
        );
    }

    public function stok_akhir()
    {
        return $this->hasMany(MonthlyStokUpdate::class, 'kode_rs', 'kode');
    }

    public function pengeluarandepo()
    {
        return $this->hasManyThrough(
            Permintaanruangan::class,
            DetailPermintaanruangan::class,
            'kode_rs',
            'id',
            'kode',
            'permintaanruangan_id'
        );
    }

    public function pemakaianruangan()
    {
        return $this->hasManyThrough(
            Pemakaianruangan::class,
            DetailsPemakaianruangan::class,
            'kode_rs',
            'id',
            'kode',
            'pemakaianruangan_id'
        );
    }

    public function rincianpenerimaan()
    {
        return $this->hasMany(DetailPenerimaan::class, 'kode_rs', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}
