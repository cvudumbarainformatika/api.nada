<?php

namespace App\Models\Sigarang\Transaksi\Pemakaianruangan;

use App\Models\Sigarang\Pengguna;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Ruang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemakaianruangan extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(DetailsPemakaianruangan::class);
    }

    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_pengguna', 'kode');
    }

    public function penggunaruang()
    {
        return $this->belongsTo(PenggunaRuang::class, 'kode_pengguna', 'kode_pengguna');
    }

    public function ruangpengguna()
    {
        return $this->belongsTo(PenggunaRuang::class, 'kode_pengguna', 'kode_ruang');
    }

    public function ruanganmaster()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }
    public function recentstok()
    {
        return $this->hasMany(RecentStokUpdate::class, 'kode_ruang', 'kode_ruang');
    }



    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('no_penerimaan    ', 'LIKE', '%' . $query . '%');
            // ->orWhere('tanggal', 'LIKE', '%' . $query . '%');

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
