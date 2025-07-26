<?php

namespace App\Models\Sigarang;

use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $table = 'pihak_ketiga';
    protected $fillable = [];

    public function penerimaan()
    {
        return $this->hasMany(Penerimaan::class, 'kode_perusahaan', 'kode');
    }
    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('kode', 'LIKE', '%' . $query . '%')
                ->orWhere('nama', 'LIKE', '%' . $query . '%')
                ->orWhere('kodemapingrs', 'LIKE', '%' . $query . '%');
        });
    }
}
