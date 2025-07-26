<?php

namespace App\Models\Sigarang;

use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rekening50 extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('uraian', 'LIKE', '%' . $query . '%')
                ->orWhere('kode', 'LIKE', '%' . $query . '%');
        });
    }

    public function rincianpenerimaan()
    {
        return $this->hasMany(DetailPenerimaan::class, 'kode_50', 'kode');
    }
    public function barangrs()
    {
        return $this->hasMany(BarangRS::class, 'kode_50', 'kode');
    }
}
