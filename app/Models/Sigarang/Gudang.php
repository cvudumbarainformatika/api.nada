<?php

namespace App\Models\Sigarang;

use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
        });
    }

    public function minmax()
    {
        return $this->hasOne(Mminmaxobat::class, 'kd_ruang', 'kode');
    }
}
