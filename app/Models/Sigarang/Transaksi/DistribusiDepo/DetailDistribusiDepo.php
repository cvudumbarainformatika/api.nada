<?php

namespace App\Models\Sigarang\Transaksi\DistribusiDepo;

use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailDistribusiDepo extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];
    public function barang108()
    {
        return $this->belongsTo(Barang108::class, 'kode_108', 'kode')->withTrashed();
        // return $this->belongsTo(Barang108::class, 'kode', 'kode_108');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function distribusi()
    {
        return $this->belongsTo(DistribusiDepo::class, 'distribusi_depo_id');
    }
    public function recent()
    {
        return $this->hasMany(RecentStokUpdate::class, 'no_penerimaan', 'no_penerimaan');
    }
}
