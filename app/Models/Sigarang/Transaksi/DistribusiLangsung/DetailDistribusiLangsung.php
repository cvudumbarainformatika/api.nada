<?php

namespace App\Models\Sigarang\Transaksi\DistribusiLangsung;

use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailDistribusiLangsung extends Model
{
    use HasFactory;

    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function barang()
    {
        return $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
    }

    public function distribusi()
    {
        return $this->belongsTo(DistribusiLangsung::class);
    }

    public function stokruangan()
    {
        return $this->hasMany(RecentStokUpdate::class, 'no_penerimaan', 'no_penerimaan');
    }

    public function getHargaAttribute()
    {
        $no_penerimaan = $this->no_penerimaan;
        $kode_rs = $this->kode_rs;
        $data = RecentStokUpdate::select('harga')->where('kode_rs', $kode_rs)
            ->where('no_penerimaan', $no_penerimaan)->get();
        $harga = 0;
        if (count($data) > 0) {
            $harga = $data[0]->harga;
        }
        return $harga;
    }
}
