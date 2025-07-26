<?php

namespace App\Models\Sigarang;

use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyStokUpdate extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function penyesuaian()
    {
        return $this->hasOne(StokOpname::class);
    }

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
    }
    public function penerimaan()
    {
        return $this->belongsTo(Penerimaan::class, 'no_penerimaan', 'no_penerimaan');
    }
    public function depo()
    {
        return $this->belongsTo(Gudang::class, 'kode_ruang', 'kode');
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
    }
    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_ruang', 'kode');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('barang', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%')
                    ->orWhere('kode', 'LIKE', '%' . $query . '%')
                    ->withTrashed();
                // })->orWhereHas('ruang', function ($q) use ($query) {
                //     $q->where('uraian', 'like', '%' . $query . '%')
                //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            });
            $search->when($reqs['search'] ?? false, function ($search, $query) {
                return $search->where('kode_ruang', '=', $query);
                // $q->where('nama', 'like', '%' . $query . '%')
                //     ->orWhere('kode', 'LIKE', '%' . $query . '%');
                // })->orWhereHas('ruang', function ($q) use ($query) {
                //     $q->where('uraian', 'like', '%' . $query . '%')
                //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            });
            // ->orWhereHas('satuan', function ($q) use ($query) {
            //     $q->where('nama', 'like', '%' . $query . '%')
            //         ->orWhere('kode', 'LIKE', '%' . $query . '%');
            // });
        });
    }
}
