<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenggunaRuang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kode_ruang', 'kode');
        // return $this->belongsTo(Ruang::class, 'kode', 'kode_ruang');
    }

    public function penanggungjawab()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }
    public function pj()
    {
        return $this->belongsTo(Pengguna::class, 'kode_penanggungjawab', 'kode');
    }

    public function pengguna()
    {
        return $this->belongsTo(Pengguna::class, 'kode_pengguna', 'kode');
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('ruang', function ($q) use ($query) {
                $q->where('uraian', 'like', '%' . $query . '%');
            })->orWhereHas('pengguna', function ($q) use ($query) {
                $q->where('jabatan', 'like', '%' . $query . '%');
            })->orWhereHas('penanggungjawab', function ($q) use ($query) {
                $q->where('jabatan', 'like', '%' . $query . '%');
            });
        });
    }
}
