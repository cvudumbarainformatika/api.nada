<?php

namespace App\Models\Sigarang\Transaksi\Pemakaianruangan;

use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailsPemakaianruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];
    // protected $appends = ['harga'];


    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }
    public function barang108()
    {
        return  $this->belongsTo(Barang108::class, 'kode_rs', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function pemakaianruangan()
    {
        return $this->belongsTo(Pemakaianruangan::class);
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
