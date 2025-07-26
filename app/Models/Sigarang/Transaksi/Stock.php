<?php

namespace App\Models\Sigarang\Transaksi;

use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Pengguna;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kode_gudang', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('barang', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            })->OrWhereHas('gudang', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
            })->OrWhereHas('pengguna', function ($q) use ($query) {
                $q->where('jabatan', 'like', '%' . $query . '%');
            });
            // return $search->where('uraian', 'LIKE', '%' . $query . '%')
            //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }
}
