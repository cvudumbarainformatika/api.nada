<?php

namespace App\Models\Pegawai;

use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Ruang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    protected $casts = [
        'jadwal' => 'array'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'user_id');
    }
    public function kategory()
    {
        return $this->belongsTo(Kategory::class);
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->whereHas('user_id', function ($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%');
                // ->orWhere('kode', 'LIKE', '%' . $query . '%');
                // return $search->where('jenispegawai', 'LIKE', '%' . $query . '%');
                // ->orWhere('nama', 'LIKE', '%' . $query . '%');
            });
        });
    }
}
